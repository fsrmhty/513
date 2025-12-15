<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

require_once '../config/database.php';

// 获取用户订单
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT o.* 
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">My Orders</h1>
    <p class="page-subtitle">View and manage your order history</p>
</div>

<div class="admin-container">
    <?php if (empty($orders)): ?>
        <div class="text-center" style="padding: 3rem;">
            <p style="font-size: 1.2rem; margin-bottom: 1rem;">You have no orders yet</p>
            <a href="/techbuild-pro/products/" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td>$<?= number_format($order['total'], 2) ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.75rem; border-radius: var(--radius); background: 
                                <?= $order['status'] === 'completed' ? 'var(--success-light)' : 
                                  ($order['status'] === 'cancelled' ? 'var(--error-light)' : 'var(--gray-100)') ?>; 
                                color: <?= $order['status'] === 'completed' ? 'var(--success)' : 
                                       ($order['status'] === 'cancelled' ? 'var(--error)' : 'var(--gray-700)') ?>;">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                        <td>
                            <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline">View Details</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>