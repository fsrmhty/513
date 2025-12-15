<?php
// config/database.php

// 数据库配置
$host = 'sql308.infinityfree.com';
$dbname = 'if0_37528983_513week7';
$username = 'if0_37528983';
$password = 'cH97l2BhUUqrMGF';

try {
    // 创建 PDO 连接
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // 开发时显示详细错误
    die("Database connection failed: " . $e->getMessage());
}
?>