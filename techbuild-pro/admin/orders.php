<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

// 处理状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    // 验证状态值
    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        header("Location: orders.php?msg=updated");
        exit;
    }
}

// 获取所有订单（含用户和总金额）
$stmt = $pdo->query("
    SELECT o.id, o.total, o.status, o.created_at,
           u.name AS customer_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Order Management</h1>
    <p class="page-subtitle">View and update customer orders</p>
</div>

<div class="admin-container">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success">Order status updated successfully!</div>
    <?php endif; ?>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>#<?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td><?= htmlspecialchars($o['email']) ?></td>
                        <td>$<?= number_format($o['total'], 2) ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.75rem; border-radius: var(--radius); background: 
                                <?= $o['status'] === 'completed' ? 'var(--success-light)' : 
                                  ($o['status'] === 'cancelled' ? 'var(--error-light)' : 
                                  ($o['status'] === 'confirmed' ? 'var(--primary-light)' : 'var(--gray-100)')) ?>; 
                                color: <?= $o['status'] === 'completed' ? 'var(--success)' : 
                                       ($o['status'] === 'cancelled' ? 'var(--error)' : 
                                       ($o['status'] === 'confirmed' ? 'var(--primary)' : 'var(--gray-700)')) ?>;">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td><?= date('Y-m-d', strtotime($o['created_at'])) ?></td>
                        <td>
                            <form method="POST" class="inline">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-control" style="width: auto; display: inline-block;">
                                    <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $o['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>