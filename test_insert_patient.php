<?php
// 引入数据库配置和你的加密类
require_once 'app/config/conn.php';
require_once 'app/config/SecurityHelper.php';

// 模拟前端传来的数据
$patient_id = 'P999'; // 随便造一个ID
$patient_name = 'Test Crypto Patient';
$patient_dob = '1990-01-01';
$patient_phone = '0123456789'; // 会被加密
$patient_gender = 'Male';
$patient_email = 'test@test.com';
$patient_address = '123 Secret Street'; // 会被加密
$registered_by = 'A001'; // 随便填一个存在的管理员ID

// 加密数据
$encrypted_phone = SecurityHelper::encrypt($patient_phone);
$encrypted_address = SecurityHelper::encrypt($patient_address);

$stmt = $conn->prepare("INSERT INTO patient (patient_id, patient_name, patient_date_of_birth, patient_phone, patient_gender, patient_email, patient_address, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param('ssssssss', 
    $patient_id, 
    $patient_name, 
    $patient_dob, 
    $encrypted_phone, // 密文
    $patient_gender, 
    $patient_email, 
    $encrypted_address, // 密文
    $registered_by
);

if ($stmt->execute()) {
    echo "<h1>Success! 数据已加密插入。</h1>";
    echo "<p>明文手机号: " . $patient_phone . "</p>";
    echo "<p>存入数据库的密文: " . $encrypted_phone . "</p>";
    echo "<p>快去 phpMyAdmin 的 patient 表看看！</p>";
} else {
    echo "Error: " . $stmt->error;
}
?>