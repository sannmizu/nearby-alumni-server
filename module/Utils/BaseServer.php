<?php
include_once 'module/Utils/utils_include.php';

class BaseSever {
    const effective_time = 5*60;
    var $response;
    var $aes_key;
    var $aes_iv;
    var $user_id;
    public function __construct(string $logToken = NULL, string $connToken = NULL) {
        $this->response = "";
        $this->aes_key = "";
        $this->aes_iv = "";
        if($logToken != NULL) {
            self::check_logToken($logToken);
        }
        if($connToken != NULL) {
            self::check_connToken($connToken);
        }
    }
    public function check_logToken($logToken) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->prepare("SELECT USER_ID, EXPIRE_TIME FROM LOG WHERE LOG_TOKEN = ?");
            $mysqldb->bind_param("s", $logToken);
            $mysqldb->execute();
            $result = $mysqldb->get_result();
            if($result->num_rows == 0) {
                throw new MyException("logToken有误", 10017);
            } else {
                $row = $result->fetch_assoc();
                $expire = $row['EXPIRE_TIME'];
                self::check_time($expire);
                $this->user_id = $row['USER_ID'];
            }
        } catch (MyException $e) {
            throw $e;
        }
    }
    public function check_connToken($connToken) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->prepare("SELECT EXPIRE_TIME, AES_KEY, AES_IV FROM CONNECT WHERE CONN_TOKEN = ?");
            $mysqldb->bind_param("s", $connToken);
            $mysqldb->execute();
            $result = $mysqldb->get_result();
            if($result->num_rows == 0) {
                throw new MyException("connToken有误", 10017);
            } else {
                $row = $result->fetch_assoc();
                $expire = $row['EXPIRE_TIME'];
                self::check_time($expire);
                $this->aes_key = $row['AES_KEY'];
                $this->aes_iv = $row['AES_IV'];
            }
        } catch (MyException $e) {
            throw $e;
        }
    }
    public function check_timestamp($timestamp) {
        $now_time = time();
        return ($timestamp > $now_time - self::effective_time && $timestamp < $now_time + self::effective_time);
    }
    public function check_sign($sign_string, $sign) {
        return (md5($sign_string) == $sign);
    }
    public function aes_decrypt($data) {
        if($this->aes_key == "" || $this->aes_iv == "") {
            throw new MyException("key not found", MyException::SERVER_ERROR);
        } else {
            return aes_decrypt($data, $this->aes_key, $this->aes_iv);
        }
    }
    public function aes_encrypt($data) {
        if($this->aes_key == "" || $this->aes_iv == "") {
            throw new MyException("key not found", MyException::SERVER_ERROR);
        } else {
            return aes_encrypt($data, $this->aes_key, $this->aes_iv);
        }
    }
    private function check_time($expire) {
        if(strtotime($expire) < time()) {
            throw new MyException("Token过期", 10037);
        }
        return true;
    }
}
?>