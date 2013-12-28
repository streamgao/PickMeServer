<?php
require_once(DIRNAME(__FILE__) . '/class/AuthAction.php');

$auth = new AuthAction();
$methods = get_class_methods($auth);

if(empty($_GET['op'])|| $_GET['op']=='') {
    $auth->fallback();
    die;
}

$op = $_GET['op'];
if(in_array($op, $methods)) {
    $auth->$op();
}
else {
    $auth->fallback();
}



?>
