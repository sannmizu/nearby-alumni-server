<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Utils/utils_include.php';
include_once 'module/Utils/geohash.class.php';
include_once 'module/autoload.php';

use xmpush\Builder;
use xmpush\Constants;
use xmpush\Sender;

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
            $result1 = $mysqldb->query("SELECT U1.USER_ID FROM USER_FRIENDS U1
                                        WHERE U1.FRIEND_ID = $this->user_id AND U1.USER_ID IN
                                        (SELECT U2.FRIEND_ID FROM USER_FRIENDS U2
                                        WHERE U2.USER_ID = $this->user_id)");
            $jsonRoot = array("friendlist" => array());
            while ($row = $result1->fetch_assoc()) {
                $id = $row['USER_ID'];
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
    /**
     * @throws Exception
     * @return string
     * {"requestlist":[
     *      {"userId":"", "info":{"name":"", "sign":"", "icon":""}},
     *      {"userId":"", "info":{"name":"", "sign":"", "icon":""}},
     *      ...
     * ]}
     */
    public function requests() {
        $mysqldb = new MysqlDB();
        try {
            $result1 = $mysqldb->query("SELECT U1.USER_ID FROM USER_FRIENDS U1
                                        WHERE U1.FRIEND_ID = $this->user_id AND U1.USER_ID NOT IN
                                        (SELECT U2.FRIEND_ID FROM USER_FRIENDS U2
                                        WHERE U2.USER_ID = $this->user_id)");
            $jsonRoot = array("requestlist" => array());
            while ($row = $result1->fetch_assoc()) {
                $id = $row['USER_ID'];
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
                $jsonRoot["requestlist"][] = $paramArray;
            }
            $this->response = OkJson(json_encode($jsonRoot));
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    //请求：{"aim":"friend", "data":{"fromId":xx, "toId":xx, "name"=""}}
    public function friends($friend_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->autocommit(false);
            $result = $mysqldb->query("SELECT REGID FROM MIPUSH WHERE USER_ID = '$to_id'");
            if($result->num_rows == 0) {
                $regid = null;
                $alias = $friend_id;//TODO:暂定
                $say = "用户长时间没上线，消息可能收不到";
            } else {
                $row = $result->fetch_assoc();
                $regid = $row['REGID'];
                $result = $mysqldb->query("SELECT EXPIRE_TIME FROM CONNECT WHERE USER_ID = '$to_id'");
                if($result->num_rows == 0) {
                    $say = "用户长时间没上线，消息可能收不到";
                } else {
                    $row = $result->fetch_assoc();
                    $expire_time =  $row['EXPIRE_TIME'];
                    if(strtotime($expire_time) < time()) {
                        $say = "用户长时间没上线，消息可能收不到";
                    } else {
                        $say = "发送成功";
                    }
                }
            }
            $result = $mysqldb->query("SELECT NICKNAME FROM USERS_DATA WHERE USER_ID = '$this->user_id'");
            $row = $result->fetch_assoc();
            $name = $row['NICKNAME'];
            $mysqldb->query("REPLACE INTO USER_FRIENDS(USER_ID, FRIEND_ID) VALUES ('$this->user_id', '$friend_id')");
            
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        //发送请求
        $sender = new Sender();
        $payloadArray = array("aim" => "friend");
        $payloadArray['data']['fromId'] = (int)$this->user_id;
        $payloadArray['data']['toId'] = (int)$friend_id;
        $payloadArray["data"]["name"] = $name;
        $payload = json_encode($payloadArray);
      //  $description = $name."请求加你为好友";
        $message = new Builder();
      //  $message->title("好友请求");
      //  $message->description($description);
        $message->passThrough(1);
        $message->notifyId(2);
        $message->payload($payload);
        $message->extra("only_send_once", "1");
      //  $message->extra("notify_foreground", "0");
      //  $message->extra(Constants::EXTRA_PARAM_NOTIFY_EFFECT, Constants::NOTIFY_ACTIVITY);
      //  $message->extra(Constants::EXTRA_PARAM_INTENT_URI, "intent:#Intent;component=com.sannmizu.nearby_alumni/.chat.ChatActivity;i.id=".$this->user_id.";S.name=".$name.";end");
        $message->build();
        if($regid != null) {
            if(!$sender->send($message, $regid)->getErrorCode()) {
                $this->response = OkJson("");
            } else {
                throw new MyException("发送失败", 10001);
            }
        } else {
            if(!$sender->sendToAlias($message, $alias)->getErrorCode()) {
                $this->response = OkJson("");
            } else {
                throw new MyException("发送失败", 10001);
            }
        }
        return $this->response;
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
            $mysqldb->query("SELECT * FROM USER_FOLLOWS
                            WHERE USER_ID = '$this->user_id' AND FRIEND_ID = '$follow_id'");
            if($mysqldb->affected_rows == 0) {
                throw new MyException("不是关注关系", 20000);
            }
            $mysqldb->query("UPDATE USER_FOLLOWS SET RELATION = '$relation'
                            WHERE USER_ID = '$this->user_id' AND FRIEND_ID = '$follow_id'");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
}
?>
