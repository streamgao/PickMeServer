<?php
require_once(DIRNAME(__FILE__) . '/../../config.inc.php');
require_once(DIRNAME(__FILE__) . '/hmacauth.class.php');

class AuthAction {
  private $hauth;
  function __construct() {
    global $link;
    $this->hauth = new hmacauth($link,'user', false);
  }

  public function findUserid($access_token){
	
			//$access_token = $_GET['access_token'];
			$userInfo = $this->hauth->getAuthUser($access_token);
			if($userInfo === false ) {
				echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
				return false;
			}
			$user_id  = $userInfo['user_id'];
			return $user_id;
	
  }
  
  public function fallback() {
    echo "AuthAction Fallback.";
  }
  //Auth.php?op=isLog&access_token=xxxxxxxx
  //已登录返回1
  public function isLog() {//判断用户是否登录
    $accToken = $_GET['access_token'];
    if( empty($accToken) || $accToken=="" ) echo "0";
    else {
      $info = $this->hauth->getAuthUser($accToken);
      if($info === false)  echo "0";
      else echo "1";
    }
  }


  //Auth.php?op=logInStep1GetAuthtoken&user_name=xxx
  //返回auth_token
  //如果返回的auth_token为空字符串，则是产生了错误，可能是用户名非法
  public function logInStep1GetAuthtoken() {//用户登录
    $userName = $_GET['user_name'];
    $authToken = $this->hauth->step1GetAuthToken($userName);
    $ret = array();
    $ret['ok'] = 0;
    if(strlen($authToken) == 10) $ret['ok'] = 1;
    $ret['auth_token'] = $authToken;
    echo json_encode($ret);	
  }//logInStep1()


  //Auth.php?op=logInStep2GetAccesstoken&user_name=xxx&auth_token=xxxx&auth_hash=xxxx
  //返回access_token
  //如果返回的access_token为空字符串，校验失败
  public function logInStep2GetAccesstoken() {//用户登录
    $userName = $_GET['user_name'];
    $authToken = $_GET['auth_token'];
    $authHash = $_GET['auth_hash'];
    $errorInfo = '';

    $accToken = $this->hauth->step2Auth($userName,$authToken,$authHash, &$errorInfo);

    $ret = array();
    $ret['ok'] = 0;
    $ret['error_info'] = $errorInfo;
    if(strlen($accToken)>10) {
      $ret['ok'] = 1;
      $ret['access_token'] = $accToken;
    }
    echo json_encode($ret);
  }//logInStep2()

  //Auth.php?op=logOut&access_token=xxxx
  public function logOut() {//用户登出
    $accToken = $_GET['access_token'];
    $ret = $this->hauth->logOut($accToken);
    echo json_encode($ret);
  }//

  //Auth.php?op=register&user_name=xxx&password_hash=xxx
  //password_hash is sha256(password)
  public function register(){
    $userName = $_GET['user_name'];
    $passwdHash = $_GET['password_hash'];
    $ret = $this->hauth->register($userName, $passwdHash);
    echo json_encode($ret);
  }
  
  public function register2(){
    $user_name = $_GET['user_name'];
    $password_hash = $_GET['password_hash'];
    $user_realname = $_GET['user_realname'];
    $user_sno = $_GET['user_sno'];
    $user_sex = $_GET['user_sex'];
    $school_id = $_GET['school_id'];
    $user_tel = $_GET['user_tel'];
    $user_address = $_GET['user_address'];
    $ret = $this->hauth->register2($user_name, $password_hash, $school_id,$user_realname, $user_sno, $user_sex, $user_tel, $user_address);
    echo json_encode($ret);
	/*global $link;
	$sql="select user_id
			from user
			where user_name='$user_name' and user_sno='$user_sno' and user_passwd_hash='$password_hash' and user_realname='$user_realname'
	";
	
	if($res=@mysql_query($sql,$link)){
		$row=mysql_fetch_row($res);
		$user_id=$row[0];
      //echo json_encode(array("status"=>"ok","user_id"=>$user_id));
	}
	*/
	
  }
  
  

  //Auth.php?op=refreshToken&access_token=xxxx
  public function refreshToken(){	
    $accToken = $_GET['access_token'];
    $ret = $this->hauth->getAuthUserRefreshToken($accToken);
    echo json_encode($ret);
  }


  public function alertPwd(){
  		global $link;

    	$access_token=$_GET['access_token'];
 	    $new=$_GET['Newpass'];
    	$old=$_GET['oldpass'];
    //echo $access_token."   ".$new."      ".$old;
    	$userInfo = $this->hauth->getAuthUser($access_token);
			if($userInfo === false ) {
				echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
				return false;
			}
	$user_id  = $userInfo['user_id'];
    $ret = array();
    if($user_id){
        $ret['ok'] = 0;
		$sql = "select user_passwd_hash from user where user_id = '$user_id'";
      	$res = @mysql_query($sql, $link);
       if(!$res) {
            $ret['ok'] = 2;
         echo json_encode($ret);
         return ;
        }
    	$row = mysql_fetch_row($res);
      if($row[0]!=$old){
      	 $ret['ok'] = 7;
         echo json_encode($ret);
        return ;
      }
        $sql = "UPDATE `user` SET `user_passwd_hash`= '$new' where `user_id` = $user_id";
        $res = @mysql_query($sql, $link);
        if($res) {
            $ret['ok'] = 1;
        }
        else {
            $ret['ok'] = 2;
        }
    }else{
    	 $ret['ok'] = 0;
    }
         echo json_encode($ret);
  }




}
?>
