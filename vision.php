<?php
	$vision = $_GET['vision'];
	/*这里没有创建数据库*/
	$url="http://www.baidu.com/";
	/*if($vision!='1.0')
      echo json_encode(array("ok"=>"1","url"=>"http://www.baidu.com/"));//url是APK下载地址
    else*/
      echo json_encode(array("ok"=>"0" ,"url"=>"暂无更新"));
?>