<?php
class hmacauth {
    private $noisyPrint;
    private $initOK;
    private $dbcon;
    private $table;
    function __construct($mysqldb, $tableName, $noisy=false) {
        $this->noisyPrint = $noisy;
        if(!$this->noisyPrint) ob_start();
        $this->initOK = false;
        if(!$mysqldb) {
            echo "No mysql db available\n";
            return;
        }
        $sql = "select count(*) from `$tableName`";
        $res = @mysql_query($sql, $mysqldb);
        if(!$res) {
            echo "Mysql DB error: ". mysql_error()."\n";
            return;
        }
        else {
            $this->initOK = true;
            $this->dbcon = $mysqldb;
            $this->table = $tableName;
            echo "Init OK.\n";
        }
        if(!$this->noisyPrint) ob_end_clean();
    }
    function __destruct() {

    }

    /*
        return empty string when failed,
        return auth token when succeeded.
    */
    public function step1GetAuthToken($userName) {
        if(!$this->initOK) return '';
        if(!$this->noisyPrint) ob_start();
        $randToken = $this->generateAuthToken();
        $sql = "select `user_name`, `user_passwd_key` from `".$this->table."` where `user_name`='$userName'";
        $res = @mysql_query($sql, $this->dbcon);
        if($res && @mysql_num_rows($res)==1) { // userName exists and is unique
            $sqlu = "update `{$this->table}` set `user_passwd_key`='$randToken' where `user_name`='$userName'";
            $resu = @mysql_query($sqlu, $this->dbcon);
            if($resu) {
                echo "Auth token generated.\n";
            }
            else {
                echo "Get auth token failed.\n";
                $randToken = '获取失败';
            }
        }
        else {
            echo "User name: '$userName' does not exist.\n";
            $randToken = '用户名不存在';
        }
        if(!$this->noisyPrint) ob_end_clean();
        return $randToken;
    }

    /*
        return empty string when failed,
        return access token when succeeded.
    */
    public function step2Auth($userName, $authToken, $authHash, &$errorInfo) {
        if(!$this->initOK) return '';
        $ret = '';
        if(!$this->noisyPrint) ob_start();
        $sql = "select `user_passwd_hash` from {$this->table} where `user_name`='$userName' 
                and  `user_passwd_key`='$authToken'";
        $res = @mysql_query($sql, $this->dbcon);
        if($res && @mysql_num_rows($res)==1) {
            $row = @mysql_fetch_row($res);
            $passwd_hash = $row[0];
            $goodHash = $this->calculateAuthHash($authToken, $passwd_hash);
            echo "Good Hash: ".$goodHash. "\n";
            if($goodHash == $authHash) {
                echo "Auth OK.\n";
                $accToken = $this->generateAccessToken($userName);
                $newAuthToken = $this->generateAuthToken();
                @date_default_timezone_set('Asia/Shanghai');
                $curTime = @time();
                $expTime = $curTime + 3*24*3600; // one week
                $expTimeStr = @date('Y-m-d H:i:s', $expTime);
                // generate & write the Access Hash, expire time
                // *** change authToken to ANOTHER random one
                $sqlu = "update {$this->table} set user_passwd_access_token='$accToken', 
                         user_passwd_key = '$newAuthToken', 
                         user_auth_expire_time = '$expTimeStr', 
                         user_auth_last_time = now()  
                         where 
                         `user_name`='$userName'  
                         and `user_passwd_key` = '$authToken' ";
                $resu = @mysql_query($sqlu, $this->dbcon);
                if($resu) {
                    $ret = $accToken;
                }
            }
            else {
                echo "Auth Failed.\n";
                $errorInfo .= "Error Password.\n";
            }
        }
        else {
            echo "Illegal user name or auth token.\n";
            $errorInfo .= "Illegal user name or auth token.\n";
        }

        if(!$this->noisyPrint) ob_end_clean();
        return $ret;
    }

