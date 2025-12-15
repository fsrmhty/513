<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

// 更新预约状态
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $stmt = $pdo->prepare("UPDATE repair_bookings SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['booking_id']]);
    header("Location: bookings.php?msg=updated");
    exit;
}

// 获取所有维修预约（关联订单和产品）
$stmt = $pdo->query("
    SELECT rb.id, rb.status, rb.device_type, rb.symptoms, 
           u.name AS customer, p.name AS service_name, o.created_at
    FROM repair_bookings rb
    JOIN order_items oi ON rb.order_item_id = oi.id
    JOIN orders o ON oi.order_id = o.id
    JOIN users u ON o.user_id = u.id
    JOIN products p ON oi.product_id = p.id
    ORDER BY rb.status, o.created_at DESC
");
$bookings = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Repair Appointments</h1>
    <p class="page-subtitle">Manage and update repair booking status</p>
</div>

<div class="admin-container">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success">Booking status updated successfully!</div>
    <?php endif; ?>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Device</th>
                        <th>Symptoms</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><?= htmlspecialchars($b['customer']) ?></td>
                        <td><?= htmlspecialchars($b['service_name']) ?></td>
                        <td><?= htmlspecialchars($b['device_type']) ?></td>
                        <td><?= htmlspecialchars($b['symptoms']) ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.75rem; border-radius: var(--radius); background: 
                                <?= $b['status'] === 'completed' ? 'var(--success-light)' : 
                                  ($b['status'] === 'in_progress' ? 'var(--warning-light)' : 'var(--gray-100)') ?>; 
                                color: <?= $b['status'] === 'completed' ? 'var(--success)' : 
                                       ($b['status'] === 'in_progress' ? 'var(--warning)' : 'var(--gray-700)') ?>;">
                                <?= ucfirst($b['status']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="inline">
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <select name="status" class="form-control" style="width: auto; display: inline-block;">
                                    <option value="scheduled" <?= $b['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="in_progress" <?= $b['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $b['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
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