<?php
include_once 'module/Utils/MyException.php';
/**
 * 获得随机字符串
 * @param int $len      需要的长度
 * @param bool $special 是否需要特殊符号
 * @return string       返回随机字符串
 */
function getRandomStr($len, $special=true){
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );
    
    if($special){
        $chars = array_merge($chars, array(
            "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
            "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
            "}", "<", ">", "~", "+", "=", ",", "."
        ));
    }
    
    $charsLen = count($chars) - 1;
    shuffle($chars);                            //打乱数组顺序
    $str = '';
    for($i=0; $i<$len; $i++){
        $str .= $chars[mt_rand(0, $charsLen)];    //随机取出一位
    }
    return $str;
}

/**
 * [write_log 写入日志]
 * @param  string $data [写入的数据]
 * @return
 */
function write_log($data){
    //设置目录时间
    $years = date('Y-m');
    //设置路径目录信息
    $url = './public/log/txlog/'.$years.'/'.date('Ymd').'_request_log.txt';
    //取出目录路径中目录(不包括后面的文件)
    $dir_name=dirname($url);
    //目录不存在就创建
    if(!file_exists($dir_name))
    {
        //iconv防止中文名乱码
        $res = mkdir(iconv("UTF-8", "GBK", $dir_name),0777,true);
    }
    $fp = fopen($url,"a");//打开文件资源通道 不存在则自动创建
    fwrite($fp,date("Y-m-d H:i:s").var_export($data,true)."\r\n");//写入文件
    fclose($fp);//关闭资源通道
}

function OkJson($data) {
    if($data=="") $data="{}";
    return '{"result":"ok", "description":"成功", "data":'.$data.', "code":0}';
}

function checkUserInfo($key, $data = null) {
    $userInfo = array(
        "userId" => "USER_ID",
        "name" => "NICKNAME",
        "sign" => "SIGN",
        "sex" => "SEX",
        "icon" => "ICON",
        "areaId" => "AREA_ID",
        "age" => "AGE",
        "constellation" => "CONSTELLATION",
        "career" => "CAREER"
    );
    $lengthLimit = array(
        "userId" => 10,
        "name" => 20,
        "sign" => 30,
        "sex" => 2,
        "icon" => 0,
        "areaId" => 6,
        "age" => 3,
        "constellation" => 4,
        "career" => 20
    );
    if(isset($userInfo[$key])) {
        $queryCh = $userInfo[$key];
        if($data != null) {
            if($queryCh == "ICON") {
                return $queryCh;
            } else {
                if(utf8_strlen($data) <= $lengthLimit[$key]) {
                    return $queryCh;
                } else {
                    throw new MyException("参数".$key."长度应该小于等于".$lengthLimit[$key], 10017);
                }
            }
        } else {
            return $queryCh;
        }
    } else {
        throw new MyException("不存在参数".$key, 10008);
    }
}

function get($obj) {
    return isset($obj) ? $obj : false;
}

function sql2standard($queryCh) {
    $queryCh = strtoupper($queryCh);
    $array = array(
        "USER_ID" => "userId",
        "NICKNAME" => "name",
        "ICON" => "icon",
        "SIGN" => "sign",
        "SEX" => "sex",
        "AREA_ID" => "areaId",
        "CAREER" => "career",
        "AGE" => "age",
        "CONSTELLATION" => "constellation"
        
    );
    return $array[$queryCh];
}
// 计算中文字符串长度
function utf8_strlen($string = null) {
    $match = null;
    // 将字符串分解为单元
    preg_match_all("/./us", $string, $match);
    // 返回单元个数
    return count($match[0]);
}

/**
 * @param SimpleXMLElement $media_type
 * @return string json {"root":"", "files":["1.png","2.txt","",...]}
 */
function saveMedia($medias, string $rootPath) {
    if($medias == null) {
        return "{}";
    }
    $i = 1;
    $target_path = $rootPath;
    $resultArray = array("root" => $target_path);
    $jsonArray = array();
    foreach ($medias as $key => $value) {
        $mediaType = $value;
        $mediaKey =  preg_replace('/[. ]/', '_', $key);;
        $mediaContent = isset($_POST[$mediaKey]) ? base64_decode($_POST[$mediaKey]) : "";
        if($mediaContent == "") {
            throw new MyException("post参数".$key."未定义", 10016);
        }
        //存入数据
        $filename = $i.".".$mediaType;
        if(!file_exists($target_path.$filename)) {
            mkdir($target_path, 0777, true);
        }
        $file = fopen($target_path.$filename, "wb");
        fwrite($file,$mediaContent);
        fclose($file);
        array_push($jsonArray, $filename);
        $resultArray["files"][] = $filename;
        $i++;
    }
    //写入描述文件descript
    $file = fopen($target_path."descript.json", "w");
    fwrite($file, json_encode($jsonArray));
    fclose($file);
    return json_encode($resultArray);
}
?>