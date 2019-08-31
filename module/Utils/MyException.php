<?php
include_once 'utils.php';
class MyException extends Exception{
    const CLIENT_ERROR = 2;
    const SERVER_ERROR = 1;
}
?>