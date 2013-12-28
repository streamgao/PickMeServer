<?php

require_once(DIRNAME(__FILE__) . '/../../config.inc.php');
require_once(DIRNAME(__FILE__) . '/hmacauth.class.php');

class ImageAction {
     
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

  
	 public function upLoadGoodsimg()
	 {
		global $link;	
		global $bucket;
        global $baeImageService;
        global $baiduBCS;
       
		$access_token=$_POST['access_token'];
		$user_id=$this->findUserid($access_token);
		$goods_id=$_POST['goods_id'];

        if($fileUpload = $_POST['imgFile']){
       		echo 'received'."<br>";
        	$object = '/b/'.$access_token.time().'.jpg' ;
       
    	    $imageSrc = base64_decode($fileUpload);
          	$uploadDir = 'uploadimgtemp';
          	@mkdir($uploadDir);
         	$tmpFileName = $uploadDir . '/'.$access_token.time().'.jpg';
          	@file_put_contents($tmpFileName, $imageSrc);

          	$response = $baiduBCS->create_object($bucket, $object, $tmpFileName);
			
          	if(!$response->isOK()){
				die('Create object failed.');
                echo'response error'.'<br>';
		  	}
          //echo 'res1'."<br>";
        
		//得到已存入云存储图片的url
			$url = $baiduBCS->generate_get_object_url($bucket,$object);
			if($url === false){
				die('Generate GET object url failed.');	
			}else {  

                unlink($tmpFileName);
				$goods_bigimgpath=$url;
				$goods_bigimg=$object;
				@mysql_query("update goods set goods_bigimg='$goods_bigimg' ,goods_bigimgpath='$goods_bigimgpath' where goods_id='$goods_id'", $link);
		 	
			
/*--------------------------中图上传--------------------------*/
            		
                  	$object = '/m/'.$access_token. time() . '.jpg';//定义中图的object

                 	$params = array();
					$params[BaeImageConstant::TRANSFORM_ZOOMING] = array(//用大图做放缩，宽度为144.做为中图。
								BaeImageConstant::TRANSFORM_ZOOMING_TYPE_WIDTH, 144);
					//执行操作
					$retVal = $baeImageService->applyTransform($url, $params);
                 
					if($retVal !=false && isset($retVal['response_params']) && isset($retVal['response_params']['image_data'])){
							header("Content-type:image/jpg");
							$imageSrc = base64_decode($retVal['response_params']['image_data']);
							$response = $baiduBCS->create_object_by_content( $bucket, $object, $imageSrc );
                      		$opt = array ();	
							$goods_midimgpath = $baiduBCS->generate_get_object_url ( $bucket, $object, $opt ); 
							$goods_midimg=$object;
                    
                    		 if(@mysql_query("update goods set goods_midimg = '$goods_midimg',goods_midimgpath='$goods_midimgpath'
												where goods_id = '$goods_id'"
												,$link)){}
                      		 else{echo'header error!'."<br>";}
                    
					}else{
						echo 'transform failed, error:' . $baeImageService->errmsg() . "<br>";}
            
				
								
				/*--------------------------小图上传--------------------------*/
            		$url = $goods_bigimgpath;
                  	$object = '/s/'. $access_token. time() . '.jpg';
					
					$baeImageTransform = new BaeImageTransform();
					$baeImageTransform->setZooming (BaeImageConstant::TRANSFORM_ZOOMING_TYPE_WIDTH, 100);
                  //$baeImageTransform->setCropping(0,0,100,100);
					$retVal = $baeImageService->applyTransformByObject($url, $baeImageTransform);	
                 
					if($retVal !=false && isset($retVal['response_params']) && isset($retVal['response_params']['image_data'])){
							header("Content-type:image/jpg");
							$imageSrc = base64_decode($retVal['response_params']['image_data']);
							$response = $baiduBCS->create_object_by_content( $bucket, $object, $imageSrc );
                      		$opt = array ();	
							$goods_smallimgpath = $baiduBCS->generate_get_object_url ( $bucket, $object, $opt );
							$goods_smallimg=$object;
                    
                    		if(@mysql_query("update goods set goods_smallimg = '$goods_smallimg',goods_smallimgpath='$goods_smallimgpath'  
								where goods_id = '$goods_id'", $link)){}
                    
					}else{
						echo 'transform failed, error:' . $baeImageService->errmsg() . "<br>";}
				
        	}				
 
            echo "Upload image successfully!";
		}else{
			echo 'error,cannot receive'. $_FILES ['imgFile'] ['error']."<br>";
		}
			
	  

	}//upLoadGoodsimg

	 
	 public function upDateGoodsimg(){//暂时没用

		 global $link;
         global $bucket;
         global $baeImageService;
         global $baiduBCS;
       
         $goods_id = $_GET['goods_id'];
		 $goods_bigimg = $_GET['goods_bigimg'];
		 
		 
		 $sql="update goods set goods_bigimg = '$goods_bigimg' 
				where goods_id = '$goods_id'";
		 if(@mysql_query($sql, $link)){}
         else{echo'Update bigimg error!'."<br>";}
							 
    	$sql="SELECT goods_id,goods_bigimg,goods_midimg,goods_smallimg 
				from goods where goods_id='$goods_id'";
		if($res = @mysql_query($sql, $link)) {
			$row = mysql_fetch_row($res);
      		$goods_bigimg = $row[1];
      		$goods_midimg = $row[2];
      		$goods_smallimg = $row[3];
        }	
			      
		if($goods_bigimg!=null){
              	/*--------------------------中图放缩更新--------------------------*/
            		$url = $goods_bigimg;//大图的地址
                  	$object = '/m/'. time() . '.jpg';//定义中图的object
       				
                  	$params = array();
					$params[BaeImageConstant::TRANSFORM_ZOOMING] = array(//用大图做放缩，宽度为144.做为中图。
								BaeImageConstant::TRANSFORM_ZOOMING_TYPE_WIDTH, 144);
					//执行操作
					$retVal = $baeImageService->applyTransform($url, $params);
                 
					if($retVal !=false && isset($retVal['response_params']) && isset($retVal['response_params']['image_data'])){
							header("Content-type:image/jpg");
							$imageSrc = base64_decode($retVal['response_params']['image_data']);
							$response = $baiduBCS->create_object_by_content( $bucket, $object, $imageSrc );
                      		$opt = array ();	
							$goods_midimg = $baiduBCS->generate_get_object_url ( $bucket, $object, $opt );
						
                     	//	$info  =$baiduBCS->get_object_info($bucket, $object, $opt);   
                    	//  $header=$info->header;//这个不会，找不到object的大小
						//想找到图片的大小然后存成goods_midimgsize，goods_bigimgsize到数据库里。就不用每次给客户端的时候再找到这个商品读取大小了。
             
							$sql="update goods set goods_midimg = '$goods_midimg' 
									where goods_id = '$goods_id'";
                    		if(@mysql_query($sql, $link)){}
                      		else{echo'Update error!'."<br>";}
                    }
				
				
			
				
				/*--------------------------小图放缩更新--------------------------*/
            		$url = $goods_bigimg;
                  	$object = '/s/'. time() . '.jpg';

                  	
					$baeImageTransform = new BaeImageTransform();
					$baeImageTransform->setZooming (BaeImageConstant::TRANSFORM_ZOOMING_TYPE_WIDTH, 100);
                  //$baeImageTransform->setCropping(0,0,100,100);
					$retVal = $baeImageService->applyTransformByObject($url, $baeImageTransform);
                  
                  /*$params = array();
                  //$params[BaeImageConstant:: TRANSFORM_TRANSCODE]= BaeImageConstant::JPG;
					$params[BaeImageConstant::TRANSFORM_ZOOMING] = array(BaeImageConstant::TRANSFORM_ZOOMING_TYPE_WIDTH, 100);//二维数组缩到100px
                    $params[BaeImageConstant::TRANSFORM_VERTICALFLIP] = 1;
                  //$params[BaeImageConstant:: TRANSFORM_CROPPING]= array(0,0,100,100);
                      //执行操作
                    $retVal = $baeImageService->applyTransform($url, $params);
*/
                 
					if($retVal !==false && isset($retVal['response_params']) && isset($retVal['response_params']['image_data'])){
							header("Content-type:image/jpg");
							$imageSrc = base64_decode($retVal['response_params']['image_data']);
							$response = $baiduBCS->create_object_by_content( $bucket, $object, $imageSrc );
                      		$opt = array ();	
							$goods_smallimg = $baiduBCS->generate_get_object_url ( $bucket, $object, $opt );

							$sql="update goods set goods_smallimg = '$goods_smallimg' where goods_id ='$goods_id'";
                            if(@mysql_query($sql, $link)){}
							else{echo'Update error!'."<br>";}
					}
				


      	}else{
        }
		
      	$response = array("goods_id"=>$goods_id,
                                       "goods_bigimg" => $goods_bigimg,
                                       "goods_midimg" => $goods_midimg,
                                       "goods_smallimg" => $goods_smallimg,
                                        );
          	
		echo json_encode(array("status"=>"ok","response"=>$response));    

	 
	}
  
  
  
	 public function upLoadUserimg(){
			global $link;
			global $bucket;
			global $baeImageService;
			global $baiduBCS;
			
			$access_token=$_POST['access_token'];
			$user_id= $this->findUserid($access_token);
			
			if($fileUpload = $_POST['imgFile']){
				echo 'received'."<br>";
				$object = '/u/'.$access_token.time().'.jpg' ;//u or user?
       
				$imageSrc = base64_decode($fileUpload);
				$uploadDir = 'uploadimgtemp';
				@mkdir($uploadDir);
				$tmpFileName = $uploadDir . '/'.$access_token.time().'.jpg';
				@file_put_contents($tmpFileName, $imageSrc);

				$response = $baiduBCS->create_object($bucket, $object, $tmpFileName);
			
				if(!$response->isOK()){
					die('Create object failed.');
					echo'response error'.'<br>';
				}
				        
				//得到已存入云存储图片的url
				$url = $baiduBCS->generate_get_object_url($bucket,$object);
				if($url === false){
					die('Generate GET object url failed.');	
				}else {  
					unlink($tmpFileName);
					$user_imgpath=$url;
					$user_img=$object;
					$sql="update user set user_img='$user_img', user_imgpath='$user_imgpath'
							where user_id='$user_id'";
					if(@mysql_query($sql, $link)){
						echo 'Save user image successfully!';   
					}else{
						echo json_encode(array("status"=>"error","reason"=>"Database error！Cannot save the image!"));
					}	
			
				}
			}
	 }
  
  
	 public function deletePostImg($goods_bigimg,$goods_midimg,$goods_smallimg){
	 //根据url定位object然后删除
			global $bucket;			
			global $baeImageService;
			global $baiduBCS;
			
       		$opt=array();
       
			/*$goods_bigimg=$_GET['goods_bigimg'];
			$goods_midimg=$_GET['goods_midimg'];
			$goods_smallimg=$_GET['goods_smallimg'];
       */
			$res1=$baiduBCS->delete_object($bucket, $goods_bigimg );
			$res2=$baiduBCS->delete_object($bucket, $goods_midimg);
			$res3=$baiduBCS->delete_object($bucket, $goods_smallimg);
			
			if($res1&&$res2&&$res3){
				return true;
			}else{
				if(!$res1->isOK ()){
					echo'Cannot delete Bigimage!'."<br>";
					return false;
				}
				if(!$res2->isOK ()){
					echo'Cannot delete Midimage!'."<br>";
					return false;
				}
				if(!$res3->isOK ()){
					echo'Cannot delete Smallimage!'."<br>";
					return false;
				}
				
			}
			
			
	 } 





 
 }//upDateGoodsimg