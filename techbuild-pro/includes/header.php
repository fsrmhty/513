<?php
// 在 header.php 的最开头添加
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 一次性获取用户角色
$user_role = null;
if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_role = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Header role check error: " . $e->getMessage());
        // 避免显示错误给用户
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechBuild Pro - Your PC Building & Repair Experts</title>
    <meta name="description" content="TechBuild Pro offers custom PC building, computer components, and reliable repair services. Build your dream PC today!">
    <meta name="keywords" content="PC building, computer repair, custom PC, gaming PC, computer components">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/techbuild-pro/assets/css/style.css">
    
    <!-- 不要在这里加载所有JS库，只在需要的地方加载 -->
    <script src="/techbuild-pro/assets/js/main.js" defer></script>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <!-- 修正logo链接 -->
            <a href="/techbuild-pro/" class="logo">TechBuild Pro</a>
            
            
            
            <nav class="nav" id="main-nav">
                <!-- 公共链接 -->
                
                
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($user_role === 'admin'): ?>
                        <!-- 管理员专用导航 -->
                        <a href="/techbuild-pro/admin/" class="nav-link">Dashboard</a>
                        <a href="/techbuild-pro/admin/products.php" class="nav-link">Products</a>
                        <a href="/techbuild-pro/admin/orders.php" class="nav-link">Orders</a>
                        <a href="/techbuild-pro/admin/users.php" class="nav-link">Users</a>
                        <a href="/techbuild-pro/admin/ticket_management.php" class="nav-link">Tickets</a>
                  			<a href="https://hhh.free.nf/WP/list/" class="nav-link" target="_blank">List</a>
                        
                    <?php elseif ($user_role === 'technician'): ?>
                        <!-- 技术员专用导航 -->
                        <a href="/techbuild-pro/technican/" class="nav-link">Dashboard</a>
                        <a href="/techbuild-pro/technican/repairs.php" class="nav-link">Repairs</a>
                        <a href="/techbuild-pro/user/profile.php" class="nav-link">Profile</a>
                        
                    <?php else: ?>
                        <!-- 客户导航 -->
                        <a href="/techbuild-pro/products/" class="nav-link">Products</a>
                        <a href="/techbuild-pro/cart/" class="nav-link" id="cart-link">
                            Cart 
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="cart-badge"><?= array_sum($_SESSION['cart']) ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/techbuild-pro/user/profile.php" class="nav-link">Account</a>
                        <a href="/techbuild-pro/user/orders.php" class="nav-link">Orders</a>
                			 <a href="/techbuild-pro/discussion.php" class="nav-link">Forum</a>
                			 <a href="/techbuild-pro/contact.php" class="nav-link">Contact</a>
                `			 <a href="http://hhh.free.nf/WP/recruitment/" class="nav-link" target="_blank">Recruitment</a>
                    <?php endif; ?>
                    
                    <!-- 所有登录用户都有的链接 -->
                	  
                    <a href="/techbuild-pro/auth/logout.php" class="nav-link">Logout</a>
                	  
                    
                <?php else: ?>
                    <!-- 未登录用户 -->
                	  <a href="/techbuild-pro/about.php" class="nav-link">About</a>
                    <a href="/techbuild-pro/products/" class="nav-link">Products</a>
                    <a href="/techbuild-pro/auth/login.php" class="nav-link">Admin Login</a>
                    <a href="/techbuild-pro/auth/subscribe_login.php" class="nav-link">Subscriber Login</a>
                    <a href="https://hhh.free.nf/WP/email/" class="nav-link" target="_blank">Subscribe</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main">
        <!-- 消息显示区域 -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>