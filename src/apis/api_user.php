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
    

  //=====================================
  static  function _isNameValid($name) {
    if (preg_match('/^[a-zA-Z\d_]{2,32}$/i', $name)) {
      return true;
    } else {
      return false;
    }
  } 
  
  //-1 非法
  //1 已存在
  //0 不存在，可以注册
  static  function _isNameExist($name) {
    if(!self::_isNameValid($name)) {
      return -1;
    }
    $db=API::db();
    
    $prefix=api_g("usr-table-prefix");
    $sth = $db->pdo->prepare("SELECT `user_pass`  
         FROM `{$prefix}user` 
	       WHERE UPPER(`user_name`) = UPPER(:name) LIMIT 1");
 
    $sth->bindParam(':name', $name, PDO::PARAM_STR, 32);
    $sth->execute();
    $rr=$sth->fetchAll();
    /*
    $prefix=api_g("usr-table-prefix");
    $r=$db->select($prefix.'user',
      ['user_pass'],
      [ 'AND'=>['user_name'=>$name],
        "LIMIT" => 1]);
        */
    if(count($rr)==1) {
      return 1;
    }
    return 0;
  }
}
