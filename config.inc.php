<?php
require_once('BaeImageService.class.php');

$baeImageService = new BaeImageService();
$baiduBCS = new BaiduBCS( $ak, $sk, $host);
$bucket = 'songzi';

//通过请求方法的不同获取method
if($_SERVER['REQUEST_METHOD'] === "POST" ){
  $method = $_POST['method'];
}else{
  $method = $_GET['method'];
}
 
/*设置数据库名*/
$db = 'gzFbmcjIEekCxKIjYrdw';
 
/*从环境变量里取出数据库连接需要的参数*/
$host_mysql = getenv('HTTP_BAE_ENV_ADDR_SQL_IP');
$port = getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
$user = getenv('HTTP_BAE_ENV_AK');
$pwd = getenv('HTTP_BAE_ENV_SK');

/*接着调用mysql_connect()连接服务器*/
$link = @mysql_connect("{$host_mysql}:{$port}",$user,$pwd,true);
if(!$link) {
    die("Connect Server Failed: " . mysql_error());
}
/*连接成功后立即调用mysql_select_db()选中需要连接的数据库*/
if(!mysql_select_db($db,$link)) {
    die("Select Database Failed: " . mysql_error($link));
}
 
mysql_query("set names utf8", $link);

?>
