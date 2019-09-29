<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Utils/geohash.class.php';

class AppServer extends BaseSever {
    const effective_time = 5*60;
    public function __construct(string $logToken = NULL, string $connToken = NULL){
        parent::__construct($logToken, $connToken);
    }
    /**
     * @param string $xml
     * @return string $return_xml
     * {"id":"", "logToken":"", "expiretime":""}
     * @exception MyException
     * @Description
     * {"type":"","timestamp":"","data":{"account":"","pwd":"","sign":"","regid":""}}
     *                                              //sign=MD5(timestamp+account+pwd)
     */
    public function login($data)
    {
        $repire_interval = 7*24*60*60;
        $data = str_replace("\n", "\\n", $data);
        if(!$json = json_decode($data)) throw new MyException("json格式错误", 10017);
        $json = new JsonElement($json);
        
        if(!$type = $json->type) throw new MyException("缺少参数type", 10016);
        if(!$timestamp = $json->timestamp) throw new MyException("缺少参数timestamp", 10016);
        if(!$account = $json->data->account) throw new MyException("缺少参数account", 10016);
        if(!$pwd = $json->data->pwd) throw new MyException("缺少参数pwd", 10016);
        if(!$sign = $json->data->sign) throw new MyException("缺少参数sign", 10016);
        if(!$regid = $json->data->regid) throw new MyException("缺少参数regid", 10016);
        //验证签名和时间戳有效性
        if(!self::check_timestamp($timestamp)){
            throw new MyException("登陆请求超过有效期", 10037);
        }
        if(!self::check_sign($timestamp.$account.$pwd, $sign)){
            throw new MyException("数据校验错", 10012);
        }
        
        $search_str = "";
        switch ($type) {
            case "id":
                $search_str = "USER_ID";
                break;
            case "name":
                $search_str = "NICK_NAME";
                break;
            case "tel":
                $search_str = "TEL";
                break;
            case "email":
                $search_str = "EMAIL";
                break;
            default:
                throw new MyException("type取值错误，请取id/name/tel/email", 10008);
        }
        //数据库操作
        $mysqldb = new MysqlDB();
        try{
            $mysqldb->autocommit(false);
            $mysqldb->prepare("SELECT USER_ID, PASSWORD, SALT FROM USERS_ACCOUNT WHERE $search_str = ?");
            $mysqldb->bind_param("s", $account);
            $mysqldb->execute();
            $result = $mysqldb->get_result();
            
            if($result->num_rows > 0){
                $row = $result->fetch_assoc();
                $salt = $row['SALT'];
                $user_id = $row['USER_ID'];
                if($row['PASSWORD']==md5($pwd.$salt)) {
                    $radom = getRandomStr(8);
                    $log_token = md5($radom.$user_id);
                    $expire_timestamp = time() + $repire_interval;
                    $expire_time = date("Y-m-d H:i:s",$expire_timestamp);
                    $mysqldb->query("REPLACE INTO LOG(USER_ID, LOG_TOKEN, EXPIRE_TIME)
                                     VALUES ('$user_id', '$log_token', '$expire_time')");
                    $mysqldb->query("REPLACE INTO MIPUSH(USER_ID, REGID) VALUES ('$user_id', '$regid')");
                    $this->response = OkJson('{"userId":'.$user_id.', "logToken":"'.$log_token.'", "expiretime":"'.$expire_time.'"}');
                    $mysqldb->commit();
                }
                else {
                    throw new MyException("密码错误", 20001);
                }
            }
            else {
                throw new MyException("用户不存在", 20001);
            }
        } catch(MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    /**
     * @param string $data
     * @return boolean
     * @exception MyException
     * @Description
     * {"timestamp"="", "userId":"", "sign":""}
     *                  //MD5(timestamp+id)
     */
    public function logout($data) {
        $data = str_replace("\n", "\\n", $data);
        if(!$json = json_decode($data)) throw new MyException("json格式错误", 10017);
        $json = new JsonElement($json);
        
        if(!$timestamp = $json->timestamp) throw new MyException("缺少参数timestamp", 10016);
        if(!$user_id = $json->userId) throw new MyException("缺少参数userid", 10016);
        if(!$sign = $json->sign) throw new MyException("缺少参数sign", 10016);
        
        //验证签名和时间戳有效性
        if(!self::check_timestamp($timestamp)){
            throw new MyException("登陆请求超过有效期", 403);
        }
        if(!self::check_sign($timestamp.$user_id, $sign)){
            throw new MyException("数据校验错", 403);
        }
        
        $mysqldb = new MysqlDB();
        try{
            $mysqldb->query("DELETE FROM RSA WHERE USER_ID = '$user_id'");
            if($mysqldb->affected_rows) {
                $this->response = OkJson("");
            } else {
                throw new MyException("重复登出", 20000);
            }
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    /**
     * @Description
     * {"info":"", "key":"", "iv":"", "sign":""}
     * @return
     * {"connToken":{"value":"", "expiretime":""}, "aes":{"key":"", "iv":"", "mode":""}}
     */
    public function connect($data) {
        $repire_interval = 7*24*60*60;
        $data = str_replace("\n", "\\n", $data);
        if(!$json = json_decode($data)) throw new MyException("json格式错误", 10017);
        $json = new JsonElement($json);
        
        if(!$info = $json->info) throw new MyException("缺少参数info", 10016);
        if(!$client_key = $json->key) throw new MyException("缺少参数key", 10016);
        if(!$client_iv = $json->iv) throw new MyException("缺少参数iv", 10016);
        if(!$sign = $json->sign) throw new MyException("缺少参数sign", 10016);
        $userid = $this->user_id;
        
        //检验签名是否正确
        if(!self::check_sign($info.$client_key.$client_iv, $sign)){
            throw new MyException("数据校验错", 403);
        }
        //随机产生之后通信用的密钥
        $session_key = getRandomStr(16);
        $session_iv = getRandomStr(16);
        $salt = getRandomStr(8);
        //按规则随机产生token
        $token = md5($userid.$salt);
        //获取过期时间
        $expire_timestamp = time() + $repire_interval;
        $expire_time = date("Y-m-d H:i:s",$expire_timestamp);
        
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("REPLACE INTO CONNECT(CONN_TOKEN, USER_ID, AES_KEY, AES_IV, EXPIRE_TIME)
                VALUES ('$token', '$userid', '$session_key', '$session_iv', '$expire_time')");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        $this->response = OkJson('{"connToken":{"value":"'.$token.'", "expiretime":"'.$expire_time.'"}, "aes":{"key":"'.$session_key.'", "iv":"'.$session_iv.'", "mode":"aes-cbc-128"}}');
        return aes_encrypt($this->response, $client_key, $client_iv);
    }
    
    public function setLocate($lat, $long) {
        $mysqldb = new MysqlDB();
        try {
            if(!is_numeric($lat)) throw new MyException("latitude参数必须是数字", 10017);
            if(!is_numeric($long)) throw new MyException("longitude参数必须是数字", 10017);
            $pos = strpos($lat, ".");
            $lat = substr($lat, 0, $pos+8);
            $pos = strpos($long, ".");
            $long = substr($long, 0, $pos+8);
            $geohash = (new Geohash())->encode($lat, $long);
            $geohash = substr($geohash, 0, 11);
            $mysqldb->prepare("REPLACE INTO USERS_LOC(USER_ID, LOCATION, GEOHASH, TIME)
                               VALUES (?, ST_GEOMFROMTEXT('POINT($long $lat)'), ?, NOW())");
            $mysqldb->bind_param("ss", $this->user_id, $geohash);
            $mysqldb->execute();
            $this->response = OkJson("");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
}
?>