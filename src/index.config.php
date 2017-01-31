<?php
//这个文件可以重定义，用来修改config
api_g("DBNAME",'test_01');
api_g("DBSERVER",'localhost');
api_g("DBPORT",3306);
api_g("DBUSER",'test_user');
api_g("DBPASS",'test_passwd');


api_g("WX_APPID",'');
api_g("WX_APPSEC",'');


//api_tbl_log 访问记录
//api_tbl_tokenbucket 令牌桶，用于控制访问量
//api_tbl_user 
//api_tbl_token
//api_tbl_bind
//api_tbl_wxuser
api_g("api-table-prefix",'api_tbl_');
api_g("usr-salt",['version'=>'ab','salt'=>'api_salt-laolin@&*']); //目前 version 长度 要求==2


//开头要有'/'，结束不能有'/'，从 index.php 所在路径相对计算
api_g("path-apis",[
  ]);

api_g('-cfg file--',__FILE__);
