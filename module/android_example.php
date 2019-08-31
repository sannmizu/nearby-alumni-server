<?php

use xmpush\Builder;
use xmpush\HttpBase;
use xmpush\Sender;
use xmpush\Constants;
use xmpush\Stats;
use xmpush\Tracer;
use xmpush\Feedback;
use xmpush\DevTools;
use xmpush\Subscription;
use xmpush\TargetedMessage;
use xmpush\Region;

include_once(dirname(__FILE__) . '/autoload.php');

$secret = '17zAp0C8BunRIyZeiKZYmA==';
$package = 'com.sannmizu.nearby_alumni';

// 常量设置必须在new Sender()方法之前调用
Constants::setPackage($package);
Constants::setSecret($secret);

// $sender->setRegion(Region::China);// 支持海外

// message1 演示自定义的点击行为
$sender = new Sender();
$payloadArray = array("aim" => "chat");
$payloadArray["data"]["fromId"] = 10008;
$payloadArray["data"]["toId"] = 10007;
$payloadArray["data"]["name"] = "123";
$payloadArray["data"]["time"] = date("Y-m-d H:i:s");
$payloadArray["data"]["content"] = "测试信息";
$payloadArray["data"]["media"] = json_decode("{}");
$payload = json_encode($payloadArray);
$description = substr("测试信息", 0, 40);
$message = new Builder();
$message->title("123");
$message->description($description);
$message->passThrough(0);
$message->notifyId(1);
$message->payload($payload);
$message->build();
print_r($sender->send($message, "aP5vOm3L8f6/38Of5ZZ13rA6XlGTrATkROXbIZTqF9t7JTPDH1XCSzzp51ag3QJz"));
//$targetMessage = new TargetedMessage();
//$targetMessage->setTarget('alias1', TargetedMessage::TARGET_TYPE_ALIAS); // 设置发送目标。可通过regID,alias和topic三种方式发送
//$targetMessage->setMessage($message1);

// message2 演示预定义点击行为中的点击直接打开app行为
/*$message2 = new Builder();
$message2->title($title);
$message2->description($desc);
$message2->passThrough(0);
$message2->payload($payload); // 对于预定义点击行为，payload会通过点击进入的界面的intent中的extra字段获取，而不会调用到onReceiveMessage方法。
$message2->extra(Builder::notifyEffect, 1); // 此处设置预定义点击行为，1为打开app
$message2->extra(Builder::notifyForeground, 1);
$message2->notifyId(0);
$message2->build();
$targetMessage2 = new TargetedMessage();
$targetMessage2->setTarget('alias2', TargetedMessage::TARGET_TYPE_ALIAS);
$targetMessage2->setMessage($message2);

$targetMessageList = array($targetMessage, $targetMessage2);
//print_r($sender->multiSend($targetMessageList,TargetedMessage::TARGET_TYPE_ALIAS)->getRaw());

print_r($sender->sendToAliases($message1, $aliasList)->getRaw());*/
//$stats = new Stats();
//$startDate = '20140301';
//$endDate = '20140312';
//print_r($stats->getStats($startDate,$endDate)->getData());
//$tracer = new Tracer();
//print_r($tracer->getMessageStatusById('t1000270409640393266xW')->getRaw());

?>
