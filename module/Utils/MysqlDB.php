<?php

class MysqlDB {
    const servername = "localhost";
    const username = "root";
    const password = "QWEasd123!@#";
    const dbname = "test";
    private $stmt;
    private $conn;
    private $result;
    var $affected_rows;
    public function __construct(){
        // 创建连接
        $this->conn = new mysqli(self::servername, self::username, self::password, self::dbname);
        
        // 测试连接
        if ($this->conn->connect_error) {
            throw new MyException($this->conn->error, MyException::SERVER_ERROR);
        }
    }
    /**
     * @param string $sql
     * @throws MyException
     * @return mysqli_result|bool false on failure. For successful SELECT, 
     * SHOW, DESCRIBE orEXPLAIN queries mysqli_query will returna mysqli_result 
     * object. For other successful queries mysqli_query willreturn true. 
     */
    public function query($sql){
        $this->result = $this->conn->query($sql);
        $this->affected_rows = $this->conn->affected_rows;
        if(!$this->result){
            throw new MyException($this->conn->error, MyException::SERVER_ERROR);
            return false;
        }
        return $this->result;
    }
    public function prepare($query){
        if($this->stmt){
            $this->stmt->close();
        }
        $this->stmt = $this->conn->prepare($query);
        if(!$this->stmt) {
            throw new MyException($this->conn->error, MyException::SERVER_ERROR);
            return false;
        }
        return true;
    }
    public function bind_param(string $types, ...$var1){
        if(!$this->stmt->bind_param($types, ...$var1)) {
            throw new MyException($this->conn->error, MyException::SERVER_ERROR);
            return false;
        }
        return true;
    }
    /**
     * @return bool true on success or false on failure 
     */
    public function execute(){
        if(!$this->stmt->execute()){
            throw new MyException($this->conn->error, MyException::SERVER_ERROR);
            return false;
        }
        return true;
    }
    public function autocommit($mode){
        return $this->conn->autocommit($mode);
    }
    public function rollback(){
        $this->conn->rollback();
    }
    public function commit(){
        $this->conn->commit();
    }
    public function get_result(){
        return $this->stmt->get_result();
    }
    public function close(){
        if($this->conn){
            $this->conn->close();
        }
        if($this->stmt){
            $this->stmt->close();
        }
    }
}

?>