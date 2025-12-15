<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查是否是技术人员
if (!isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'technician' && $role !== 'admin') {
    header("Location: /techbuild-pro/");
    exit;
}

$technician_id = $_SESSION['user_id'];

// 处理状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $schedule_id = (int)$_POST['schedule_id'];
        $new_status = $_POST['status'];
        
        // 验证状态值
        $allowed_statuses = ['scheduled', 'in_progress', 'completed'];
        if (in_array($new_status, $allowed_statuses)) {
            $stmt = $pdo->prepare("UPDATE technician_schedule SET status = ? WHERE id = ? AND technician_id = ?");
            $stmt->execute([$new_status, $schedule_id, $technician_id]);
            
            $_SESSION['success'] = "Repair status updated successfully!";
            header("Location: repairs.php");
            exit;
        }
    }
    
    if (isset($_POST['add_note'])) {
        $schedule_id = (int)$_POST['schedule_id'];
        $note = trim($_POST['note']);
        
        if (!empty($note)) {
            // 更新描述或添加备注
            $stmt = $pdo->prepare("UPDATE technician_schedule SET description = CONCAT(COALESCE(description, ''), '\n\n[Note] ', ?) WHERE id = ? AND technician_id = ?");
            $stmt->execute([$note, $schedule_id, $technician_id]);
            
            $_SESSION['success'] = "Note added successfully!";
            header("Location: repairs.php");
            exit;
        }
    }
}

