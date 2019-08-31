<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Chat/ChatServer.php';

class ChatRestHandler extends SimpleRest {
    public function __construct($obj) {
        $this->obj = $obj;
    }
    public function execute() {
        try {
            $objid = isset($_GET['objectid']) ? $_GET['objectid'] : "";
            $value = isset($_POST['value']) ? $_POST['value'] : "";
            $logToken = isset($_GET['logToken']) ? $_GET['logToken'] : "";
            $connToken = isset($_GET['connToken']) ? $_GET['connToken'] : "";
            
            if($objid != "") {
                self::checkUserExist($objid);
            }
            switch($this->obj){
                case "user":
                    self::assert(array("userid" => $objid,"value" => $value));
                    $this->sendMessage($connToken, $logToken, $objid, $value);
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
}
?>