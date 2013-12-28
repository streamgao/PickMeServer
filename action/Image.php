<?php

require_once (DIRNAME(__FILE__) . "/class/ImageAction.php");



$image = new ImageAction();
$methods = get_class_methods($image);

if(empty($_GET['op'])|| $_GET['op']=='') {
    $image->fallback();
    die;
}

$op = $_GET['op'];

if(in_array($op, $methods)) {
    $image->$op();
}
else {
    $image->fallback();
}



?>