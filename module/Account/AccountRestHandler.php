<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Account/AccountSever.php';
include_once 'module/Account/UserServer.php';

class AccountRestHandler extends SimpleRest {
    public function __construct($obj) {
        $this->obj = $obj;
    }
    public function execute() {
        try {
            $objid = isset($_GET['objectid']) ? $_GET['objectid'] : "";
            $value = isset($_POST['value']) ? $_POST['value'] : "";
            $logToken = isset($_GET['logToken']) ? $_GET['logToken'] : "";
            $connToken = isset($_GET['connToken']) ? $_GET['connToken'] : "";
            $target = isset($_GET['target']) ? $_GET['target'] : "";
            $relation = isset($_GET['group']) ? $_GET['group'] : "";
            $type = isset($_GET['type']) ? $_GET['type'] : "";
            $standard = isset($_GET['standard']) ? $_GET['standard'] : "";
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 1;
            $newInfo = isset($_POST['newInfo']) ? $_POST['newInfo'] : "";
            $method = $_SERVER['REQUEST_METHOD'];
            if($objid != "") {
                self::checkUserExist($objid);
            }
            switch($this->obj) {
                case "new":
                    if($method == "POST") {
                        self::assert(array("value" => $value));
                        $this->newAccount($value);
                    } else if($method == "PUT") {
                        self::assert(array("value" => $value));
                        $this->updateAccount($value);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "friends":
                    switch($method) {
                        case "GET":
                            self::urlAssert(false, $objid);
                            $this->getFriendsList($logToken);
                            break;
                        case "POST":
                            self::urlAssert(true, $objid);
                            $this->addFriend($logToken, $objid);
                            break;
                        case "DELETE":
                            self::urlAssert(true, $objid);
                            $this->deleteFriend($logToken, $objid);
                            break;
                        default:
                            throw new MyException("请求不被解析", 404);
                            break;
                    }
                    break;
                case "requests":
                    if($method == "GET") {
                        self::urlAssert(false, $objid);
                        $this->getFriendRequest($logToken);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "follows":
                    switch($method) {
                        case "GET":
                            self::urlAssert(false, $objid);
                            $this->getFollowsList($logToken);
                            break;
                        case "POST":
                            self::urlAssert(true, $objid);
                            $this->addFollow($logToken, $objid);
                            break;
                        case "DELETE":
                            self::urlAssert(true, $objid);
                            $this->deleteFollow($logToken, $objid);
                            break;
                        case "PUT":
                            self::urlAssert(true, $objid);
                            self::assert(array("relation" => $relation));
                            $this->changeFollow($logToken, $objid, $relation);
                            break;
                        default:
                            throw new MyException("请求不被解析", 404);
                            break;
                    }
                    break;
                case "user":
                    switch($method) {
                        case "GET":
                            if($objid != "") {
                                self::urlAssert(true, $target);
                                $this->getUserInfo($objid, $target);
                            } else {
                                if($target == "search") {
                                    self::assert(array("type" => $type,"standard" => $standard));
                                    $this->searchUser($type, $standard, $limit);
                                } else {
                                    throw new MyException("请求不被解析", 404);
                                }
                            }
                            break;
                        default:
                            throw new MyException("请求不被解析", 404);
                            break;
                    }
                    break;
                case "info":
                    switch($method) {
                        case "PUT":
                            $this->edit($connToken, $logToken, $newInfo);
                            break;
                        default:
                            throw new MyException("请求不被解析", 404);
                            break;
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
    private function newAccount($data) {
        $server = new AccountServer();
        $response = $server->register(rsa_decrypt($data));
        echo $response;
    }
    private function updateAccount($data) {
        $server = new AccountServer();
        $response = $server->updatePassword(rsa_decrypt($data));
        echo $response;
    }
    private function getFriendsList($logToken) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->friendList();
        echo $response;
    }
    private function getFriendRequest($logToken) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->requests();
        echo $response;
    }
    private function addFriend($logToken, $objid) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->friend($objid);
        echo $response;
    }
    private function deleteFriend($logToken, $objid) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->unfriend($objid);
        echo $response;
    }
    private function getFollowsList($logToken) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->followList();
        echo $response;
    }
    private function addFollow($logToken, $objid) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->follow($objid);
        echo $response;
    }
    private function deleteFollow($logToken, $objid) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->unfollow($objid);
        echo $response;
    }
    private function changeFollow($logToken, $objid, $relation) {
        self::assert(array("logToken" => $logToken));
        $server = new UserServer($logToken);
        $response = $server->groupFollow($objid, $relation);
        echo $response;
    }
    private function getUserInfo($objid, $target) {
        $server = new AccountServer();
        $response = $server->getInfo($objid, $target);
        echo $response;
    }
    private function searchUser($searchType, $value, $limit) {
        $server = new AccountServer();
        $response = $server->search($searchType, $value, $limit);
        echo $response;
    }
    private function edit($connToken, $logToken, $newData) {
        self::assert(array("connToken" => $connToken,"logToken" => $logToken,"newInfo" => $newData));
        $server = new AccountServer($logToken, $connToken);
        $response = $server->editData($server->aes_decrypt($newData));
        echo $response;
    }
} 

?>