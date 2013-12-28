<?php
//require_once(DIRNAME(__FILE__) .'/GoodsAction.php');
require_once(DIRNAME(__FILE__) . '/../../config.inc.php');
//require_once(DIRNAME(__FILE__).'/ImageAction.php');
require_once(DIRNAME(__FILE__) . '/hmacauth.class.php');

class UserAction {
    private $hauth;

    function __construct() {
        global $link;
        $this->hauth = new hmacauth($link,'user');
    }

    public function fallback() {
        //没有可选函数时调用
        echo "Use Action Fallback.\n";
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

    //User.php?op=viewMyinfo&access_token=xxxx
    public function viewMyinfo(){
        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];


        global $link;
        $sql="select u.user_id, u.user_name, s.school_name, u.user_sno, u.user_regtime, 
					 u.user_credit, u.user_realname, u.user_sex, u.user_tel, u.user_address, 
					 u.user_qq, u.user_renren, u.user_imgpath  
				from user as u, school as s  
				where u.user_id='$user_id' and u.school_id=s.school_id " ; 
        if($res= @mysql_query($sql, $link)){
            $row= mysql_fetch_row($res);
            
            	$user_name=$row[1];
           	 	$school_name=$row[2];
            	$user_sno=$row[3];
            	$user_regtime=$row[4];
            	$user_credit=$row[5];
            	$user_nickname=$row[6];
            	$user_sex=$row[7];
            	$user_tel=$row[8];
            	$user_address=$row[9];
            	$user_qq=$row[10];
            	$user_renren=$row[11];
            	$user_imgpath=$row[12];
            

        }else{
            echo json_encode(array("status"=>"wrong","reason"=>"Database error!" . mysql_error()) );
            return ;
        }

        $sql="SELECT COUNT(*) 
            FROM history
            WHERE `user_id` ='$user_id'";
        if($res= @mysql_query($sql, $link)) {
            $row= mysql_fetch_row($res);	
            $user_sellnum=$row[0];
        }
        else {
            echo json_encode(array("status"=>"wrong","reason"=>"Database error2!" . mysql_error()) );
            return;
        }
        $response = array("user_id"=> $user_id,
            "user_name"=> $user_name,
            "school_name"=> $school_name,
            "user_sno"=> $user_sno,
            "user_regtime"=> $user_regtime,
            "user_sellnum"=> $user_sellnum,
            "user_credit"=> $user_credit,
            "user_realname"=> $user_nickname,
            "user_sex"=> $user_sex,
            "user_tel"=> $user_tel,
            "user_address"=> $user_address,
            "user_qq"=> $user_qq,
            "user_renren"=> $user_renren,
            "user_imgpath"=> $user_imgpath	);

       echo json_encode(array("status"=>"ok","response"=>$response));
      
    }

	
    public function viewFavouriteList(){
        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];
        global $link;
		
		
		$pagenum=$_GET['page'];
		
