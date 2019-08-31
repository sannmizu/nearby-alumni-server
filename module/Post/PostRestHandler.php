<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Post/PostServer.php';

class PostRestHandler extends SimpleRest{
    public function __construct($obj) {
        $this->obj = $obj;
    }
    public function execute() {
        try{
            $objid = isset($_GET['objectid']) ? $_GET['objectid'] : "";
            $value = isset($_POST['value']) ? $_POST['value'] : "";
            $logToken = isset($_GET['logToken']) ? $_GET['logToken'] : "";
            $connToken = isset($_GET['connToken']) ? $_GET['connToken'] : "";
            $target = isset($_GET['target']) ? $_GET['target'] : "";
            $limit = isset($_GET['limit']) ? $_GET['limit'] : "";
            $type = isset($_GET['type']) ? $_GET['type'] : "";
            $line = isset($_GET['line']) ? $_GET['line'] : "";
            $method = $_SERVER['REQUEST_METHOD'];
            switch ($this->obj) {
                case "user":
                    if($objid != "") {
                        self::checkUserExist($objid);
                    }
                    if($method == "GET" && $target == "all"){
                        self::urlAssert(true, $objid);
                        $this->getAllPost($objid);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "blog":
                    if($objid != "") {
                        self::checkBlogExist($objid);
                    }
                    switch($method){
                        case "GET":
                            self::urlAssert(true, $objid);
                            $this->getPost($objid, $target);
                            break;
                        case "POST":
                            if($objid == ""){
                                self::assert(array("value" => $value));
                                $this->sendPost($connToken, $logToken, $value);
                            } else {
                                switch($target) {
                                    case "likes":
                                        self::urlAssert(true, $objid);
                                        $this->likes($objid, $this->obj, "+ 1");
                                        break;
                                    case "star":
                                        self::urlAssert(true, $objid);
                                        $this->star($logToken, $objid);
                                        break;
                                    default:
                                        throw new MyException("请求不被解析", 404);
                                        break;
                                }
                            }
                            break;
                        case "DELETE":
                            switch ($target){
                                case "":
                                    self::urlAssert(true, $objid);
                                    $this->deletePost($logToken, $objid);
                                    break;
                                case "likes":
                                    self::urlAssert(true, $objid);
                                    $this->likes($objid, $this->obj, "- 1");
                                    break;
                                case "star":
                                    self::urlAssert(true, $objid);
                                    $this->unstar($logToken, $objid);
                                    break;
                                default:
                                    throw new MyException("请求不被解析", 404);
                                    break;
                            }
                            break;
                    }
                    break;
                case "comment":
                    if($objid != "") {
                        self::checkCommentExist($objid);
                    }
                    switch($method){
                        case "GET":
                            if($objid == "") {
                                $this->getSelfComments($logToken);
                            } else {
                                if($target != "" && $target != "post") {
                                    throw new MyException("请求不被解析", 404);
                                }
                                $this->getComments($objid, $limit, $target == "post" ? true : false);
                            }
                            break;
                        case "POST":
                            switch($target) {
                                case "":
                                    self::urlAssert(true, $objid);
                                    self::assert(array("value" => $value));
                                    $this->sendComment($logToken, $objid, $value);
                                    break;
                                case "likes":
                                    self::urlAssert(true, $objid);
                                    $this->likes($objid, $this->obj, "+ 1");
                                    break;
                                default:
                                    throw new MyException("请求不被解析", 404);
                                    break;
                            }
                            break;
                        case "DELETE":
                            switch($target) {
                                case "":
                                    self::urlAssert(true, $objid);
                                    $this->deleteComment($logToken, $objid);
                                    break;
                                case "likes":
                                    self::urlAssert(true, $objid);
                                    $this->likes($objid, $this->obj, "- 1");
                                    break;
                                default:
                                    throw new MyException("请求不被解析", 404);
                                    break;
                            }
                            break;
                    }
                    break;
                case "stars":
                    if($method == "GET") {
                        self::urlAssert(false, $objid);
                        $this->getSelfStars($logToken);
                    } else {
                        throw new MyException("请求不被解析", 404);
                    }
                    break;
                case "":
                    self::assert(array("type" => $type,"line" => $line,"limit" => $limit));
                    $this->push($logToken, $type, $line, $limit);
                    break;
                default:
                    throw new MyException("请求不被解析", 404);
                    break;
            }
        } catch (MyException $e) {
            throw $e;
        }
    }
    
    private function getPost($postid, $target) {
        $server = new PostServer();
        $response = $server->getPost($postid, $target);
        echo $response;
    }
    private function getAllPost($user_id) {
        $server = new PostServer();
        $response = $server->getAllPost($user_id);
        echo $response;
    }
    private function sendPost($connToken, $logToken, $data) {
        self::assert(array("connToken" => $connToken,"logToken" => $logToken));
        $server = new PostServer($logToken, $connToken);
        $response = $server->sendPost($server->aes_decrypt($data));
        echo $response;
    }
    private function deletePost($logToken, $id) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->delelePost($id);
        echo $response;
    }
    private function likes($targetid, $target, $i) {
        $server = new PostServer();
        $response = $server->like($target, $targetid, $i);
        echo $response;
    }
    private function star($logToken, $post_id) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->star($post_id);
        echo $response;
    }
    private function unstar($logToken, $post_id) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->unstar($post_id);
        echo $response;
    }
    private function sendComment($logToken, $repost_id, $value) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->sendComment($repost_id, $value);
        echo $response;
    }
    private function deleteComment($logToken, $comment_id) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->deleteComment($comment_id);
        echo $response;
    }
    private function getSelfComments($logToken) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->getSelfComments();
        echo $response;
    }
    private function getComments($comment_id, $limit, bool $isPost = false) {
        $server = new PostServer();
        $response = $server->getComments($comment_id, $limit, $isPost);
        echo $response;
    }
    private function getSelfStars($logToken) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->getSelfStars();
        echo $response;
    }
    private function push($logToken, $type, $line, $limit) {
        self::assert(array("logToken" => $logToken));
        $server = new PostServer($logToken);
        $response = $server->pushPosts($type, $line, $limit);
        echo $response;
    }
}
?>