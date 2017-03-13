<?php
class class_wx{
  public static function main( $para1,$para2) {
    return API::data( 'API of wx is ready.' );
  }
  public static function jsapisign( ) {
    $jsapiTicket=self::__jsapiTicket();
    if(API::is_error($jsapiTicket)) {
      return $jsapiTicket;
    }
    $app=API::GET('app');
    if( ! api_g('WX_APPS')[$app] )
      return API::msg(603,"Error app:'$app'");
    $app=api_g('WX_APPS')[$app][0];

    $sign = self::__getSign($jsapiTicket['data'],$app);
    return API::data( $sign );
  }
  
  /**
   *  /wx/mediaget API 和 Api-core 中的 /file/get API 接近。 待后面适当合并。
   */
  public static function mediaget( ) {
    if( ! USER::userVerify() ) {
      return API::msg(2001,'Error verify token.');
    }
    $tok=self::__accessToken();
    if(API::is_error($tok)) {
      return $tok;
    }
    $media_id=API::INP('media_id');
    if(!$media_id)
      return API::msg(621,'Error media_id');
    $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token=' .
       $tok['data']. '&media_id=' . $media_id;
    $res = self::__httpGet_withInfo($url);
    if(!$res['file'])
      return API::msg(622,'Error get media file');
    /**
     *  http://mp.weixin.qq.com/wiki/11/07b6b76a6b6e8848e855a435d5e34a5f.html
     *  正确情况下的返回HTTP头如下：
        HTTP/1.1 200 OK
        Connection: close
        Content-Type: image/jpeg 
        Content-disposition: attachment; filename="MEDIA_ID.jpg"
        Date: Sun, 06 Jan 2013 10:20:18 GMT
        Cache-Control: no-cache, must-revalidate
        Content-Length: 339721
        curl -G "https://api.weixin.qq.com/cgi-bin/media/get?access_token=ACCESS_TOKEN&media_id=MEDIA_ID"
        错误情况下的返回JSON数据包示例如下（示例为无效媒体ID错误）：:
        {"errcode":40007,"errmsg":"invalid media_id"}
     */
    api_g('___fname',$res['fname']);
    if($res['type']=="text/plain") {
      if(strpos($res['file'],'errcode')) {
        $e=json_decode($res['file'],true);
        return API::msg($e['errcode'],'Wx media get err:'.$e['errmsg']);//出错
      }
    }
    $pweb=api_g('path-web');
    $upstn=api_g("upload-settings");
    if( ! isset($upstn['path']) ) {
      return API::msg(2001,'upload-settings errors.');
    }
    $pup=$pweb . $upstn['path'];
    
    $filename=$res['fname'];
    $allowed=$upstn['ext'];  //which file types are allowed seperated by comma
    $extension_allowed=  explode(',', $allowed);
    $file_extension=  pathinfo($filename, PATHINFO_EXTENSION);
    if( ! in_array($file_extension, $extension_allowed) ) {
      return API::msg(1007,"$file_extension is not allowed");
    }
    $uid=api_g('userVerify')['uid'];
    if(!$uid)$uid='0';
    //var_dump($res);
    $newname= sprintf( 'wx_%s.%s',
        sha1( $res['file'] ),
        $file_extension
      );
    if( file_exists ($pup.'/'.$newname) ) {
      //相同的文件不用重复保存
    }
    G::saveFile($pup.'/'.$newname, $res['file']);
    $fdata=[
      'uid'=>$uid, 
      'fdesc'=>API::INP('desc'),
      'fname'=>$newname,
      'oname'=>$filename,
      'ftype'=>$res['type'],
      'ftime'=>time(),
      'fsize'=>$res['size']
    ];
    $db=api_g("db");
    $prefix=api_g("api-table-prefix");
    
    $r=$db->insert($prefix.'uploads',$fdata );

    return API::data(['name'=>$newname]);
		//;
  }  
  
  static function __jsapiTicket( ) {
    //注意 
    //目前依靠从下面文件中的函数 WxToken::xxx() 
    // 获取全局 JsApiTicket
    if(!file_exists( api_g('path-web') . '/WxToken.inc.php'))
      return API::msg(601,'Error missing file : WxToken.inc');
    require_once api_g('path-web') . '/WxToken.inc.php';
    $jsapiTicket=WxToken::GetJsApiTicket();
    if(!$jsapiTicket)
      return API::msg(602,'Error GetJsApiTicket');
    return API::data($jsapiTicket);
  }
  
  static function __accessToken( ) {
    //注意
    //目前依靠从下面文件中的函数 WxToken::xxx() 
    // 获取全局 access_token
    if(!file_exists( api_g('path-web') . '/WxToken.inc.php'))
      return API::msg(601,'Error missing file : WxToken.inc');
    require_once api_g('path-web') . '/WxToken.inc.php';
    $tok=WxToken::GetToken();
    if(!$tok)
      return API::msg(612,'Error GetToken');
    return API::data($tok);
  }

  static function __getSign($jsapiTicket,$app) {
    $url=API::GET('url');
    $timestamp = time();
    $nonceStr = self::__createNonceStr();
    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $app,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }
  static function __createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }
  
  static function __httpGet_withInfo($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    
    //这里来自微信提供的参考样例，但是这里php运行会出现有一个notice
    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($curl, CURLOPT_URL, $url);
    
    //curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
    //curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    //curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);

    $res = curl_exec($curl);
    
    list($headers, $body) = explode("\r\n\r\n", $res, 2);
    
    $reDispo = '/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/mi';
    if (preg_match($reDispo, $headers, $mDispo)) {
      $filename = trim($mDispo['f'],' ";');
    } else {
      $filename = '';
    }
      
    $info = ['file'=>$body];
    $info['size']= curl_getinfo($curl,CURLINFO_CONTENT_LENGTH_DOWNLOAD );
    $info['type'] = curl_getinfo($curl,CURLINFO_CONTENT_TYPE );
    $info['fname']=$filename;
    
    
    curl_close($curl);

    return $info;//$res;
  }

}
