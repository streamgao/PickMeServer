<?php
require_once(DIRNAME(__FILE__) .'/ImageAction.php');
require_once(DIRNAME(__FILE__) . '/../../config.inc.php');
require_once(DIRNAME(__FILE__) . '/UserAction.php');
require_once(DIRNAME(__FILE__) . '/hmacauth.class.php');

class GoodsAction {
  
   		function __construct() {
       		 global $link;
       		 $this->hauth = new hmacauth($link,'user');
   		 }		
		 		 
		 
		public function fallback() {
        //没有可选函数时调用
				echo "Fallback.\n";
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

   		  //根据分类获取商品流信息 置顶优先排序
		public function getGoodsByCategory() {
    		
			global $link;
			$class_id = $_GET['class_id'];
			$pagenum=$_GET['page']*10;
			$sql="SELECT goods_id,goods_price,goods_midimgpath,goods_title,goods_info,user_id
					from goods 
					where class_id = '$class_id' 
					order by goods_cretime desc,goods_istop desc
					limit $pagenum, 10 ";
   				
			if($res = @mysql_query($sql, $link)){
      
				$response = array();
				$res_count = 0;
    
				while($row = mysql_fetch_row($res)) {
				
					if($row[0]==null &&$row[1]==null && $row[2]==null  && $row[3]==null && $row[4]==null&& $row[5]==null){
					}else{
          
						$goods_id = $row[0];
						$goods_price = $row[1];
						$goods_midimgpath = $row[2];
                        $goods_title = $row[3];
					
			
						$response[$res_count] = array("goods_id"=> $goods_id,
                                      "goods_price"=>$goods_price,
          							  "goods_midimgpath"=>$goods_midimgpath,
                                      "goods_title"=>$goods_title);
                                      
						$res_count++;
					}//else
				}//while
      
				echo json_encode(array("status"=>"ok","response"=>$response));   
			}//if
			else{
				echo json_encode(array("status"=>"error", "error_messege" => "Database Error!"));
			}
		}
  		
  		public function getGoodsByCategory1() {
    		
			global $link;
          	$school_id=$_GET['school_id'];
			$class_id = $_GET['class_id'];
			$pagenum=$_GET['page']*10;
			$sql="SELECT goods_id,goods_price,goods_midimgpath,goods_title,goods_info,user_id
					from goods 
					where class_id = $class_id and user_id in(select user_id from user where school_id = $school_id)
					order by goods_cretime desc,goods_istop desc
					limit $pagenum, 10";
			if($res = @mysql_query($sql, $link)){
      
				$response = array();
				$res_count = 0;
    
				while($row = mysql_fetch_row($res)) {
				
					if($row[0]==null &&$row[1]==null && $row[2]==null  && $row[3]==null && $row[4]==null&& $row[5]==null){
					}else{
          
						$goods_id = $row[0];
						$goods_price = $row[1];
						$goods_midimgpath = $row[2];
                        $goods_title = $row[3];
					
			
						$response[$res_count] = array("goods_id"=> $goods_id,
                                      "goods_price"=>$goods_price,
          							  "goods_midimgpath"=>$goods_midimgpath,
                                      "goods_title"=>$goods_title);
						$res_count++;
					}//else
				}//while
      
				echo json_encode(array("status"=>"ok","response"=>$response));   
			}//if
			else{
				echo json_encode(array("status"=>"error", "error_messege" => "Database Error!"));
			}
		}
  
  		public function getGoodsRandom(){
		
			global $link;

			$pagenum = $GET['page']*10;
			$sql = "SELECT goods_id,goods_price,goods_midimgpath,goods_title,user_id
						from goods			
						order by goods_cretime desc,goods_istop desc
						limit $pagenum, 10 ";
				
			if($res = @mysql_query($sql, $link)){
			
				$response = array();
				$res_count = 0;
				
				while($row = mysql_fetch_row($res)){
				
					if($row[0]==null &&$row[1]==null && $row[2]==null  && $row[3]==null && $row[4]==null){
					}else{//如果不是空，商品存在
						$goods_id = $row[0];
						$goods_price = $row[1];
						$goods_midimgpath = $row[2];
                        $goods_title = $row[3];
					
					
						$response[$res_count] = array("goods_id"=>$goods_id,
												"goods_price"=>$goods_price,
												"goods_midimgpath"=>$goods_midimgpath,
                                                "goods_title"=>$goods_title);
						$res_count++;
					}
				}
				
				echo json_encode(array("status"=>"ok","response"=>$response));
			}
			else{
				echo json_encode(array("status"=>"error", "error_messege"=>"Database Error!"));
			}
		}
    
        public function getDetailInfo() {
    
    		global $link;
    		$goods_id = $_GET['goods_id'];
			$access_token=$_GET['access_token'];
			$user_id= $this->findUserid($access_token);

			
			$sql="SELECT goods.goods_id, goods.goods_bigimgpath, goods.goods_title, goods.goods_price, goods.goods_cretime, goods.goods_info, class.class_name,
					user.user_name,user.user_tel,user.user_address
					from goods,user,class
					where goods.goods_id = '$goods_id' and goods.user_id = user.user_id and goods.class_id=class.class_id" ;
   		
			if($res = @mysql_query($sql, $link)){
      
						$row = mysql_fetch_row($res);
          
						$goods_id = $row[0];
						$goods_bigimgpath = $row[1];
						$goods_title = $row[2];
						$goods_price = $row[3];
						$goods_cretime = $row[4];
						$goods_info = $row[5];
						$class_name = $row[6];
						$user_name = $row[7];
						$user_tel = $row[8];
						$user_address = $row[9];
					
						$sql="SELECT COUNT( * ) 
							FROM favourite, goods
							WHERE goods.goods_id = favourite.goods_id
							AND goods.goods_id ='$goods_id '";
						$res = @mysql_query($sql, $link);
						$row = mysql_fetch_row($res);
						$favourite_num=$row[0];					

                    
						$response = array("goods_id"=> $goods_id,
									"goods_bigimgpath"=> $goods_bigimgpath,
									"goods_title"=> $goods_title,
									"goods_price"=> $goods_price,
									"favourite_num"=> $favourite_num,
									"goods_cretime"=> $goods_cretime,
									"goods_info"=> $goods_info,
									"class_name"=> $class_name,
									"user_name"=> $user_name,
                                    "user_tel"=>$user_tel,
          							"user_address"=>$user_address);
						
						$addhistory= new GoodsAction();
              $addhistory->addHistory($goods_id,$user_id);//添加商品浏览记录
						
											
      	
				echo json_encode(array("status"=>"ok","response"=>$response));   
			}else{
				echo json_encode(array("status"=>"error", "error_messege" => "Database Error!"));
			}

  		}//functionend
		
		public function getMyDetailInfo() {
    
    		global $link;
    		$goods_id = $_GET['goods_id'];
			$access_token=$_GET['access_token'];
			$user_id= $this->findUserid($access_token);
			
			$sql="select user_id from goods where goods_id='$goods_id' " ;
   			$res= @mysql_query($sql, $link);
			$goods_uid= mysql_fetch_row($res);

			
			$sql="SELECT goods.goods_id, goods.goods_bigimgpath, goods.goods_title, goods.goods_price, goods.goods_cretime, goods.goods_info, class.class_name
					from goods,class
					where goods.goods_id = '$goods_id' and goods.class_id=class.class_id" ;
   		
			if($res = @mysql_query($sql, $link)){
  
						$row = mysql_fetch_row($res);
          
						$goods_id = $row[0];
						$goods_bigimgpath = $row[1];
						$goods_title = $row[2];
						$goods_price = $row[3];
						$goods_cretime = $row[4];
						$goods_info = $row[5];
						$class_name = $row[6];
					
						$sql="SELECT COUNT( * ) 
							FROM favourite, goods
							WHERE goods.goods_id = favourite.goods_id
							AND goods.goods_id ='$goods_id '";
						$res = @mysql_query($sql, $link);
						$row = mysql_fetch_row($res);
						$favourite_num=$row[0];
						
					if($goods_uid[0]==$user_id){//是买家自己查看已发布的信息，返回已发布信息的详情页面
			
						$response = array("goods_id"=> $goods_id,
									"goods_bigimgpath"=> $goods_bigimgpath,
									"goods_title"=> $goods_title,
									"goods_price"=> $goods_price,
									"favourite_num"=> $favourite_num,
									"goods_cretime"=> $goods_cretime,
									"goods_info"=> $goods_info,
									"class_name"=> $class_name);
			
					}
					
				
      	
				echo json_encode(array("status"=>"ok","response"=>$response));   
			}else{
				echo json_encode(array("status"=>"error", "error_messege" => "Database Error!"));
			}

  		}//functionend
		
		
			
		
		
		  //搜索商品 以标题匹配关键字
		public function searchByKey(){
			$key=$_GET['key'];
			$pagenum = $GET['page']*10;
			global $link;
			$sql="SELECT goods_id,goods_title,goods_price,goods_midimgpath 
					from goods 
					where goods_title like \"%$key%\" 
					order by goods_cretime desc,goods_istop desc
					limit $pagenum, 10 ";
        
          
		
			if($res = @mysql_query($sql, $link)){
				$response = array();
				$res_count = 0;
    
				while($row = mysql_fetch_row($res)) {
          
					$goods_id = $row[0];
					$goods_title = $row[1];
					$goods_price = $row[2];
					$goods_midimgpath = $row[3];
        
			
					$response[$res_count] = array("goods_id"=> $goods_id,
                                      "goods_title"=> $goods_title,
                                      "goods_price"=>$goods_price,
          							  "goods_midimgpath"=>$goods_midimgpath);
					$res_count++;
				}//while
      
				echo json_encode(array("status"=>"ok","response"=>$response));   
			}//if
			else{
				echo json_encode(array("status"=>"error", "error_messege" => "Database Error!Cannot find goods!"));
			}
		}
		
		
		public function addHistory($goods_id,$user_id){//添加浏览记录
			global $link;
			$sql="delete from history where good_id=$good_id and user_id=$user_id";
			@mysql_query($sql, $link);
			$sql="INSERT INTO  history (goods_id ,user_id )
								VALUES ('$goods_id', '$user_id') ";
			if($res = @mysql_query($sql, $link)){
				return true;
			}else{
				echo json_encode(array("status"=>"error", "error_messege" => "Database Error!Cannot add history!"));
				return false;
			}
			
		}
		
		public function addFavourite(){///这个要改，加判断
			global $link;
			
			$goods_id=$_GET['goods_id'];
			$access_token=$_GET['access_token'];
			$user_id= $this->findUserid($access_token);
			$sql="delete from favourite where good_id=$good_id and user_id=$user_id";
			@mysql_query($sql, $link);
			$sql="INSERT INTO  favourite (goods_id ,user_id )
								VALUES ('$goods_id', '$user_id') ";
			if($res = @mysql_query($sql, $link)){
				echo"add ok!";
			}else{
				echo json_encode(array("status"=>"error", "error_messege" => "Database Error!Cannot add favourite!"));
				
			}
			
		}
  	
  	
	
	
	/*------------------------------------------------上传更新功能部分-------------------------------------------------------------*/
	
		public function upLoadGoodsinfo(){
				
			global $link;
			
			$goods_istop  =  $_GET['goods_istop'];
			$class_id  =  $_GET['class_id'];
			$goods_title  = $_GET['goods_title'];
			$goods_info  = $_GET['goods_info'];				
			$goods_price  = $_GET['goods_price'];
          	
          	$access_token=$_GET['access_token'];
			$user_id= $this->findUserid($access_token);
            if($user_id!==false){
					$sql="INSERT INTO goods(class_id, goods_title, goods_info, goods_cretime, goods_price, goods_istop, user_id) 
						VALUES ('$class_id', '$goods_title', '$goods_info',CURRENT_TIMESTAMP, '$goods_price', '$goods_istop', '$user_id')";
				
				if($res = @mysql_query($sql, $link)){
					
					$sql="select goods_id
						from goods
						where goods_title='$goods_title' and goods_info='$goods_info' and goods_price='$goods_price' and user_id='$user_id' and class_id='$class_id' and goods_istop='$goods_istop'								
				    	";
				    if($res = @mysql_query($sql, $link)){
						$row=mysql_fetch_row($res);
						$goods_id=$row[0];
						echo json_encode(array("status"=>"ok", "goods_id" => $goods_id));
					}
	
					$getcredit=new GoodsAction();
					$getcredit->getCreditbyUpload($user_id);
				}else{
					echo json_encode(array("status"=>"error", "error_messege" => "Database Error!"));
				}
          
          }else{
			echo json_encode(array("status"=>"wrong","reason"=>"Illegal access token."));
		  }
		  
		}
								
		
		public function getCreditbyUpload($user_id){
        
        	global $link;
   			$sql="update user set user_credit = user_credit+4 where user_id = '$user_id' " ;
   			if($res = @mysql_query($sql, $link)){
				//echo"\n".'credit ok!!'."\n";
        		return true;
            }else{
            	return false;
            }
  		}//getCreditbyUpload
		
		public function setTop(){ //$user_id){
        
        	global $link;
			
			$access_token=$_GET['access_token'];
			$user_id= $this->findUserid($access_token);
			$goods_id=$_GET['goods_id'];
			
          	
			$permission=$this->checkPermission($goods_id,$user_id);
			if($permission==true)
			{
				$sql="select user.user_credit,goods.goods_istop 
						from user,goods 
						where user.user_id = '$user_id' and goods_id = '$goods_id'";
				if($res = @mysql_query($sql, $link)){
					$row = mysql_fetch_row($res);
					if($row[0]<50){
						echo json_encode(array("status"=>"wrong","reason"=>"Not enough credit!"));
                      //return false;
					}else{
						$sql1="update user set user_credit=user_credit-100 where user_id = '$user_id'";
						$sql2="update goods set goods_istop=1 where goods_id = '$goods_id'";
						if( @mysql_query($sql1, $link) &&  @mysql_query($sql2, $link)){
							echo json_encode(array("status"=>"ok","result"=>"Settop successfully!"));
                          //return true;
						}else{
							echo json_encode(array("status"=>"wrong","reason"=>"Dtabase Error!Cannot settop!"));
                          //return false;
						}
					}	
				}else{
					echo json_encode(array("status"=>"wrong","reason"=>"Dtabase Error!Cannot find!"));
                  //return false;
				}
			}else{
                echo json_encode(array("status"=>"wrong","reason"=>"NO permission out func!"));
              //return false;
			}
			
  		}//cutCreditbySettop
		

		
		public function upDateGoodsinfo(){
        
        	global $link;
			$goods_id=$_GET['goods_id'];
			
		    $access_token=$_GET['access_token'];
			$user_id= $this->findUserid($access_token);
			
			$Cutcreditaction=new GoodsAction();
			$permission=$this->checkPermission($goods_id,$user_id);
			if($permission==true)
			{
					$sql="select goods_title,goods_info,goods_price,class_id,goods_istop from goods where goods_id='$goods_id' ";
					if($res= @mysql_query($sql, $link)){}
					else{
						echo json_encode(array("status"=>"wrong","reason"=>"Dtabase Error!Cannot find!"));
					}
					$res_count=0;
					$response = array();
					
					$upinfo=array();
					$upinfo[0]=$_GET['goods_title'];
					$upinfo[1]=$_GET['goods_info'];
					$upinfo[2]=$_GET['goods_price'];
					$upinfo[3]=$_GET['class_id'];
					$upinfo[4]=$_GET['goods_istop'];
					
					
					$row = mysql_fetch_row($res);
					while($res_count<5) {
          
						if( $upinfo[$res_count]!=null ){//如果是null表示没更新
								$row[$res_count]=$upinfo[$res_count];
						}else{
						}
						$res_count++;
					}//while
					
					if($row[1]=="000"){//如果goods_info是000就是更新了但是没信息,描述可空
						$row[1]=null;
					}

					$sql="update goods 
					      set goods_title='$row[0]' , goods_info='$row[1]' ,  goods_price = '$row[2]' , class_id='$row[3]' , goods_istop='$row[4]' 
						  where goods_id='$goods_id'";
					if(@mysql_query($sql,$link)){
						echo json_encode(array("status"=>"ok","result"=>"Update information of".'$goods_id'." successfully!"));
					}else{
						echo json_encode(array("status"=>"wrong","reason"=>"Dtabase Error!Cannot update!"));
					}
					
			}else{
			}
	
			
  		}//upDategoodsinfo
		
		
		public function checkPermission($goods_id,$user_id){
          	global $link;
			$sql="select user_id from goods where goods_id='$goods_id' " ;
			
   			if($res= @mysql_query($sql, $link)){
              
				$goods_uid= mysql_fetch_row($res);
				if($goods_uid[0]==$user_id){
                    //echo "Okpermited!<br>";
					return true;
				}else {
					echo json_encode(array("status"=>"wrong","reason"=>"No permission!"));
					return false;
				}
			}else{
              	echo json_encode(array("status"=>"wrong","reason"=>"Database error!cannotcheck!"));
				return false;
			}
		}
		
		
		public function shake(){
		
			
		  global $link;
		
          $access_token=$_GET['access_token'];
          $user_id= $this->findUserid($access_token);
          //$user_id=$_GET['id'];
			//$time = $_GET['time'];
			$sql="SELECT MAX( class_num ) 
					FROM (
						SELECT goods.class_id AS class_id, COUNT( class_id ) AS class_num
							FROM goods, favourite
							WHERE favourite.user_id ='$user_id'
							AND favourite.goods_id = goods.goods_id
							GROUP BY goods.class_id )  t
						where class_num=t.class_num
							";
			if($res= @mysql_query($sql, $link)){
          		$row= mysql_fetch_row($res);
            	$max=$row[0];
          
          
				$sql="SELECT class_id
					FROM (
						SELECT goods.class_id AS class_id, COUNT( class_id ) AS class_num
							FROM goods, favourite
							WHERE favourite.user_id ='$user_id'
							AND favourite.goods_id = goods.goods_id
							GROUP BY goods.class_id )  t
					where class_num='$max'
					order by rand()
				";
				if($res= @mysql_query($sql, $link)){
					$row= mysql_fetch_row($res);
					$class_id=$row[0];
                 
				
					$sql="SELECT goods.goods_id, goods.goods_bigimgpath, goods.goods_title, goods.goods_price, goods.goods_cretime, goods.goods_info, class.class_name,
						user.user_name,user.user_tel,user.user_address
						from goods,user,class
						where goods.class_id='$class_id'
						order by rand()" ;
   		
					if($res = @mysql_query($sql, $link)){
    
						$row = mysql_fetch_row($res);
          
						$goods_id = $row[0];
						$goods_bigimgpath = $row[1];
						$goods_title = $row[2];
						$goods_price = $row[3];
						$goods_cretime = $row[4];
						$goods_info = $row[5];
						$class_name = $row[6];
						$user_name = $row[7];
						$user_tel = $row[8];
						$user_address = $row[9];
					
						$sql="SELECT COUNT( * ) 
							FROM favourite, goods
							WHERE goods.goods_id = favourite.goods_id
							AND goods.goods_id ='$goods_id '";
						$res = @mysql_query($sql, $link);
						$row = mysql_fetch_row($res);
						$favourite_num=$row[0];
					}				
				} 

				$response = array("goods_id"=> $goods_id,
									"goods_bigimgpath"=> $goods_bigimgpath,
									"goods_title"=> $goods_title,
									"goods_price"=> $goods_price,
									"favourite_num"=> $favourite_num,
									"goods_cretime"=> $goods_cretime,
									"goods_info"=> $goods_info,
									"class_name"=> $class_name,
									"user_name"=> $user_name,
                                    "user_tel"=>$user_tel,
          							"user_address"=>$user_address);
						
              $addhistory= new GoodsAction();
              $addhistory->addHistory($goods_id,$user_id);//添加商品浏览记录
              
              
              echo json_encode(array("status"=>"ok","response"=>$response)); 
          		
			}
		
			
		}
		
 
		public function deletePost(){
				global $link;
				
				 $access_token=$_GET['access_token'];
         		 $user_id= $this->findUserid($access_token);
			
				$goods_id  = $_GET['goods_id'];
				
				
				$sql="select goods_bigimg, goods_midimg, goods_smallimg
						from goods
						where goods_id='$goods_id";
				if($res=@mysql_query($sql, $link)){
				
					$row= mysql_fetch_row($res);
					$goods_bigimg=$row[0];
					$goods_midimg=$row[1];
					$goods_smallimg=$row[2];					
				}
				
				$sql="delete 
						from goods
						where goods_id='$goods_id'			
				";//删除数据
				
				if($res=@mysql_query($sql, $link) && $row[0]!=null){
						$image=new ImageAction();
						$deleteimg=$image->deletePostImg($goods_bigimg,$goods_midimg,$goods_smallimg);//删除图片
                  		$sql1="delete from favourite where goods_id='$goods_id'";
                  		$sql2="delete from history where goods_id='$goods_id'";
                  		$res=@mysql_query($sql1, $link);
                  		$res=@mysql_query($sql2, $link);//收藏和历史记录表里也删除相应的数据
				}else{
				}		
		
		}
  
   public function sellerinfo(){
  		  global $link;
    	 $user_name=$_GET['user_name'];
    	 $sql="select user_name,user_sex,user_address,user_tel,school_name from user,school
						where user.user_name='$user_name' and user.school_id = school.school_id";
    	 if($res=@mysql_query($sql, $link)){				
					$row= mysql_fetch_row($res);
           			
					$user=array(
                      	"user_name"=>$row[0],
						"user_sex"=>$row[1],
						"user_address"=>$row[2],
         		   		"user_tel"=>$row[3],
						"school_name"=>$row[4]);
           		
		}
     
         $sql="select goods_id,goods_title,goods_price,goods_cretime,goods_smallimgpath 
				from goods,user
				where goods.user_id=user.user_id and user.user_name='$user_name' ";
     
    	 $goodses = array();
         $num=0;
  		 if($res=@mysql_query($sql, $link)){
           
   			  while($row=mysql_fetch_row($res)){
                
      			    $goodes[$num++]=array(
                    	"goods_id"=>$row[0],
                        "goods_title"=>$row[1],
                        "goods_price"=>$row[2],
                        "goods_cretime"=>$row[3],
                        "goods_smallimgpath"=>$row[4]
                    );
   		   	 }
            
 		  }
    	 echo json_encode(array("status"=>"ok","user"=>$user,"goodes"=>$goodes)); 
  }
  public function viewActivityList(){
    	$pagenum=$_GET['page']*10;
  		global $link;
    	$sql="SELECT activity.activity_id, activity.activity_title,activity.activity_cretime,association.association_name,activity.activity_img,
		activity.activity_imgpath,activity.activity_info
		FROM  `activity` , association
		WHERE activity.`association_id` = association.`association_id` 
		LIMIT 0 , 30";   
    	if($res = @mysql_query($sql, $link)){
          	  $res_count=0;
 	  		  $response = array();
   			  while($row=mysql_fetch_row($res)){
                if($row[0]==null &&$row[1]==null && $row[2]==null  && $row[3]==null && $row[4]==null&& $row[5]==null){
					}
                else{    
						$response[$res_count] = array(
                        "activity_id"=>$row[0],
                        "activity_title"=>$row[1],
                        "activity_cretime"=>$row[2],
                        "association_name"=>$row[3],
                        "activity_img"=>$row[4],
                        "activity_imgpath"=>$row[5],
                        "activity_info"=>$row[6]
                        );
						$res_count++;
					}//else
   		   	 }     
            echo json_encode(array("status"=>"ok","activitys"=>$response)); 
        }else{
        	 echo json_encode(array("status"=>"errror")); 
        }
  }
 public function viewActivityInfo(){    
   		$activity_id=$_GET['activity_id'];
 		  global $link;
    	$sql="SELECT activity.activity_id, activity.activity_title,activity.activity_cretime,association.association_name,activity.activity_img,
		activity.activity_imgpath,activity.activity_info
		FROM  `activity` , association
		WHERE activity.`association_id` = association.`association_id` and activity_id='$activity_id'
		LIMIT 0 , 30";
    	 if($res=@mysql_query($sql, $link)){           
   				$row=mysql_fetch_row($res);
                if($row[0]==null &&$row[1]==null && $row[2]==null  && $row[3]==null && $row[4]==null&& $row[5]==null){
					}
                else{    
						$response= array(
                        "activity_id"=>$row[0],
                        "activity_title"=>$row[1],
                        "activity_cretime"=>$row[2],
                        "association_id"=>$row[3],
                        "activity_img"=>$row[4],
                        "activity_imgpath"=>$row[5],
                        "activity_info"=>$row[6]
                        );
					}//else    
          		 echo json_encode(array("status"=>"ok","activitys"=>$response)); 
 		  }
   	  
  }
}

?>