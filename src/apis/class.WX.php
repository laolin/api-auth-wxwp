<?php

class WX {
  static function GetToken() {
    //注意
    //目前依靠从下面文件中的函数 WxToken::xxx() 
    // 获取全局 access_token
    if(!file_exists( api_g('path-web') . '/WxToken.inc.php'))
      return API::msg(601,'Error missing file : WxToken.inc');
    require_once api_g('path-web') . '/WxToken.inc.php';
    $tok=WxToken::GetToken();
    return $tok;
  }
  static function getOpenIdByUid($appid,$uidArr) {
    $prefix=api_g("api-table-prefix");
    $db=API::db();
    
    $r=$db->select($prefix.'user_wx',
      ['uidBinded','openid'],
      [
        'and'=>[ 'uidBinded'=>$uidArr, 'appFrom'=>$appid ],
        'LIMIT'=>999
      ]
    );
    return $r;
  }

}

