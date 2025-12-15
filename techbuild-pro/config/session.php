<?php
// config/session.php
ini_set('session.cookie_lifetime', 86400); // 24小时
ini_set('session.gc_maxlifetime', 86400); // 24小时
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/techbuild-pro/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => false, // 如果是HTTPS改为true
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
?>