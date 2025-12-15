<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$cart_items = cart_get_items($pdo);
if (empty($cart_items)) {
    header("Location: /techbuild-pro/cart/");
    exit;
}

$total = array_sum(array_column($cart_items, 'total'));

// 模拟下单（无真实支付）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 插入订单
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'confirmed')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $order_id = $pdo->lastInsertId();

        // 插入订单项
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        // 在事务提交前，修复维修服务处理
        foreach ($cart_items as $item) {
        if ($item['type'] === 'repair_service') {
        $stmt = $pdo->prepare("
            INSERT INTO repair_bookings (order_item_id, device_type, symptoms, status, priority)
            VALUES (?, 'To be specified', 'Awaiting customer details', 'scheduled', 'medium')
        ");
        
        // 获取订单项ID
        $stmt2 = $pdo->prepare("SELECT id FROM order_items WHERE order_id = ? AND product_id = ?");
        $stmt2->execute([$order_id, $item['id']]);
        $order_item_id = $stmt2->fetchColumn();
        
        if ($order_item_id) {
            $stmt->execute([$order_item_id]);
            $booking_id = $pdo->lastInsertId();
            
            // 自动创建工单
            $ticket_stmt = $pdo->prepare("
                INSERT INTO repair_tickets 
                (repair_booking_id, title, description, priority, status, created_by) 
                VALUES (?, ?, ?, 'medium', 'new', ?)
            ");
            
            $ticket_title = "New Repair: " . $item['name'];
            $ticket_description = "Customer: " . $_SESSION['user_name'] . " | Service: " . $item['name'];
            
            $ticket_stmt->execute([
                $booking_id, 
                $ticket_title, 
                $ticket_description,
                $_SESSION['user_id']
            ]);
            
            error_log("🔄 Created repair ticket #" . $pdo->lastInsertId() . " for booking #" . $booking_id);
        }
    }
}

        $pdo->commit();
        cart_clear(); // 清空购物车

        $_SESSION['success'] = "Order #$order_id placed successfully! (Simulated)";
        header("Location: /techbuild-pro/user/orders.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Order failed. Please try again.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Checkout</h1>
    <p class="page-subtitle">Complete your purchase</p>
</div>

<div class="admin-container">
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h3 style="color: var(--primary); margin-bottom: 1rem;">Order Summary</h3>
            <p style="font-size: 1.5rem; font-weight: bold; color: var(--success);">
                Total: $<?= number_format($total, 2) ?>
            </p>
        </div>

        <div class="alert alert-warning" style="margin-bottom: 2rem;">
            ⚠️ This is a simulated checkout. No real payment will be processed.
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="text-align: center;">
                <button type="submit" class="btn btn-primary btn-lg">Confirm Order (Simulate Payment)</button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="/techbuild-pro/cart/" class="btn btn-outline">← Back to Cart</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>