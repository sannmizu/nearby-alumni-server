<?php
include_once 'utils.php';
class MyException extends Exception{
    const CLIENT_ERROR = 200;
    const SERVER_ERROR = 100;
}
?>