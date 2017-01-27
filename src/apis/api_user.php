<?php
// ================================
/*
*/
class class_user{
  const TOKEN_SALT= 'I*f)Os*&iopna)78';
  public static function main( $para1,$para2) {
    $res=API::msg(0,'Ok.user');
    return $res;
  }
  
  //返回用于加密密码的 salt str
  public static function salt( $para1,$para2) {
    $res=API::msg(0,'Ok.');
    $res['data']=api_g("usr-salt");
    return $res;
  }
  
  //-1 非法
  //1 已存在
  //0 可以注册
  public static function exist( $para1,$para2) {
    $res=API::msg(0,'Ok.');
    $db=API::db();
    $name=API::INP('name');
    $res['data']= self::_isNameExist($name);
    return $res;
  }
  
  //需要 防机器人注册
  public static function reg( $para1,$para2) {
    $res=API::msg(0,'Ok.');
    $db=API::db();
    
    $name=API::INP('name');
    $pass=API::INP('pass');
    $salt=API::INP('salt');
    
    $salt_srv=api_g("usr-salt");
    
    $err='';
    $test1=self::_isNameExist($name);
    if($test1 == -1) {
      $err.='Invalid id. ';
    } else if($test1 == 1) {
      $err.='UserId Exist. ';
    }
    if(strlen($pass) != 32) {
      $err.='Error passwd format. ';
    }
    if($salt != $salt_srv['salt']) {
      $err.='Salt Error. ';
    }
    if(strlen($err)) {
      return API::msg(601,$err);
    }
    $prefix=api_g("usr-table-prefix");
    
    $r=$db->insert($prefix.'user',
      ['user_name'=>$name, 'user_pass'=>$pass] );
    $res['data']=$r;
    return $res;
  }


    
  //=====================================
  static  function _isNameValid($name) {
    //字母，数字，下划线。下划线不能在最前面或最后面。
    if (preg_match('/^[a-zA-Z\d][a-zA-Z\d_]{0,30}[a-zA-Z\d]$/i', $name)) {
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
