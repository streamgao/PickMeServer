<?php

require_once (DIRNAME(__FILE__) . "/class/GoodsAction.php");



$goods = new GoodsAction();
$methods = get_class_methods($goods);

if(empty($_GET['op'])|| $_GET['op']=='') {
    $goods->fallback();
    die;
}

$op = $_GET['op'];
if(in_array($op, $methods)) {
    $goods->$op();
}
else {
    $goods->fallback();
}



?>