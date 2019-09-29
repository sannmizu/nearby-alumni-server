<?php
include 'module/Utils/utils_include.php';
$mysqldb = new MysqlDB();
try {
    $mysqldb->autocommit(false);
    $mysqldb->query("update users_data set nickname = 133 where user_id= 10020");
    echo $mysqldb->affected_rows;
    $mysqldb->commit();
} catch (MyException $e) {
    $mysqldb->rollback();
} finally {
    $mysqldb->close();
}
?>