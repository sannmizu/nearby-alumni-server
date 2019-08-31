<?php
include_once 'module/Utils/utils_include.php';

class UserServer extends BaseSever {
    public function __construct(string $logToken = NULL, string $connToken = NULL){
        parent::__construct($logToken, $connToken);
    }
    
    /**
     * @throws Exception
     * @return string
     * {"friendlist":[
     *      {"userId":"", "info":{"name":"", "sign":"", "icon":""}},
     *      {"userId":"", "info":{"name":"", "sign":"", "icon":""}},
     *      ...
     * ]}
     */
    public function friendList() {
        $mysqldb = new MysqlDB();
        try {
            $result1 = $mysqldb->query("SELECT FRIEND_ID FROM USER_FRIENDS WHERE USER_ID = '$this->user_id'");
            $jsonRoot = array("friendlist" => array());
            while ($row = $result1->fetch_assoc()) {
                $id = $row['FRIEND_ID'];
                $result2 = $mysqldb->query("SELECT NICKNAME, SIGN, ICON FROM USERS_DATA WHERE USER_ID = $id");
                $info = $result2->fetch_assoc();
                $paramArray = array(
                    "userId" => (int)$id,
                    "info" => array(
                        "name" => $info['NICKNAME'],
                        "sign" => $info['SIGN'],
                        "icon" => $info['ICON']
                        )
                );
                $jsonRoot["friendlist"][] = $paramArray;
            }
            $this->response = OkJson(json_encode($jsonRoot));
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    //待改进：需要设置好友申请
    public function friend($friend_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("REPLACE INTO USER_FRIENDS(USER_ID, FRIEND_ID) VALUES ('$this->user_id', '$friend_id')");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return OkJson("");
    }
    
    public function unfriend($friend_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("DELETE FROM USER_FRIENDS WHERE USER_ID = '$this->user_id' AND FRIEND_ID = '$friend_id'");
            if($mysqldb->affected_rows) {
                $this->response = OkJson("");
            } else {
                throw new MyException("不是好友关系", 20000);
            }
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    var $relation2str = array(
        0 => "normal",
        1 => "special",
        "0" => "normal",
        "1" => "special"
    );
    var $str2relation = array(
        "normal" => 0,
        "special" => 1
    );
    /**
     * @throws Exception
     * @return string
     * {"followlist":[
     *      {"relation":"", "user":{"userId":"", "info":{"name":"", "sign":"", "sex":""}}},
     *      {"relation":"", "user":{"userId":"", "info":{"name":"", "sign":"", "sex":""}}},
     *      ...
     * ]}
     */
    public function followList() {
        $mysqldb = new MysqlDB();
        try {
            $result = $mysqldb->query("SELECT FOLLOW_ID, RELATION FROM USER_FOLLOWS WHERE USER_ID = '$this->user_id'");
            $jsonRoot = array("followlist" => array());
            while ($row = $result->fetch_assoc()) {
                $id = $row['FRIEND_ID'];
                $relation = $row['RELATION'];
                $result = $mysqldb->query("SELECT NICKNAME, SIGN, SEX FROM USER_FRIENDS WHERE USER_ID = $id");
                $info = $result->fetch_assoc();
                $paramArray = array(
                    "relation" => $this->relation2str[$relation],
                    "user" => array(
                        "userId" => $id,
                        "info" => array(
                            "name" => $info['NICKNAME'],
                            "sign" => $info['SIGN'],
                            "sex" => $info['SEX'],
                        )
                    )
                );
                $jsonRoot["friendlist"][] = $paramArray;
            }
            $this->response = OkJson(json_encode($jsonRoot));
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    public function follow($follow_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("REPLACE INTO USER_FOLLOWS(USER_ID, FRIEND_ID, RELATION) VALUES ('$this->user_id', '$follow_id', 0)");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return OkJson("");
    }
    
    public function unfollow($follow_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("DELETE FROM USER_FOLLOWS WHERE USER_ID = '$this->user_id' AND FRIEND_ID = '$follow_id'");
            if($mysqldb->affected_rows) {
                $this->response = OkJson("");
            } else {
                throw new MyException("不是关注关系", 20000);
            }
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    public function groupFollow($follow_id, $group) {
        $mysqldb = new MysqlDB();
        $relation = isset($this->str2relation[$group]) ? $this->str2relation[$group] : "";
        if($relation == "") {
            throw new MyException("不存在的关系", 10404);
        }
        try {
            $mysqldb->query("UPDATE USER_FOLLOWS SET RELATION = '$relation'
                            WHERE USER_ID = '$this->user_id' AND FRIEND_ID = '$follow_id'");
            if($mysqldb->affected_rows) {
                $this->response = OkJson("");
            } else {
                throw new MyException("不是关注关系", 20000);
            }
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
}
?>
