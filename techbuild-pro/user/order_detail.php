<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

require_once '../config/database.php';

// 处理取消请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = (int)$_POST['order_id'];
    $reason = trim($_POST['cancel_reason']);
    
    // 验证订单属于当前用户且状态可取消
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if ($order && in_array($order['status'], ['pending', 'confirmed'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', cancel_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $order_id]);
        $_SESSION['success'] = "Order #$order_id has been cancelled.";
        header("Location: order_detail.php?id=" . $order_id);
        exit;
    }
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Order not found.");
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// 获取订单详情
$stmt = $pdo->prepare("
    SELECT o.*, u.name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

// 获取订单商品
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.type 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Order #<?= $order['id'] ?></h1>
    <p class="page-subtitle">Order Details</p>
</div>

<div class="admin-container">
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h2>Order Information</h2>
        <p><strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></p>
        <p><strong>Status:</strong> 
            <span style="padding: 0.25rem 0.75rem; border-radius: var(--radius); background: 
                <?= $order['status'] === 'completed' ? 'var(--success-light)' : 
                  ($order['status'] === 'cancelled' ? 'var(--error-light)' : 'var(--warning-light)') ?>; 
                color: <?= $order['status'] === 'completed' ? 'var(--success)' : 
                       ($order['status'] === 'cancelled' ? 'var(--error)' : 'var(--warning)') ?>;">
                <?= ucfirst($order['status']) ?>
            </span>
        </p>
        <p><strong>Total:</strong> $<?= number_format($order['total'], 2) ?></p>

        <!-- 取消订单按钮 -->
        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
            <button onclick="showCancelForm(<?= $order['id'] ?>)" class="btn btn-danger">Cancel Order</button>
            
            <div id="cancelForm" style="display:none; margin-top: 1rem; padding: 1rem; background: var(--gray-50); border-radius: var(--radius);">
                <h3>Cancel Order</h3>
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="form-group">
                        <label class="form-label" for="cancel_reason">Reason for cancellation:</label>
                        <textarea name="cancel_reason" id="cancel_reason" class="form-control" required></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="submit" name="cancel_order" class="btn btn-danger">Confirm Cancel</button>
                        <button type="button" onclick="hideCancelForm()" class="btn btn-outline">Cancel</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <h3>Order Items</h3>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Grand Total:</td>
                        <td style="font-weight: bold;">$<?= number_format($order['total'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <a href="/techbuild-pro/user/orders.php" class="btn btn-outline">← Back to Orders</a>
    </div>
</div>

<script>
function showCancelForm(orderId) {
    document.getElementById('cancelForm').style.display = 'block';
}

function hideCancelForm() {
    document.getElementById('cancelForm').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>