        $sql="select goods.goods_id,goods.goods_title,goods.goods_cretime, goods.goods_price, goods.goods_smallimgpath
            from goods,favourite
            where goods.goods_id=favourite.goods_id and favourite.user_id='$user_id' 
            order by goods.goods_cretime desc
            limit $pagenum, 10";
        if($res= @mysql_query($sql, $link)){
            $response = array();
            $res_count = 0;
            while($row = mysql_fetch_row($res)) {
			
				if($row[0]==null &&$row[1]==null && $row[2]==null && $row[3]==null && $row[4]==null){
				}else{
			
					$goods_id=$row[0];
					$goods_title=$row[1];
					$goods_cretime=$row[2];
					$goods_price=$row[3];
					$goods_smallimgpath=$row[4];

					$response[$res_count] = array("goods_id"=> $goods_id,
							"goods_title"=> $goods_title,
							"goods_cretime"=> $goods_cretime,
							"goods_price"=>$goods_price,
							"goods_smallimgpath"=> $goods_smallimgpath);
					$res_count++;
				}//else
            }//while


            echo json_encode(array("status"=>"ok","response"=>$response));   
        }
        else {
            echo json_encode(array("status"=>"error", "error_messege" => "Database Error!Cannot find!"));
        }
    }


    public function viewHistoryList(){

        global $link;
        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];
		
		$pagenum=$_GET['page']*10;
        $sql="select goods.goods_id,goods.goods_title,goods.goods_cretime, goods.goods_price, goods.goods_smallimgpath
            from goods,history
            where goods.goods_id=history.goods_id and history.user_id='$user_id' 
            order by goods.goods_cretime desc
            limit $pagenum, 10";

        if($res= @mysql_query($sql, $link)){

            $response = array();
            $res_count = 0;

            while($row=mysql_fetch_row($res)){
				
				if($row[0]==null &&$row[1]==null && $row[2]==null && $row[3]==null && $row[4]==null){
				}else{

					$goods_id=$row[0];
					$goods_title=$row[1];
					$goods_cretime=$row[2];
					$goods_price=$row[3];
					$goods_smallimgpath=$row[4];

					$response[$res_count]=array("goods_id"=> $goods_id,
							"goods_title"=> $goods_title,
							"goods_price"=> $goods_price,
							"goods_cretime"=> $goods_cretime,
							"goods_smallimgpath"=> $goods_smallimgpath);

					$res_count++;
				}//else
            }

            echo json_encode(array("status"=>"ok","response"=>$response));   
        }else{
            echo json_encode(array("status"=>"error", "error_messege" => "Database Error!Cannot find!"));
        }
    }


    public function viewPostList(){

        global $link;
        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
		
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];
		
		$pagenum=$_GET['page']*10;
		
        $sql="select goods_id,goods_title,goods_cretime, goods_price,goods_smallimgpath 
            from goods
            where user_id='$user_id' 
            order by goods_cretime desc 
            limit $pagenum, 10";
        
			
        if($res= @mysql_query($sql, $link)){

            $response = array();
            $res_count = 0;

            while($row=mysql_fetch_row($res)){		
				if($row[0]==null &&$row[1]==null && $row[2]==null && $row[3]==null && $row[4]==null){
				}else{
               
					$goods_id=$row[0];
					$goods_title=$row[1];
					$goods_cretime=$row[2];
					$goods_price=$row[3];
					$goods_smallimgpath=$row[4];
								

					$response[$res_count]=array("goods_id"=> $goods_id,
							"goods_title"=> $goods_title,
							"goods_price"=> $goods_price,
							"goods_cretime"=> $goods_cretime,
							"goods_smallimgpath"=> $goods_smallimgpath);

					$res_count++;
				}
            }

            echo json_encode(array("status"=>"ok","response"=>$response));   
        }else{
            echo json_encode(array("status"=>"error", "error_messege" => "Database Error!Cannot find!"));
        }
    }


    public function deleteFavourite(){
        global $link;
        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];
        $goods_id=$_GET['goods_id'];

        $sql="delete from favourite
            where goods_id='$goods_id' and user_id='$user_id'";

        if($res=@mysql_query($sql,$link)){
            echo 'Delete favourite successfully!'."<br>";
        }else{
            echo json_encode(array("status"=>"error","reason"=>"Database Error!Cannot delete favourite!"));
        }

    }


    public function deleteHistory(){
        global $link;
        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];
        $goods_id=$_GET['goods_id'];

        $sql="delete from history
            where goods_id='$goods_id' and user_id='$user_id'";

        if($res=@mysql_query($sql,$link)){
            echo 'Delete history successfully!'."<br>";
        }else{
            echo json_encode(array("status"=>"error","reason"=>"Database Error!Cannot delete history!"));
        }

    }


    public function deletePost(){//删除商品，同时删除bcs里的图片
        global $link;
        global $bucket;
        global $baeImageService;
        global $baiduBCS;

        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];
        $goods_id=$_GET['goods_id'];

        $check=new GoodsAction();
        if($check->checkPermission($goods_id,$user_id)){


            $sql="select goods_bigimgpath,goods_midimgpath,goods_smallimgpath
                from goods
                where goods_id='$goods_id'";
            $res=@mysql_query($sql,$link);
            $row=mysql_fetch_row($res);
            $goods_bigimgpath=$row[0];
            $goods_midimgpath=$row[1];
            $goods_smallimgpath=$row[2];

            $sql="delete from goods
                where goods_id='$goods_id' and user_id='$user_id'
                ";
            if($res=@mysql_query($sql,$link)){
                echo 'Delete Post successfully!'."<br>";
            }else{
                echo json_encode(array("status"=>"error","reason"=>"Database Error!Cannot delete post!"));
            }

            $deleteimage=new ImageAction();
            $deleteimage->deletePostImg($goods_bigimgpath,$goods_midimgpath,$goods_smallimgpath);
        }else{}


    }//deletePost




    public function updateMyinfo() {
    	global $link;
        $access_token = $_GET['access_token'];
        $userInfo = $this->hauth->getAuthUser($access_token);
        if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
        $user_id  = $userInfo['user_id'];
		
		
        $user_name=$_GET['user_name'];
		$user_sex = $_GET['user_sex'];
		$user_tel = $_GET['user_tel'];
		$user_address = $_GET['user_address'];
		$user_qq = $_GET['user_qq'];
		
      
		$sql="update user
				set user_name='$user_name', user_sex='$user_sex',user_tel='$user_tel',user_qq='$user_qq',user_address='$user_address'
				where user_id='$user_id'
		";
		if($res= @mysql_query($sql, $link)){
			echo json_encode(array("status"=>"ok","response"=>"Update myinfo successfully."));
		
		}
		
		
    }
  public function addAdvice(){
  	$access_token = $_GET['access_token'];
    $userInfo = $this->hauth->getAuthUser($access_token);
    if($userInfo === false ) {
            echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
            return ;
        }
    $user_id  = $userInfo['user_id'];
    
    $text = $_GET['advice'];
    
    $sql="INSERT INTO `advice`(`user_id`, `advice`) VALUES ('$user_id','$text')";
    
    $res = @mysql_query($sql, $link);
        if($res) {
            $ret['ok'] = 1;
        }
        else {
            $ret['ok'] = 2;
        }
  	  echo json_encode($ret);
  }
  
  
  
   public function isLeagalId(){
  	$id = $_GET['id'];
    $sid = $_GET['sid'];
    $len=strlen($id);
    $ret['ok'] = 0;
     if($sid==4){
    	if($len==8&&preg_match("/^[34]{1}[0-1]{1}[0-9]{6}$/",$id)){   
			 $ret['ok'] = 1;
		}else if($len==9&&preg_match("/^[SBG]{1}199[0-9]{4}$|[SBG]{1}20[01]{1}[0-9]{5}$/",$id)){
		 $ret['ok'] = 1;
        }
	}
     if($sid==1){
       if($len==9&&preg_match("/^[01]{1}[0-9]{1}[01]{1}[0-9]{3}[1-6]{1}[0-4]{1}[0-9]{1}$/",$id))
          $ret['ok'] = 1;
       else if($len==7){
		 $ret['ok'] = 1;
		}
    }
     if($sid==2&&$len==10&&preg_match("/^199[0-9]{7}$|20[01]{1}[0-9]{7}$/",$id)){
		 $ret['ok'] = 1;
	}
	 echo json_encode($ret);
  }

}
?>
