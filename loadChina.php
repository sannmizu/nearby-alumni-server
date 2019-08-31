<?php
include_once 'module/Utils/utils_include.php';
$file = fopen("./public/China2019_5.json", "r");
$str = fread($file, filesize("./public/China2019_5.json"));
$json = json_decode($str);
$mysqldb = new MysqlDB();
try {
    $mysqldb->autocommit(false);
    foreach ($json as $province) {
        $mysqldb->query("INSERT INTO AREA(AREA_ID, PID, NAME) VALUES ('$province->code', NULL, '$province->name')");
        //    echo $province->code.":".$province->name."\n";
        $cityList = $province->cityList;
        foreach ($cityList as $city) {
            if($city->code == "110000" || $city->code == "120000" || $city->code == "310000" || $city->code == "500000") {
                
            } else {
                $mysqldb->query("INSERT INTO AREA(AREA_ID, PID, NAME) VALUES ('$city->code', '$province->code', '$city->name')");
            }
            //        echo $city->code.":".$city->name."\n";
            $areaList = $city->areaList;
            foreach ($areaList as $area) {
                $mysqldb->query("INSERT INTO AREA(AREA_ID, PID, NAME) VALUES ('$area->code', '$city->code', '$area->name')");
                //            echo $area->code.":".$area->name."\n";
            }
        }
    }
    $mysqldb->commit();
} catch (Exception $e) {
    $mysqldb->rollback();
    echo $e->getMessage();
}
$mysqldb->close();
echo "OK";
?>