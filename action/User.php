<?php


require_once(DIRNAME(__FILE__) . "/class/UserAction.php");

$user = new UserAction();
$methods = get_class_methods($user);

if(empty($_GET['op'])|| $_GET['op']=='') {
    $user->fallback();
    die;
}

$op = $_GET['op'];
if(in_array($op, $methods)) {
    $user->$op();
}
else {
    $user->fallback();
}




?>