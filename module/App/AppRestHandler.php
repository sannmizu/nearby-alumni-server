<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/App/AppServer.php';

class AppRestHandler extends SimpleRest {
    private $task;
    /**
     * 构造器
     * @param string $task 不同操作
     * @return
     * */
    public function __construct($task) {
        $this->task = $task;
    }
    public function execute() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $value = isset($_POST['value']) ? $_POST['value'] : "";
            $logToken = isset($_GET['logToken']) ? $_GET['logToken'] : "";
            $lat = isset($_GET['latitude']) ? $_GET['latitude'] : "";
            $long = isset($_GET['longitude']) ? $_GET['longitude'] : "";
            switch($this->task){
                case "login":
                    if($method == "POST") {
                        $this->getLogin($value);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "logout":
                    if($method == "GET") {
                        $this->getLogout($value);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "connect":
                    if($method == "POST") {
                        $this->getConnect($value, $logToken);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "locate":
                    if($method == "PUT") {
                        $this->setLocate($logToken, $lat, $long);
                    } else {
                        throw new MyException("请求不被解析", 404);
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
    private function getLogin($data) {
        self::assert(array("value" => $data));
        $server = new AppServer();
        $response = $server->login(rsa_decrypt($data));
        echo $response;
    }
    private function getLogout($data) {
        self::assert(array("value" => $data));
        $server = new AppServer();
        $response = $server->logout($data);
        echo $response;
    }
    private function getConnect($data, $logToken) {
        self::assert(array("value" => $data, "logToken" => $logToken));
        $server = new AppServer($logToken);
        $response = $server->connect(rsa_decrypt($data));
        echo $response;
    }
    private function setLocate($logToken, $lat, $long) {
        self::assert(array("logToken" => $logToken,"latitude" => $lat,"longitude" => $long));
        $server = new AppServer($logToken);
        $response = $server->setLocate($lat, $long);
        echo $response;
    }
}

?>