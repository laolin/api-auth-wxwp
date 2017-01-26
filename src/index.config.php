<?php
//这个文件可以重定义，用来修改config
api_g("DBNAME",'test_01');
api_g("DBSERVER",'localhost');
api_g("DBPORT",3306);
api_g("DBUSER",'test_user');
api_g("DBPASS",'test_passwd');


api_g("WX_APPID",'');
api_g("WX_APPSEC",'');

//api_usr_user 
//api_usr_token
//api_usr_bind
//api_usr_wx
api_g("usr-table-prefix",'api_usr_');
api_g("usr-salt",['version'=>'1.0','salt'=>'api_salt-laolin@&*']);


//开头要有'/'，结束不能有'/'，从 index.php 所在路径相对计算
api_g("path-apis",[
  ]);

api_g('-cfg file--',__FILE__);
