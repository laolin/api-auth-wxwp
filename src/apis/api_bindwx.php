<?php

class class_bindwx{
  
  //先写死域名列表在这里
  public static function WX_DOMAINS() {
    return
    [
      'qinggaoshou.com','www.qinggaoshou.com','api.qinggaoshou.com','app.qinggaoshou.com',
      'linjp.com','api.linjp.com','app.linjp.com',
      'linjp.cn','api.linjp.cn','app.linjp.cn',
      'laolin.com','www.laolin.com','api.laolin.com','app.laolin.com',
      'localhost','127.0.0.1'
    ];
  }
  public static function main( ) {

    $res=API::data(['time'=>time(),self::WX_DOMAINS()]);
    return $res;
  }
  
  /**
   *  
非微信开放邢台指定域名页面使用微信扫码登录方法，

1，非微信开放平台指定域名 页面（自己的其他网站A），
可显示二维码扫描。

2，微信开放平台调用指定域名内（自己的网站B）的回调页面：
网站B通过state，识别是来自A站，实现跳回A站的页面。
并带上code。
见 API： callback_xdom

3，A站拿到code是不能直接获取 微信信息的，但是可能在 B 站做一个API，通过code，取得用户信息以JSONP，返回给A站。
见 API： callback_auth
   */
  // callback_xdom 这个API不是返回JSON数据，是给微信服务器回调的。
  public static function callback_xdom(  ) {
    $code=API::GET('code');
    $state=urldecode(API::GET('state'));
    $ss=explode('~',$state);
    if(! $code) {
      return API::data(['invalid code',$code,$state]);
    }
    if(count($ss)!=3) {
      return API::data(['state item != 3',$code,$state]);
    }
    if( $ss[0]!='cb_xd' ) { //固定字符串，同客户端app统一约定的
      return API::data(['state item 1 error: '.$ss[0],$code,$state]);
    }
    $apps=api_g('WX_APPS');
    if(! isset( $apps[$ss[1]] )) {
      return API::data(['invalid app-name: '.$ss[1],$code,$state]);
    }
    
    //第二步客户端的 HTML5 页面的 URI 信息
    $urlObj=parse_url($ss[2]);
    
    //第三步告诉客户端再调用的服务端 API 的路径
    $apipath=($_SERVER['HTTPS']? 'https://':'http://') .$_SERVER["HTTP_HOST"].
      explode('callback_xdom',$_SERVER['REQUEST_URI'])[0];
    
    if( !in_array($urlObj['host'],self::WX_DOMAINS() ) ) {
      return API::data(['invalid domain:'.$dom,$code,$state]);
    }
    
    
    //第二步客户端的 HTML5 页面的 URI 信息
    $url="$urlObj[scheme]://$urlObj[host]";
    if($urlObj[port])
      $url.=":$urlObj[port]";
    $url.="$urlObj[path]?";
    if($urlObj['query'])
      $url.=$urlObj['query']."&";
    $url.="_ret_code=$code&_ret_app=".$ss[1];
  
    $url.="#$urlObj[fragment]";
  

    header('Location: ' .$url);
    //die();
    return API::data([$url,$code,$state,$ss[2],$urlObj]);
 }
  
  public static function callback_auth( $para1, $para2 ) {
    $code=API::GET('code');
    $app=API::GET('app');
    if(strlen($code)<10)return API::msg(1001,'Error auth code.');
    return BINDWX::oauth($code,$app);
  }
  
}
class BINDWX{
  static public function oauth($code,$app){
    //1，微信回传来的$code
    //$ret=[];
    
    //$ret[]=$code;

    $apps=api_g('WX_APPS');
    if(!isset($apps[$app])) {
      return API::msg(1001,'Error app name');
    }
    $appid=$apps[$app][0];
    $appsec=$apps[$app][1];
    //2, 根据code，换回 access_token 
    $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $appsec . '&code=' . $code . '&grant_type=authorization_code';
    $json_token = json_decode(file_get_contents($url),true);
    
    if(!$json_token['access_token'])
      return API::msg(1001,'Error get access_token');
    //$ret[]=$json_token;

    
    //3, 获取用户信息
    $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $json_token['access_token'] . '&openid=' . $json_token['openid'];
    $user_info = json_decode(file_get_contents($info_url),true);
    if(!$user_info['openid'])
      return API::msg(1001,'Error get user_info');
    
    //$ret[]=$user_info;
    unset($user_info['privilege']);//这个一般是空数组格式，不保存
    $uname='wx-'.substr($user_info['openid'],-8);
    $newUserId = self::user_save($user_info,$uname);
    $tokenid=$app;
    $newToken=USER::__ADMIN_addToken($newUserId,$tokenid);
    $newToken['uname']=$uname;
    $newToken['wxinfo']=$user_info;
    return API::data($newToken);

  }
  static public function user_save($user_info,$uname){
    $prefix=api_g("api-table-prefix");
    $db=API::db();
    
    $r_old=$db->get($prefix.'user_wx',
      ['id','uidBinded'],
      ['and'=>['openid'=>$user_info['openid'] ] ]);
    $uidBinded=0;
    if($r_old) {
      api_g('$r_old',$r_old);
      $uidBinded=$r_old['uidBinded'];
    }
    if(!$uidBinded) { // not binded to any uid, so create one
      $r_ad=USER::__ADMIN_addUser($uname,//用户名： wx-xxx
        'ab:'.time()//随便指定一个不可登录的密码
      );
      if($r_ad['errcode']!=0) {
        return $r_ad;
      }
      $uidBinded=$r_ad['data'];
      $user_info['uidBinded']=$uidBinded;
    }
      
    
    if($r_old) {
      $id=$r_old['id'];
      $r=$db->update($prefix.'user_wx',
        $user_info,
        ['and'=>['id'=>$r_old['id']],'limit'=>1]);
    } else {
      $r=$db->insert($prefix.'user_wx',
        $user_info);
      $id=$r;
    }
    api_g('$r_new',$r);
    
    return $uidBinded;

  }
}
