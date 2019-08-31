<?php
define("AES_TYPE", "aes-128-cbc");
define("privateKeyFilePath", "D:/eclipse-workspace/index/public/server_rsa_private_key.pem");
define("publicKeyFilePath", "D:/eclipse-workspace/index/public/server_rsa_public_key.pem");
{
    extension_loaded('openssl') or die('php需要openssl扩展支持');
    file_exists(privateKeyFilePath) or die('文件路径不正确');
}
function rsa_encrypt($data){
    $crypto = "";
    foreach (str_split($data, 117) as $chunk) {
        $crypted = "";
        if(!openssl_public_encrypt($chunk, $crypted, file_get_contents(publicKeyFilePath), OPENSSL_PKCS1_PADDING)){
            return false;
        }
        $crypto .= $crypted;
    }
    return base64_encode($crypto);
}
function rsa_decrypt($data){
    $data = base64_decode($data);
    $crypto = "";
    foreach (str_split($data, 128) as $chunk) {
        $crypted = "";
        if(!openssl_private_decrypt($chunk, $crypted, file_get_contents(privateKeyFilePath), OPENSSL_PKCS1_PADDING)){
            return false;
        }
        $crypto .= $crypted;
    }
    return $crypto;
}
function aes_encrypt($data, $key ,$iv, $salt = ""){
    return base64_encode(openssl_encrypt($data.$salt, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv));
}
function aes_decrypt($data, $key, $iv, $salt = ""){
    $data = base64_decode($data);
    $offset = strlen($salt);
    if($offset == 0){
        return openssl_decrypt($data, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);
    } else {
        return substr(openssl_decrypt($data, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv), 0, -$offset);
    }
}
?>