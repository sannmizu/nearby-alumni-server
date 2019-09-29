<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Utils/geohash.class.php';
include_once 'module/autoload.php';

use xmpush\Builder;
use xmpush\Constants;
use xmpush\Sender;

class ChatServer extends BaseSever {
    public function __construct(string $logToken = NULL, string $connToken = NULL){
        parent::__construct($logToken, $connToken);
    }
    
    /**
     * @param int $to_id
     * @param string $message
     * {"content":"", "extra":{"extra.pic1":"png", ..., ...}}发送端
     * {"aim":"chat", "data":{"fromId":xx, "toId":xx, "name":"", "time":"", "content":"", "media":{media}}}
     */
    public function send($to_id, $data) {
        $secret = '17zAp0C8BunRIyZeiKZYmA==';
        $package = 'com.sannmizu.nearby_alumni';
        
        // 常量设置必须在new Sender()方法之前调用
        Constants::setPackage($package);
        Constants::setSecret($secret);
        $data = str_replace("\n", "\\n", $data);
        if(!$json = json_decode($data)) throw new MyException("json格式错误", 10017);
        $medias = isset($json->extra) ? $json->extra : null;
        $content = isset($json->content) ? $json->content : null;
        if(!$medias && !$content) throw new MyException("缺少参数content或者extra", 10016);
        
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->autocommit(false);
            $result = $mysqldb->query("SELECT REGID FROM MIPUSH WHERE USER_ID = '$to_id'");
            if($result->num_rows == 0) {
                $regid = null;
                $alias = $to_id;//TODO:暂定
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
            //暂存媒体文件
            $rootPath = "/api/v1/public/medias/upload/".date("Ym")."/".date("d")."/";
            $mediaJson = saveMedia($medias, $rootPath);
            //TODO:暂存聊天数据
            //$rootPath = "/api/v1/public/chats/".$to_id."/chats/".$this->user_id."/".date("YmdHis")."/";
            $mysqldb->commit();
        } catch (MyException $e) {
            $mysqldb->rollback();
            throw $e;
        } finally {
            $mysqldb->close();
        }
        
        $sender = new Sender();
        $payloadArray = array("aim" => "chat");
        $payloadArray["data"]["fromId"] = (int)$this->user_id;
        $payloadArray["data"]["toId"] = (int)$to_id;
        $payloadArray["data"]["name"] = $name;
        $payloadArray["data"]["time"] = date("Y-m-d H:i:s");
        $payloadArray["data"]["content"] = $content;
        $payloadArray["data"]["media"] = json_decode($mediaJson);
        $payload = json_encode($payloadArray);
        $description = substr($content, 0, 40);
        $message = new Builder();
        $message->title($name);
        $message->description($description);
        $message->passThrough(0);
        $message->notifyId(1);
        $message->payload($payload);
        $message->extra("only_send_once", "1");
        $message->extra("notify_foreground", "0");
        $message->extra(Constants::EXTRA_PARAM_NOTIFY_EFFECT, Constants::NOTIFY_ACTIVITY);
        $message->extra(Constants::EXTRA_PARAM_INTENT_URI, "intent:#Intent;component=com.sannmizu.nearby_alumni/.chat.ChatActivity;i.id=".$this->user_id.";S.name=".$name.";end");
        $message->build();
        
        if($regid != null) {
            if(!$sender->send($message, $regid)->getErrorCode()) {
                $this->response = OkJson('{"state":"'.$say.'"}');
            } else {
                throw new MyException("发送失败", 10001);
            }
        } else {
            if(!$sender->sendToAlias($message, $alias)->getErrorCode()) {
                $this->response = OkJson('{"state":"'.$say.'"}');
            } else {
                throw new MyException("发送失败", 10001);
            }
        }
        return $this->response;
    }
    /**
     *  {"messages":[
     *      {"userId":xx, "messages":[
     *          {"aim":"chat", "data":{"fromId":xx, "toId":xx, "name":"", "time":"", "content":"", "media":{media}}},
     *          {"aim":"chat", "data":{"fromId":xx, "toId":xx, "name":"", "time":"", "content":"", "media":{media}}},
     *          ...
     *      ]},
     *      ...
     *  ]}
     */
    public function get($target) {
        if($target == "all") {
            
        } else {
            
        }
    }
}
?>