// 获取技术人员的维修任务
$stmt = $pdo->prepare("
    SELECT 
        ts.*,
        rb.device_type,
        rb.symptoms,
        u.name as customer_name,
        u.email as customer_email,
        p.name as service_name
    FROM technician_schedule ts
    LEFT JOIN repair_bookings rb ON ts.repair_booking_id = rb.id
    LEFT JOIN order_items oi ON rb.order_item_id = oi.id
    LEFT JOIN orders o ON oi.order_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE ts.technician_id = ?
    ORDER BY 
        CASE 
            WHEN ts.status = 'in_progress' THEN 1
            WHEN ts.status = 'scheduled' THEN 2
            WHEN ts.status = 'completed' THEN 3
            ELSE 4
        END,
        ts.start_datetime ASC
");
$stmt->execute([$technician_id]);
$repairs = $stmt->fetchAll();

// 统计信息
$stats = [
    'scheduled' => count(array_filter($repairs, fn($r) => $r['status'] === 'scheduled')),
    'in_progress' => count(array_filter($repairs, fn($r) => $r['status'] === 'in_progress')),
    'completed' => count(array_filter($repairs, fn($r) => $r['status'] === 'completed')),
    'total' => count($repairs)
];
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Repair Management</h1>
    <p class="page-subtitle">Manage and track your repair assignments</p>
</div>

<div class="admin-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- 统计卡片 -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Repairs</h3>
            <p><?= $stats['total'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Scheduled</h3>
            <p><?= $stats['scheduled'] ?></p>
        </div>
        <div class="stat-card">
            <h3>In Progress</h3>
            <p><?= $stats['in_progress'] ?></p>
        </div>
        <div class="stat-card">
            <h3>Completed</h3>
            <p><?= $stats['completed'] ?></p>
        </div>
    </div>

    <!-- 维修任务表格 -->
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">My Repair Assignments</h3>
        
        <?php if (empty($repairs)): ?>
            <div class="text-center" style="padding: 3rem;">
                <p style="font-size: 1.2rem; margin-bottom: 1rem;">No repair assignments found</p>
                <a href="/techbuild-pro/technician/" class="btn btn-primary">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Scheduled Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repairs as $repair): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($repair['title']) ?></strong>
                                <?php if (!empty($repair['device_type'])): ?>
                                    <br><small>Device: <?= htmlspecialchars($repair['device_type']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($repair['symptoms'])): ?>
                                    <br><small>Issue: <?= htmlspecialchars($repair['symptoms']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($repair['customer_name'])): ?>
                                    <?= htmlspecialchars($repair['customer_name']) ?>
                                    <br><small><?= htmlspecialchars($repair['customer_email']) ?></small>
                                <?php else: ?>
                                    <em>No customer info</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= !empty($repair['service_name']) ? htmlspecialchars($repair['service_name']) : 'General Repair' ?>
                            </td>
                            <td>
                                <?= date('M j, Y', strtotime($repair['start_datetime'])) ?><br>
                                <small><?= date('g:i A', strtotime($repair['start_datetime'])) ?> - <?= date('g:i A', strtotime($repair['end_datetime'])) ?></small>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: var(--radius); background: 
                                    <?= $repair['status'] === 'completed' ? 'var(--success-light)' : 
                                      ($repair['status'] === 'in_progress' ? 'var(--warning-light)' : 'var(--primary-light)') ?>; 
                                    color: <?= $repair['status'] === 'completed' ? 'var(--success)' : 
                                           ($repair['status'] === 'in_progress' ? 'var(--warning)' : 'var(--primary)') ?>;">
                                    <?= ucfirst($repair['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <!-- 状态更新表单 -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="schedule_id" value="<?= $repair['id'] ?>">
                                        <select name="status" class="form-control" style="width: auto; display: inline-block; margin-bottom: 0.5rem;" 
                                                onchange="this.form.submit()">
                                            <option value="scheduled" <?= $repair['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                            <option value="in_progress" <?= $repair['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="completed" <?= $repair['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>

                                    <!-- 添加备注按钮 -->
                                    <button type="button" onclick="showNoteForm(<?= $repair['id'] ?>)" 
                                            class="btn btn-sm btn-outline">Add Note</button>

                                    <!-- 查看详情按钮 -->
                                    <button type="button" onclick="showRepairDetails(<?= $repair['id'] ?>)" 
                                            class="btn btn-sm btn-secondary">Details</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 快速操作 -->
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Quick Actions</h3>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/techbuild-pro/technician/" class="btn btn-primary">Back to Dashboard</a>
            <button onclick="showQuickReport()" class="btn btn-secondary">Generate Quick Report</button>
            <button onclick="showAllCompleted()" class="btn btn-outline">View Completed Repairs</button>
        </div>
    </div>
</div>

<!-- 添加备注的模态框 -->
<div id="noteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); width: 90%; max-width: 500px;">
        <h3>Add Note</h3>
        <form method="POST" id="noteForm">
            <input type="hidden" name="schedule_id" id="noteScheduleId">
            <div class="form-group">
                <label class="form-label">Note:</label>
                <textarea name="note" class="form-control" rows="4" placeholder="Enter your notes about this repair..." required></textarea>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" name="add_note" value="1" class="btn btn-primary">Save Note</button>
                <button type="button" onclick="hideNoteForm()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showNoteForm(scheduleId) {
    document.getElementById('noteScheduleId').value = scheduleId;
    document.getElementById('noteModal').style.display = 'flex';
}

function hideNoteForm() {
    document.getElementById('noteModal').style.display = 'none';
    document.getElementById('noteForm').reset();
}

function showRepairDetails(repairId) {
    alert('Repair Details for ID: ' + repairId + '\n\nIn a full implementation, this would show detailed information including:\n- Customer contact details\n- Repair history\n- Parts used\n- Time tracking\n- Photos and documents');
}

function showQuickReport() {
    const completed = <?= $stats['completed'] ?>;
    const inProgress = <?= $stats['in_progress'] ?>;
    const scheduled = <?= $stats['scheduled'] ?>;
    
    alert('Quick Repair Report:\n\n' +
          `Completed: ${completed} repairs\n` +
          `In Progress: ${inProgress} repairs\n` +
          `Scheduled: ${scheduled} repairs\n` +
          `Total: ${completed + inProgress + scheduled} repairs\n\n` +
          'Completion Rate: ' + Math.round((completed / (completed + inProgress + scheduled)) * 100) + '%');
}

function showAllCompleted() {
    alert('Showing all completed repairs...\n\nIn a full implementation, this would filter the table to show only completed repairs.');
}

// 点击模态框外部关闭
document.getElementById('noteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideNoteForm();
    }
});
</script>

<?php include '../includes/footer.php'; ?>