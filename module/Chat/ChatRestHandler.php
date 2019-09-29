<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Chat/ChatServer.php';
include_once './error_Catch.php';
class ChatRestHandler extends SimpleRest {
    public function __construct($obj) {
        $this->obj = $obj;
    }
    public function execute() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $objid = isset($_GET['objectid']) ? $_GET['objectid'] : "";
            $value = isset($_POST['value']) ? $_POST['value'] : "";
            $logToken = isset($_GET['logToken']) ? $_GET['logToken'] : "";
            $connToken = isset($_GET['connToken']) ? $_GET['connToken'] : "";
            
            if($objid != "") {
                self::checkUserExist($objid);
            }
            switch($this->obj){
                case "user":
                    if($method == "POST") {
                        self::urlAssert(true, $objid);
                        self::assert(array("value" => $value));
                        $this->sendMessage($connToken, $logToken, $objid, $value);
                    } else if($method == "GET") {
                        self::urlAssert(true, $objid);
                        $this->getMessage($connToken, $logToken, $objid);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "messages":
                    if($method == "GET") {
                        self::urlAssert(false, $objid);
                        $this->getMessage($connToken, $logToken, "all");
                    }
                    break;
                default:
                    throw new MyException("请求不被解析", 404);
                    break;
            }
        } catch (MyException $e) {
            throw $e;
        }
    }
    
    private function sendMessage($connToken, $logToken, $objid, $value) {
        self::assert(array("connToken" => $connToken,"logToken" => $logToken));
        $server = new ChatServer($logToken, $connToken);
        $response = $server->send($objid, $server->aes_decrypt($value));
        echo $response;
    }
    private function getMessage($connToken, $logToken, $target) {
        self::assert(array("connToken" => $connToken,"logToken" => $logToken));
        $server = new ChatServer($logToken, $connToken);
        $response = $server->get($target);
        echo $response;
    }
}
?>