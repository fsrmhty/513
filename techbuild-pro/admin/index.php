<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

$stats = get_dashboard_stats($pdo);

// 获取工单统计（新增）
$ticket_stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_tickets,
        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets
    FROM repair_tickets
");
$ticket_stats = $ticket_stats_stmt->fetch();
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
    <p class="page-subtitle">Manage your store and view analytics</p>
</div>

<div class="admin-container">
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <p><?= $stats['order_count'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Revenue</h3>
            <p>$<?= number_format($stats['total_revenue'], 2) ?></p>
        </div>
        <div class="stat-card">
            <h3>Users</h3>
            <p><?= $stats['user_count'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Pending Repairs</h3>
            <p><?= $stats['pending_repairs'] ?></p>
        </div>
        <!-- 新增工单统计 -->
        <div class="stat-card">
            <h3>New Tickets</h3>
            <p><?= $ticket_stats['new_tickets'] ?? 0 ?></p>
        </div>
        <div class="stat-card">
            <h3>Assigned Tickets</h3>
            <p><?= $ticket_stats['assigned_tickets'] ?? 0 ?></p>
        </div>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Quick Actions</h3>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="products.php" class="btn btn-primary">Manage Products & Services</a>
            <a href="orders.php" class="btn btn-secondary">View Orders</a>
            <a href="bookings.php" class="btn btn-success">Repair Appointments</a>
            <a href="users.php" class="btn btn-outline">User Management</a>
            <!-- 新增工单管理链接 -->
            <a href="ticket_management.php" class="btn btn-warning">Ticket Management</a>
        </div>
    </div>

    <!-- 新增工单概览部分 -->
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 2rem;">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
            <h3 style="color: var(--primary); margin: 0;">Recent Repair Tickets</h3>
            <a href="ticket_management.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        
        <?php
        // 获取最近的工单
        $recent_tickets_stmt = $pdo->query("
            SELECT 
                rt.*,
                u.name as customer_name,
                ut.name as technician_name,
                p.name as service_name
            FROM repair_tickets rt
            JOIN repair_bookings rb ON rt.repair_booking_id = rb.id
            JOIN order_items oi ON rb.order_item_id = oi.id
            JOIN orders o ON oi.order_id = o.id
            JOIN users u ON o.user_id = u.id
            LEFT JOIN users ut ON rt.assigned_technician_id = ut.id
            JOIN products p ON oi.product_id = p.id
            ORDER BY rt.created_at DESC
            LIMIT 5
        ");
        $recent_tickets = $recent_tickets_stmt->fetchAll();
        ?>

        <?php if (empty($recent_tickets)): ?>
            <div class="text-center" style="padding: 2rem;">
                <p style="color: var(--gray-500);">No repair tickets found.</p>
                <a href="ticket_management.php" class="btn btn-primary">Create First Ticket</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_tickets as $ticket): ?>
                        <tr>
                            <td>#<?= $ticket['id'] ?></td>
                            <td><?= htmlspecialchars($ticket['customer_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['service_name']) ?></td>
                            <td>
                                <span class="priority-badge priority-<?= $ticket['priority'] ?>">
                                    <?= ucfirst($ticket['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $ticket['status'] ?>">
                                    <?= ucfirst($ticket['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $ticket['technician_name'] ? htmlspecialchars($ticket['technician_name']) : '<em style="color: var(--gray-500);">Unassigned</em>' ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($ticket['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>