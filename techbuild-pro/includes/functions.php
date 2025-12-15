<?php
// includes/functions.php


// 初始化购物车
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 添加商品到购物车
function cart_add($product_id, $quantity = 1) {
    $product_id = (int)$product_id;
    $quantity = max(1, (int)$quantity);

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

// 更新商品数量
function cart_update($product_id, $quantity) {
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

// 获取购物车内容（带产品信息）
// 获取购物车内容（带产品信息）
function cart_get_items($pdo) {
    if (empty($_SESSION['cart'])) return [];

    $ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, price, type FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 重新索引数组，让id作为键
        $product_map = [];
        foreach ($products as $product) {
            $product_map[$product['id']] = $product;
        }

        $items = [];
        foreach ($_SESSION['cart'] as $id => $qty) {
            if (isset($product_map[$id])) {
                $product = $product_map[$id];
                $items[] = [
                    'id' => $id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'type' => $product['type'],
                    'quantity' => $qty,
                    'total' => $product['price'] * $qty
                ];
            }
        }
        return $items;
        
    } catch (Exception $e) {
        // 错误处理
        error_log("Cart error: " . $e->getMessage());
        return [];
    }
}

// 清空购物车
function cart_clear() {
    $_SESSION['cart'] = [];
}

// 检查是否为管理员
function require_admin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /techbuild-pro/auth/login.php");
        exit;
    }
    
    // 从数据库查 role（更安全）
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    
    if ($role !== 'admin') {
        header("Location: /techbuild-pro/");
        exit;
    }
}

// 获取统计摘要
function get_dashboard_stats($pdo) {
    $stats = [
        'order_count' => 0,
        'total_revenue' => 0,
        'user_count' => 0,
        'pending_repairs' => 0
    ];

    try {
        // 总订单数 & 销售额 - 修复数组索引访问
        $stmt = $pdo->query("SELECT COUNT(*) as order_count, COALESCE(SUM(total), 0) as total_revenue FROM orders");
        $order_data = $stmt->fetch();
        
        if ($order_data) {
            $stats['order_count'] = $order_data['order_count'] ?? 0;
            $stats['total_revenue'] = $order_data['total_revenue'] ?? 0;
        }

        // 用户数
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
        $user_data = $stmt->fetch();
        $stats['user_count'] = $user_data['user_count'] ?? 0;

        // 待处理维修
        $stmt = $pdo->query("SELECT COUNT(*) as pending_repairs FROM repair_bookings WHERE status = 'scheduled'");
        $repair_data = $stmt->fetch();
        $stats['pending_repairs'] = $repair_data['pending_repairs'] ?? 0;

    } catch (Exception $e) {
        // 记录错误但不中断页面显示
        error_log("Dashboard stats error: " . $e->getMessage());
    }

    return $stats;
}


function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


// 自动创建维修工单
function createRepairTicket($booking_id, $service_item, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO repair_tickets 
            (repair_booking_id, title, description, priority, status, created_by) 
            VALUES (?, ?, ?, 'medium', 'new', 1)
        ");
        
        $title = "Repair Service: " . $service_item['name'];
        $description = "Service booked by customer. Device details to be confirmed.";
        
        $stmt->execute([$booking_id, $title, $description]);
        
        error_log("✅ Auto-created repair ticket for booking ID: " . $booking_id);
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        error_log("❌ Failed to create repair ticket: " . $e->getMessage());
        return false;
    }
}






/**
 * 将日期时间转换为相对时间格式（论坛）
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * 检查当前用户是否为WordPress订阅者登录
 * 基于会话中的 login_source 标记
 */
function is_subscriber() {
    // 检查会话中是否有订阅者标记
    return isset($_SESSION['login_source']) && $_SESSION['login_source'] === 'subscriber';
}

/**
 * 获取订阅者信息
 * 从会话中获取订阅者的基本信息
 */
function get_subscriber_info() {
    if (!is_subscriber()) {
        return null;
    }
    
    // 从会话中提取订阅者信息
    $subscriber_id = $_SESSION['user_id'] ?? 0;
    
    // 如果订阅者ID有偏移量（在 subscribe_login.php 中添加了 100000），移除偏移量
    if ($subscriber_id > 100000) {
        $subscriber_id = $subscriber_id - 100000;
    }
    
    return [
        'id' => $subscriber_id,
        'name' => $_SESSION['user_name'] ?? 'Subscriber',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => 'customer' // 订阅者登录后视为普通客户
    ];
}

/**
 * 检查用户是否已登录（包括普通用户和订阅者）
 * 更全面的登录检查函数
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * 获取当前登录用户的姓名
 */
function get_current_user_name() {
    if (isset($_SESSION['user_name'])) {
        return $_SESSION['user_name'];
    }
    
    if (is_subscriber()) {
        $subscriber = get_subscriber_info();
        return $subscriber['name'] ?? 'Guest';
    }
    
    return 'Guest';
}

/**
 * 获取当前登录用户的角色
 */
function get_current_user_role() {
    if (isset($_SESSION['user_role'])) {
        return $_SESSION['user_role'];
    }
    
    if (is_subscriber()) {
        return 'customer'; // 订阅者视为普通客户
    }
    
    return 'guest';
}


?>