    public function getAuthUser($accToken) {
        if($accToken=='') return false;
        $sql = "select user_id, user_name from {$this->table} where 
                user_passwd_access_token='$accToken' and 
                user_auth_expire_time>now() and 
                lock_auth = 0 ";
        $res = @mysql_query($sql, $this->dbcon);
        if($res && @mysql_num_rows($res)==1 ) {
            $row = @mysql_fetch_row($res);
            $ret = array();
            $ret['user_id'] = $row[0];
            $ret['user_name'] = $row[1];
            return $ret;
        }
        else {
            return false;
        }
    }
    public function getAuthUserRefreshToken($accToken) {
        if($accToken=='') return false;
        $sql = "select user_id, user_name from {$this->table} where 
                user_passwd_access_token='$accToken' and 
                user_auth_expire_time>now() and 
                lock_auth = 0 ";
        $res = @mysql_query($sql, $this->dbcon);
        if($res && @mysql_num_rows($res)==1 ) {
            $row = @mysql_fetch_row($res);
            $ret = array();
            $ret['user_id'] = $row[0];
            $ret['user_name'] = $row[1];
            $newAccToken = $this->generateAccessToken($row[1]);
            $ret['access_token'] = $newAccToken; 
            @date_default_timezone_set('Asia/Shanghai');
            $curTime = @time();
            $expTime = $curTime + 3*24*3600; // 3 days
            $expTimeStr = @date('Y-m-d H:i:s', $expTime);
            $sqlu = "update {$this->table} set user_passwd_access_token='$newAccToken', 
                    user_auth_expire_time = '$expTimeStr', 
                    user_auth_last_time = now()  
                    where 
                    `user_name`='{$row[1]}'  
                    and `user_passwd_access_token` = '$accToken' ";
            $resu = @mysql_query($sqlu, $this->dbcon);
            if($resu) {
                return $ret;
            }
            else {
                return false;
            }
        }
        else return false;
    }
    
    private function testUnique($userName) {
        $sql = "select `user_name` from `{$this->table}` where `user_name`='$userName' ";
        $res = @mysql_query($sql, $this->dbcon);
        if(!$res) {
            $ret['error_info'] = 'database error.';
            return false;
        }
        else {
            if(@mysql_num_rows($res)>=1) {
                return false;
            }
        }
        return true;
    }

    public function register($userName, $passwordHash) {
        $ret = array();
        $ret['ok'] = 0;
        $ret['error_info'] = '';
        if( !$this->isLegalUserName($userName) ) {
            $ret['error_info'] = '非法的用户名 - Illegal user name: ' . $userName;
            return $ret;
        } 
        if( !$this->testUnique($userName) ) {
            $ret['error_info'] = "用户名已经被占用 - User name already exists: " . $userName;
            return $ret;
        }
        if( !$this->isLegalPasswordHash($passwordHash) ) {
            $ret['error_info'] = '非法的密码 - Illegal password: '.$passwordHash;
            return $ret;
        }


        $sql = "insert into `{$this->table}` (`user_name`, `user_passwd_hash`, `user_regtime`) values ('$userName', '$passwordHash', now())";
        $res = @mysql_query($sql, $this->dbcon);
        if($res) {
            $ret['ok'] = 1;
        }
        else {
            $ret['error_info'] = mysql_error();
        }
        
        return $ret;
    }
    
  
	  public function register2($user_name, $password_hash,$school_id, $user_realname,$user_sno, $user_sex, $user_tel, $user_address) {
        $ret = array();
        $ret['ok'] = 0;
        $ret['error_info'] = '';
        if( !$this->isLegalUserName($user_name) ) {
            $ret['error_info'] = '非法的用户名 - Illegal user name: ' . $user_name;
            return $ret;
        } 
        if( !$this->testUnique($user_name) ) {
            $ret['error_info'] = "用户名已经被占用 - User name already exists: " . $user_name;
            return $ret;
        }
        if( !$this->isLegalPasswordHash($password_hash) ) {
            $ret['error_info'] = '非法的密码 - Illegal password: '.$password_hash;
            return $ret;
        }


        $sql = "insert into `{$this->table}` 
			(`user_name`, `user_passwd_hash`,`school_id`,`user_realname`,`user_sno`,`user_sex`, `user_tel`, `user_address`, `user_regtime`)
			values ('$user_name', '$password_hash','$school_id','$user_realname','$user_sno', '$user_sex',  '$user_tel', '$user_address', now())";
        $res = @mysql_query($sql, $this->dbcon);
        if($res) {
            $ret['ok'] = 1;
        }
        else {
            $ret['error_info'] = mysql_error();
        }
                
        return $ret;
        
    }
    
  
  
  
  
  
    public function logOut($accToken) {
        $ret = array();
        $ret['ok'] = 0;
        $ret['error_info'] = '';
        // do not return the new access token
        if(false === getAuthUserRefreshToken($accToken) ) {
            $ret['error_info'] = "Log out failed.";
        }
        else {
            $ret['ok'] = 1;
        }
        return $ret;
    }
    private function calculateAuthHash($authToken, $passwd_hash) {
        $baseStr = $authToken . "-plus-" . $passwd_hash ;
        $hash = hash('md5', $baseStr);
        //$hash = hash('sha256', $baseStr); // sha256 maybe too complicated for frontend
        return $hash;
    }
    private function generateAuthToken($length = 10) {
        $base = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $len = strlen($base);
        $randToken = '';
        $randTokenLen = $length;
        for($i=0; $i<$randTokenLen; $i++) {
            $x = rand(0, $len-1);
            $randToken .= $base[$x];
        }
        return $randToken;
    }
    private function generateAccessToken($userName, $length = 24) {
        $base = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";
        $len = strlen($base);
        $accToken = '';
        $accTokenLen = $length;
        for($i=0; $i<$accTokenLen; $i++) {
            $x = rand(0, $len-1);
            $accToken .= $base[$x];
        }
        return $this->spec_uuid($userName.$accToken);
    }

