<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();


// 处理角色更新（允许设为 customer、admin 或 technician）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $role = $_POST['role'];

    // 允许所有三种角色
    if (in_array($role, ['customer', 'admin', 'technician'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND id != ?");
        // 防止把自己降级
        $stmt->execute([$role, $user_id, $_SESSION['user_id']]);
        header("Location: users.php?msg=role_updated");
        exit;
    } else {
        // 可选：添加错误处理
        $_SESSION['error'] = "Invalid role selected";
        header("Location: users.php");
        exit;
    }
}

// 获取所有用户
$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">User Management</h1>
    <p class="page-subtitle">Manage user roles and permissions</p>
</div>

<div class="admin-container">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'role_updated'): ?>
        <div class="alert alert-success">User role updated successfully!</div>
    <?php endif; ?>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span style="padding: 0.25rem 0.75rem; border-radius: var(--radius); background: 
                                <?= $u['role'] === 'admin' ? 'var(--primary-light)' : 'var(--success-light)' ?>; 
                                color: <?= $u['role'] === 'admin' ? 'var(--primary)' : 'var(--success)' ?>;">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): // 不能操作自己 ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" class="form-control" style="width: auto; display: inline-block;">
                                    <option value="customer" <?= $u['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="technician" <?= $u['role'] === 'technician' ? 'selected' : '' ?>>Technician</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Set Role</button>
                            </form>
                            <?php else: ?>
                                <em style="color: var(--gray-500);">(You)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>