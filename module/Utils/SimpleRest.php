<?php
/*
 * 一个简单的 RESTful web services 基类
 * 我们可以基于这个类来扩展需求
 */
include_once 'module/Utils/MyException.php';
include_once 'module/Utils/utils.php';
class SimpleRest {
    var $obj;
    
    public function __construct() {
        
    }
    public function urlAssert(bool $isFill, ...$array) {
        foreach ($array as $str) {
            if(($str != "") != $isFill) {
                throw new MyException("请求不被解析", 404);
            }
        }
    }
    public function assert($array) {
        foreach ($array as $name => $str) {
            if($str == "") {
                throw new MyException("缺少参数".$name, 10016);
            }
        }
    }
    
    public function checkUserExist($id) {
        if(utf8_strlen($id) > 10) throw new MyException("userId长度应小于等于10", 10051);
        $mysqldb = new MysqlDB();
        try {
            $result = $mysqldb->query("SELECT * FROM USERS_ROOT WHERE USER_ID = '$id'");
            if($result->num_rows == 0) {
                throw new MyException("用户不存在", 10017);
            }
        } catch(MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
    }
    public function checkBlogExist($id) {
        if(utf8_strlen($id) > 10) throw new MyException("postId长度应小于等于10", 10052);
        $mysqldb = new MysqlDB();
        try {
            $result = $mysqldb->query("SELECT * FROM POSTS WHERE POST_ID = '$id'");
            if($result->num_rows == 0) {
                throw new MyException("动态不存在", 10017);
            }
        } catch(MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
    }
    public function checkCommentExist($id) {
        if(utf8_strlen($id) > 10) throw new MyException("commentId长度应小于等于10", 10053);
        $mysqldb = new MysqlDB();
        try {
            $result = $mysqldb->query("SELECT * FROM COMMENTS WHERE COMMENT_ID = '$id'");
            if($result->num_rows == 0) {
                throw new MyException("回复不存在", 10017);
            }
        } catch(MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
    }
}
?>