    private function spec_uuid($str){
        $md5hash = md5($str);
        $uuidstr = $this->md5_to_uuid($md5hash);
        //$md5hash2 = uuid_to_md5($uuidstr);
        return $uuidstr;
    }

    private function md5_to_uuid($md5str){
        $md5hash = $md5str;
        $uuidstr = "";
        $n = 0;
        $j = 0;
        while($j<=27){
            $c1 = $md5hash[$j];
            $c2 = $md5hash[$j+1];
            $c3 = $md5hash[$j+2];
            $n = (int)(256*$this->getCharNum($c1) + 16*$this->getCharNum($c2) + $this->getCharNum($c3));
            $v1 = (int)($n/64);
            $v2 = $n%64;
            $uuidstr = $uuidstr.$this->getNumChar($v1).$this->getNumChar($v2);
            $j+=3;
        }
        $c1= $md5hash[30];
        $c2 = $md5hash[31];
        $c3 = 0;
        $n =  (int)(256*$this->getCharNum($c1) + 16*$this->getCharNum($c2) + $this->getCharNum($c3));
        $v1 = (int)($n/64);
        $v2 = $n%64;
        $uuidstr = $uuidstr.$this->getNumChar($v1).$this->getNumChar($v2);
        return $uuidstr;
    }

    private function uuid_to_md5($uuidStr){
        $md5str = "";
        $n = 0;
        $j = 0;
        while($j<21){
            $c1 = $uuidStr[$j];
            $c2 = $uuidStr[$j+1];
            $n = (int)(64*$this->getCharNumUuid($c1) + $this->getCharNumUuid($c2));
            $v1 = (int)($n/256);
            $n = $n%256;
            $v2 = (int)($n/16);
            $v3 = $n%16;
            $md5str = $md5str.$this->getNumChar($v1).$this->getNumChar($v2).$this->getNumChar($v3);
            $j+=2;
        }
        return substr($md5str,0,32);
    }

    private function getCharNum($c){
        if($c<='9' && $c>='0') return ord($c)-ord('0');
        if($c<='f' && $c>='a') return 10+ord($c)-ord('a');
        if($c<='F' && $c>='A') return 10+ord($c)-ord('A');
        return 0;
    }

    private function getCharNumUuid($c){
        if($c<='9' && $c>='0') return ord($c)-ord('0');
        if($c<='z' && $c>='a') return 10+ord($c)-ord('a');
        if($c<='Z' && $c>='A') return 36+ord($c)-ord('A');
        if($c == '-') return 62;
        if($c == '_') return 63;
        return 0;
    }

    private function getNumChar($num){
        $sh="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
        return $sh[$num];
    }

    private function isLegalUserName($userName) {
        return true;
    }

    private function isLegalPasswordHash($passwordHash) {
        //use sha256
        $len = strlen($passwordHash);
        if($len!=64) return false;
        if(preg_match('/[^0-9a-fA-F]+/', $passwordHash)) return false;
        return true;
    }

};

?>
