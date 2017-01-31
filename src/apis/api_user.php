<?php
// ================================
/*
*/

class class_user{
  public static function main( $para1,$para2) {
    $res=API::data(['time'=>time()]);
    return $res;
  }
  
  //返回用于加密密码的 salt str
  public static function salt( $para1,$para2) {
    return API::data(api_g("usr-salt"));
  }
  
  //-1: 非法
  //字符串: 已存在用户的密码
  //0: 可以注册
  public static function exist( $para1,$para2) {
    $uname=API::INP('uname');
    
    $code=0;
    $info='User not Exist.';
    $test1=self::_getPassByName($uname);
    if($test1 == -1) {
      $code=-1;
      $info='Invalid uname.';
    } else if($test1) {
      $info='User Exist.';
      $code=1;
    }
    return API::data(['code'=>$code,'info'=>$info]);
  }
  
  //需要 防机器人注册
  /*
  reg(注册)api说明
  
  输入参数：
    ver   版本号，目前规定为 'ab'，用于表示密码加密算法
    uname 有效用户名
    upass 用规定版本号对应算法加密过的密码
    
  返回：
    注册成功后，返回用户的 uid
  */
  public static function reg( $para1,$para2) {
    $db=API::db();
    
    $uname=API::INP('uname');
    $upass=API::INP('upass');
    $ver=API::INP('ver');
    
    $salt_srv=api_g("usr-salt");
    
    $err='';
    if(strlen($upass) != 32) {
      $err.='Error passwd format. ';
    }
    if($ver != $salt_srv['version']) {
      $err.='RegVerStr Error. ';
    }
    if( ! $err ) {
      $test1=self::_getPassByName($uname);
      if($test1 == -1) {
        $err.='Invalid uname. ';
      } else if($test1) {
        $err.='User Exist. ';
      }
    }
    if(strlen($err)) {
      return API::msg(600,$err);
    }
    $prefix=api_g("api-table-prefix");
    
    $r=$db->insert($prefix.'user',
      ['uname'=>$uname, 'upass'=>$salt_srv['version'].':'.$upass] );
    return API::data($r);
  }

  //需要 防机器人 登录
  //正式使用时不需要 对错误的返回值进行区分，不利于安全 
  /*
  login(登录) api说明
  
  输入参数：
    uid   有效的用户 uid
    uname 有效用户名 （当无 uid 时，才使用 uname ）
    upass 用规定版本号对应算法加密过的密码
    //tokenid 目前都是1，即每用户只有一个token，以后允许多tok
    
  返回：
    注册成功后，返回用户的 uid, uname, token
  */

  public static function login( $para1,$para2) {
  
    $uid=API::INP('uid');//先 根据 id 登录
    $uname=API::INP('uname'); //没有 id 时，再使用 username
    $upass=API::INP('upass');
    
    if(strlen($upass) != 32) {
      return API::msg(702,'Error passwd format. ');
    }
    if ($uid) {
      $p_right=self::_getPassById($uid);
    } else {
      $p_right=self::_getPassByName($uname);//会一起返回id
    }
    if($p_right == -1) {
      return API::msg(703,'Invalid uname/uid.');
    } else if(! $p_right) {
      return API::msg(704,'User Not Exist.');
    }
     
    if($upass != substr($p_right['upass'],3)) { //数据库密码前3个字符用于表示版本号
      return API::msg(705,'Passwd mismatch.');
    }
    if (! $uid) {
      $uid=$p_right['uid'];
    }

    $tokenid=1;//目前都是1，即每用户只有一个token，以后允许多tok
    $tok=self::_tokGen($uid,$tokenid);
    self::_tokSave($uid,$tokenid,$tok);
    return API::data(['uid'=>$uid,'uname'=>$p_right['uname'],'token'=>$tok]);
  }

    
  //=====================================
  static  function _isNameValid($name) {
    //字母，数字，下划线。下划线不能在最前面或最后面。
    if (preg_match('/^[a-zA-Z][a-zA-Z\d_]{0,30}[a-zA-Z\d]$/i', $name)) {
      return true;
    } else {
      return false;
    }
  }
  static  function _isIdValid($uid) {
    if( preg_match('/^[\d]{1,30}$/i', $uid)) {
      return true;
    }
    return false;
  }
  
  //返回值约定：
  //对象: 已存在用户的密码, id
  //0: 用户名不存在，可以注册
  //-1: 用户名非法
  static  function _getPassById($uid) {
    if(!self::_isIdValid($uid)) {
      return -1;
    }
    $db=API::db();
    
    $prefix=api_g("api-table-prefix");
    $rr=$db->get($prefix.'user',['uid','uname','upass'],
      ['uid'=>$uid ]  );

    if(count($rr)>0) {
      return $rr;
    }
    return 0;
  }
  
  //返回值约定：
  //对象: 已存在用户的密码, id
  //0: 用户名不存在，可以注册
  //-1: 用户名非法
  static  function _getPassByName($uname) {
    if(!self::_isNameValid($uname)) {
      return -1;
    }
    $db=API::db();
    
    $prefix=api_g("api-table-prefix");
    $sth = $db->pdo->prepare("SELECT `uid`,`uname`,`upass`  
         FROM `{$prefix}user` 
	       WHERE UPPER(`uname`) = UPPER(:uname) LIMIT 1");
    $sth->bindParam(':uname', $uname, PDO::PARAM_STR, 32);
    $sth->execute();
    $rr=$sth->fetchAll();
    //$ee=$sth->errorInfo();
    /*
    $r=$db->select($prefix.'user',
      ['user_pass'],
      [ 'AND'=>['uname'=>$uname],
        "LIMIT" => 1]);
        */
    if(count($rr)==1) {
      return $rr[0];
    }
    return 0;
  }
  
  // =========================================
  
  static  function _tokGen($uid, $tokenid) {
    list($usec, $sec) = explode(' ', microtime());
    srand($sec + $usec * 1000000);
    $salt=api_g("usr-salt");
    $salt_ver=$salt['version'];
    $str=substr( sha1( $salt['salt'] . rand().uniqid().$_SERVER['REMOTE_ADDR']) ,-6);//先取短点，若有需要可以取长点
    
    $day=0+date('ymd',time() );
    
    return "$salt_ver-$uid-$tokenid-$day-$str";
  }
  static  function _tokGet($uid,$tokenid) {
    $db=API::db();
    $prefix=api_g("api-table-prefix");
    $rr=$db->get($prefix.'token',['token'],
      ['and'=>['uid'=>$uid,'tokenid'=>$tokenid ],
          "LIMIT" => 1]  );

    if(isset($rr['id']) && $rr['token']) {
      return $rr['token'];
    }
    return '';
  }
  static  function _tokSave($uid,$tokenid,$tok) {
    $db=API::db();
    $prefix=api_g("api-table-prefix");
    
    $ip=$_SERVER['REMOTE_ADDR'];
    
    $rr=$db->get($prefix.'token',['id'],
      ['and'=>['uid'=>$uid,'tokenid'=>$tokenid ]]  );

    if(isset($rr['id']) && $rr['id']) {
      $id=$rr['id'];
      $r=$db->update($prefix.'token',
        [ 'uid'=>$uid ,'tokenid'=>$tokenid , 'token'=>$tok , 'ip'=>$ip ] ,
        [ 'AND'=>['id'=>$id],
          "LIMIT" => 1]);
    } else {
      $r=$db->insert($prefix.'token',
        [ 'uid'=>$uid ,'tokenid'=>$tokenid , 'token'=>$tok , 'ip'=>$ip ] );
    }
    return $r;
  }
}
