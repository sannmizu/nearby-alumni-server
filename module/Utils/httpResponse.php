<?php
const http_httpVersion = "HTTP/1.1";
const http_XmlType = "text/xml";
const http_TextType = "text/plain";
const http_JsonType = "application/json";
$http_ErrorType = array(
    -1 => "未知错误",
    0 => "成功",
    1 => "内部错误",
    10001 => "系统错误",
    10002 => "系统繁忙",
    10008 => "参数错误",
    10012 => "非法请求",
    10013 => "不合法的用户",
    10016 => "缺失必要参数",
    10017 => "参数值非法",
    10037 => "请求过期",
    10051 => "userId过长",
    10052 => "postId过长",
    10053 => "commentId过长",
    10404 => "不合理的参数",
    20000 => "操作失败",
    20001 => "拒绝登录"
);
function setHttpHeaders($contentType, $statusCode){
    
    $statusMessage = getHttpStatusMessage($statusCode);
    
    header(http_httpVersion. " ". $statusCode ." ". $statusMessage);
    header("Content-Type:". $contentType);
}
function getHttpStatusMessage($statusCode){
    $httpStatus = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',   //语义有误，参数有误
        401 => 'Unauthorized',  //需要用户验证
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported');
    return ($httpStatus[$statusCode]) ? $httpStatus[$statusCode] : $httpStatus[500];
}
function getErrorByCode($code) {
    global $http_ErrorType;
    return isset($http_ErrorType[$code]) ? $http_ErrorType[$code] : $http_ErrorType[-1];
}
function setReturn(Exception $e) {
    global $http_ErrorType;
    if($e->getCode() == 404) {
        setHttpHeaders(http_TextType, 404);
        echo $e->getMessage();
        exit();
    }
    $code = isset($http_ErrorType[$e->getCode()]) ? $e->getCode() : -1;
    $msg = $e->getMessage();
    if($code == -1 || $code == 1) {
        write_log($e->getFile()." ".$e->getLine().":".$e->getMessage()."\n".$e->getTraceAsString());
        $msg = "内部错误";
    }
    setHttpHeaders(http_JsonType, 200);
    return '{"result":"error","reason":"'.$msg.'", "description":"'.getErrorByCode($code).'", "code":'.$code.'}';
}
?>