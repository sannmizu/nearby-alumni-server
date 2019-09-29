<?php
include_once 'module/App/AppRestHandler.php';
include_once 'module/Account/AccountRestHandler.php';
include_once 'module/Post/PostRestHandler.php';
include_once 'module/Chat/ChatRestHandler.php';
include_once 'module/Utils/MyException.php';
include_once 'module/Utils/httpResponse.php';

if($_SERVER['REQUEST_METHOD'] == "PUT") {
    parse_str(file_get_contents('php://input'), $_POST);
}
$module = isset($_GET['module']) ? $_GET['module'] : "";
try {
    switch ($module){
        case "app":
            $task = isset($_GET['object']) ? $_GET['object'] : "";
            $userRestHandler = new AppRestHandler($task);
            $userRestHandler->execute();
            break;
        case "account":
            $object = isset($_GET['object']) ? $_GET['object'] : "";
            $userRestHandler = new AccountRestHandler($object);
            $userRestHandler->execute();
            break;
        case "post":
            $object = isset($_GET['object']) ? $_GET['object'] : "";
            $postRestHandler = new PostRestHandler($object);
            $postRestHandler->execute();
            break;
        case "chat":
            $object = isset($_GET['object']) ? $_GET['object'] : "";
            $chatRestHandler = new ChatRestHandler($object);
            $chatRestHandler->execute();
            break;
        default:
            throw new MyException("url错误", 404);
            break;
    }
} catch (Exception $e) {
    $response = setReturn($e);
    echo $response;
}
?>