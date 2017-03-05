<?php
class class_wx{
  public static function main( $para1,$para2) {
    return API::data( 'API of wx is ready.' );
  }
  public static function jsapisign( ) {
    //注意此API 
    //目前依靠从下面文件中的函数 WxToken::xxx() 
    // 获取全局access_token JsApiTicket
    if(!file_exists( api_g('path-web') . '/WxToken.inc.php'))
      return API::msg(6060,'Error missing file : WxToken.inc');
    $app=API::GET('app');
    if( ! api_g('WX_APPS')[$app] )
      return API::msg(603,"Error app:'$app'");
    $app=api_g('WX_APPS')[$app][0];
    require_once api_g('path-web') . '/WxToken.inc.php';
    $jsapiTicket=WxToken::GetJsApiTicket();
    if(!$jsapiTicket)
      return API::msg(602,'Error GetJsApiTicket');
    
    $sign = self::__getSign($jsapiTicket,$app);
    return API::data( $sign );
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

}
