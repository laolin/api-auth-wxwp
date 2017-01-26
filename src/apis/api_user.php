<?php
//WWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWW
/*
*/
class class_user{
    public static function main( $para1,$para2) {
      $res=API::msg(0,'Ok.user');
      return $res;
    }
    
    //返回用于加密密码的 salt str
    public static function salt( $para1,$para2) {
      $res=API::msg(0,'Ok.email');
      $res['data']=api_g("usr-salt");
      return $res;
    }
    

}
