<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Utils/geohash.class.php';

class AccountServer extends BaseSever {
    public function __construct(string $logToken = NULL, string $connToken = NULL){
        parent::__construct($logToken, $connToken);
    }
    
    /**
     * @param string $json
     * @return
     * @Description
     * json
     * {"type":"tel","timestamp":"123456789","data":{"account":"**","nickname":"qwe","pwd":"123456","sign":"md5"}}
     *                                                              //sign=MD5($timestamp.$account.$nickname.$pwd)
     */
    public function register(string $data) {
        if(!$json = json_decode($data)) throw new MyException("json格式错误", 10017);
        
        if(!$type = $json->type) throw new MyException("缺少参数type", 10016);
        if(!$timestamp = $json->timestamp) throw new MyException("缺少参数timestamp", 10016);
        if(!$account = $json->data->account) throw new MyException("缺少参数account", 10016);
        if(!$nickname = $json->data->nickname) throw new MyException("缺少参数nickname", 10016);
        if(!$pwd = $json->data->pwd) throw new MyException("缺少参数pwd", 10016);
        if(!$sign = $json->data->sign) throw new MyException("缺少参数sign", 10016);
        
        if(strlen($nickname) > 20) throw new MyException("nickname长度应小于等于20", 10017);
        if(strlen($pwd) > 20) throw new MyException("password长度应小于等于20", 10017);
        //验证签名和时间戳有效性
        if(!self::check_timestamp($timestamp)){
            throw new MyException("注册请求超过有效期", 10037);
        }
        if(!self::check_sign($timestamp.$account.$nickname.$pwd, $sign)){
            throw new MyException("数据校验错", 10012);
        }
        
        $search_str = "";
        $other_str = "";
        switch ($type) {
            case "tel":
                if(strlen($account) > 11) throw new MyException("tel长度应小于等于11", 10017);
                $search_str = "TEL";
                $other_str = "EMAIL";
                break;
            case "email":
                if(strlen($account) > 30) throw new MyException("email长度应小于等于30", 10017);
                $search_str = "EMAIL";
                $other_str = "TEL";
                break;
            default:
                throw new MyException("type取值错误，请取tel/email", 10008);
                break;
        }
        //数据库操作
        $mysqldb = new MysqlDB();
        try{
            $mysqldb->autocommit(false);
            $mysqldb->prepare("SELECT * FROM USERS_ACCOUNT WHERE $search_str = ?");
            $mysqldb->bind_param("s", $account);
            $mysqldb->execute();
            if($mysqldb->get_result()->num_rows != 0) {
                throw new MyException("账户以注册", 20000);
            }
            $mysqldb->prepare("INSERT INTO USERS_ROOT(USER_ID, REGIST_TIME, $search_str, $other_str)
                        VALUES (NULL, NOW(), ?, NULL)");
            $mysqldb->bind_param("s", $account);
            $mysqldb->execute();
            //获取id
            $sql = "SELECT LAST_INSERT_ID()";
            $result = $mysqldb->query($sql);
            if($result->num_rows == 0){
                throw new MyException("未能获取ID", MyException::SERVER_ERROR);
            }
            $row = $result->fetch_assoc();
            $user_id = $row["LAST_INSERT_ID()"];
            //数据库插入账号信息
            $mysqldb->prepare("INSERT INTO USERS_ACCOUNT(USER_ID, PASSWORD, SALT, $search_str, $other_str)
                    VALUES (?, ?, ?, ?, NULL)");
            $salt = getRandomStr(5).time().getRandomStr(5);
            $mysqldb->bind_param("ssss", $user_id, md5($pwd.$salt), $salt, $account);
            $mysqldb->execute();
            //插入个性化信息
            $mysqldb->prepare("INSERT INTO USERS_DATA(USER_ID, NICKNAME)
                    VALUES (?, ?)");
            $mysqldb->bind_param("ss", $user_id, $nickname);
            $mysqldb->execute();
            //提交
            $mysqldb->commit();
            $this->response = OkJson('{"userId":'.$user_id.'}');
        } catch (MyException $e) {
            $mysqldb->rollback();
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    /**
     * @param string $objid
     * @param string $target
     * @return 
     * {"id":"","info":{"(target)":"", ...}}
     */
    public function getInfo(string $objid,string $target) {
        $mysqldb = new MysqlDB();
        try {
            if($target != "all") {
                $selected = checkUserInfo($target);
            } else {
                $selected = "*";
            }
            $result = $mysqldb->query("SELECT $selected FROM USERS_DATA WHERE USER_ID = '$objid'");
            if($result->num_rows == 0) {
                throw new MyException("用户不存在", 10404);
            }
            $row = $result->fetch_assoc();
            if($target == "all") {
                $jsonArray = array("userId" => $objid);
                foreach ($row as $k => $v) {
                    if($k != "user_id" && $k != "USER_ID"){
                        $jsonArray['info'][sql2standard($k)] = $v;
                    }
                }
                $this->response = OkJson(json_encode($jsonArray));
            } else {
                $jsonArray = array(
                    "userId" => (int)$objid,
                    "info" => array($target => $row[$selected])
                );
                $this->response = OkJson(json_encode($jsonArray));
            }
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    /**
     * @param string $search_type
     * @param string $search_string
     * {"latitude":"","longitude":""}
     * @param int $page
     * @throws MyException
     * @return
     * {"users":[{"user":{"userId":""}, "distance":""},{"user":{"userId":""}},...]}
     */
    public function search($search_type, $search_string, $page) {
        switch($search_type) {
            case "tel":
                if(utf8_strlen($search_string) > 11) throw MyException("tel对应参数standard长度应该小于等于11", 10017);
                $mysqldb = new MysqlDB();
                try {
                    $mysqldb->prepare("SELECT USER_ID FROM USERS_ACCOUNT WHERE TEL = ?");
                    $mysqldb->bind_param("s", $search_string);
                    $mysqldb->execute();
                    $result = $mysqldb->get_result();
                    $jsonRoot = array("users" => array());
                    while($row = $result->fetch_assoc()) {
                        $paramArray = array(
                            "user" => array(
                                "userId" => $row['USER_ID']
                            )
                        );
                        $jsonRoot["users"][] = $paramArray;
                    }
                    $this->response = OkJson(json_encode($jsonRoot));
                } catch (MyException $e) {
                    throw $e;
                } finally {
                    $mysqldb->close();
                }
                break;
            case "name":
                if(utf8_strlen($search_string) > 20) throw MyException("tel对应参数standard长度应该小于等于20", 10017);
                $mysqldb = new MysqlDB();
                try {
                    $mysqldb->prepare("SELECT USER_ID FROM USERS_DATA WHERE NICKNAME = ? ORDER BY USER_ID LIMIT ?, 10");
                    $mysqldb->bind_param("si", $search_string, ((int)$page - 1) * 10);
                    $mysqldb->execute();
                    $result = $mysqldb->get_result();
                    $jsonRoot = array("users" => array());
                    while($row = $result->fetch_assoc()) {
                        $paramArray = array(
                            "user" => array(
                                "userId" => $row['USER_ID']
                                )
                        );
                        $jsonRoot["users"][] = $paramArray;
                    }
                    $this->response = OkJson(json_encode($jsonRoot));
                } catch (MyException $e) {
                    throw $e;
                } finally {
                    $mysqldb->close();
                }
                break;
            case "surround":
                if(!$json = json_decode($search_string)) throw new MyException("json格式错误", 10017);
                if(!$longitude = $json->longitude) throw new MyException("缺少参数longitude", 10016);
                if(!$latitude = $json->latitude) throw new MyException("缺少参数latitude", 10016);
                
                $geohashclass = new Geohash();
                $geohash = $geohashclass->encode($latitude, $longitude);
                $geohashArray = $geohashclass->expand($geohash);
                $geohash = substr($geohash, 0, 4);
                $upleft = substr($geohashArray['upleft'], 0, 4);
                $up = substr($geohashArray['up'], 0, 4);
                $upright = substr($geohashArray['upright'], 0, 4);
                $right = substr($geohashArray['right'], 0, 4);
                $downright = substr($geohashArray['downright'], 0, 4);
                $down = substr($geohashArray['down'], 0, 4);
                $downleft = substr($geohashArray['downleft'], 0, 4);
                $left = substr($geohashArray['left'], 0, 4);
                
                $time = time() - 24*60*60;
                $mysqldb = new MysqlDB();
                try {
                    $mysqldb->prepare("SELECT USER_ID, LOCATION, TIME, ST_DISTANCE_SPHERE(ST_GEOMFROMTEXT('POINT($longitude $latitude)'), LOCATION) AS DISTANCE
                                       FROM USERS_LOC
                                       WHERE UNIX_TIMESTAMP(TIME) > $time AND 
                                       GEOHASH LIKE '$upleft%' OR GEOHASH LIKE '$up%' OR GEOHASH LIKE '$upright%' OR
                                       GEOHASH LIKE '$left%' OR GEOHASH LIKE '$geohash%' OR GEOHASH LIKE '$right%' OR
                                       GEOHASH LIKE '$downleft%' OR GEOHASH LIKE '$down%' OR GEOHASH LIKE '$downright%'
                                       HAVING DISTANCE < 5000
                                       ORDER BY DISTANCE
                                       LIMIT ? , 10");
                    $mysqldb->bind_param("i", ($page - 1) * 10);
                    $mysqldb->execute();
                    $result = $mysqldb->get_result();
                    $jsonRoot = array("users" => array());
                    while($row = $result->fetch_assoc()) {
                        $paramArray = array(
                            "user" => array(
                                "userId" => $row['USER_ID'],
                            ),
                            "distance" => (double)$row['DISTANCE']
                        );
                        $jsonRoot["users"][] = $paramArray;
                    }
                    $this->response = OkJson(json_encode($jsonRoot));
                } catch (MyException $e) {
                    throw $e;
                } finally {
                    $mysqldb->close();
                }
                break;
        }
        return $this->response;
    }
    /**
     * @param string $target
     * @param string $data
     * all: {"info":{"(target)":"","(target)":"",...}}
     */
    public function editData($data) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->autocommit(false);
            if(!$json = json_decode($data, true)) throw new MyException("json格式错误", 10017);
            foreach($json['info'] as $k => $v) {
                $selected = checkUserInfo($k);
                $mysqldb->prepare("UPDATE USERS_DATA SET $selected = ? WHERE USER_ID = '$this->user_id'");
                $mysqldb->bind_param("s", $v);
                $mysqldb->execute();
            }
            $mysqldb->commit();
            if($mysqldb->affected_rows != 0) {
                $this->response = OkJson("");
            } else {
                throw new MyException("未找到用户", MyException::SERVER_ERROR);
            }
        } catch (MyException $e) {
            $mysqldb->rollback();
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
}
?>