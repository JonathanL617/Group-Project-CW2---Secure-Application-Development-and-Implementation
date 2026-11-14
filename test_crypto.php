<?php
require_once 'app/config/SecurityHelper.php';

$original_data = "0123456789"; // 假设这是用户的手机号
$encrypted = SecurityHelper::encrypt($original_data);
$decrypted = SecurityHelper::decrypt($encrypted);

echo "原始数据: " . $original_data . "<br>";
echo "加密后的密文 (可以直接存数据库): " . $encrypted . "<br>";
echo "解密后的数据: " . $decrypted . "<br>";
echo "脱敏展示 (给用户看): " . SecurityHelper::maskData($original_data);
?>