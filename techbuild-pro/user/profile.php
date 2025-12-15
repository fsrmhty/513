<?php
require_once '../config/session.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

// WordPress 数据库配置（与 subscribe_login.php 相同）
$wp_host = 'sql308.infinityfree.com';
$wp_dbname = 'if0_37528983_wp458';
$wp_username = 'if0_37528983';
$wp_password = 'cH97l2BhUUqrMGF';

$error = '';
$success = '';

// 检查用户是普通用户还是订阅者
$user_id = $_SESSION['user_id'];
$is_subscriber = isset($_SESSION['login_source']) && $_SESSION['login_source'] === 'subscriber';

try {
    if ($is_subscriber) {
        // 订阅者：连接 WordPress 数据库
        $wp_pdo = new PDO("mysql:host=$wp_host;dbname=$wp_dbname;charset=utf8mb4", $wp_username, $wp_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // 从会话中获取订阅者ID（移除可能的偏移量）
        $subscriber_id = $user_id;
        if ($subscriber_id > 100000) {
            $subscriber_id = $subscriber_id - 100000;
        }
        
        // 查询订阅者信息
        $stmt = $wp_pdo->prepare("
            SELECT id, email, phone, first_name, last_name, status, created_at 
            FROM wpgu_fc_subscribers 
            WHERE id = ? AND status = 'subscribed'
        ");
        $stmt->execute([$subscriber_id]);
        $current_user = $stmt->fetch();
        
        if (!$current_user) {
            // 如果找不到订阅者数据，可能数据库不同步，从会话中获取
            $current_user = [
                'id' => $subscriber_id,
                'email' => $_SESSION['user_email'] ?? '',
                'first_name' => explode(' ', $_SESSION['user_name'] ?? '')[0] ?? '',
                'last_name' => explode(' ', $_SESSION['user_name'] ?? '')[1] ?? '',
                'phone' => 'Not available',
                'status' => 'subscribed',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // 格式化全名
        $current_user['name'] = trim($current_user['first_name'] . ' ' . $current_user['last_name']);
        if (empty($current_user['name'])) {
            $current_user['name'] = $_SESSION['user_name'] ?? 'Subscriber';
        }
        
    } else {
        // 普通用户：连接主数据库
        require_once '../config/database.php';
        
        $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();
        
        if (!$current_user) {
            die("User not found.");
        }
        
        // 添加一些默认字段以保持一致性
        $current_user['phone'] = 'Not set';
        $current_user['status'] = $current_user['role'] ?? 'active';
    }
    
} catch (Exception $e) {
    $error = "Database connection error. Please try again later.";
    error_log("Profile error: " . $e->getMessage());
    
    // 如果数据库连接失败，从会话中获取基本信息
    $current_user = [
        'id' => $user_id,
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => 'Not available',
        'status' => 'active',
        'created_at' => 'Unknown'
    ];
}

// 处理表单提交（更新资料）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证逻辑
    if (empty($name)) {
        $error = "Name is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            if ($is_subscriber) {
                // 更新订阅者信息（仅更新姓名和邮箱，密码无法修改）
                $wp_pdo = new PDO("mysql:host=$wp_host;dbname=$wp_dbname;charset=utf8mb4", $wp_username, $wp_password);
                
                // 拆分姓名
                $name_parts = explode(' ', $name, 2);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                
                $stmt = $wp_pdo->prepare("
                    UPDATE wpgu_fc_subscribers 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?
                    WHERE id = ? AND status = 'subscribed'
                ");
                
                $result = $stmt->execute([
                    $first_name, 
                    $last_name, 
                    $email, 
                    $phone,
                    $current_user['id']
                ]);
                
                if ($result) {
                    // 更新会话信息
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    // 重新获取更新后的用户信息
                    $stmt = $wp_pdo->prepare("SELECT * FROM wpgu_fc_subscribers WHERE id = ?");
                    $stmt->execute([$current_user['id']]);
                    $current_user = $stmt->fetch();
                    $current_user['name'] = $name;
                    
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
                
            } else {
                // 更新普通用户信息
                require_once '../config/database.php';
                
                // 检查邮箱是否被其他用户使用
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $error = "Email is already registered by another user.";
                } else {
                    // 更新基本信息
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $result = $stmt->execute([$name, $email, $user_id]);
                    
                    if ($result) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $current_user['name'] = $name;
                        $current_user['email'] = $email;
                        
                        // 检查是否需要更新密码
                        if (!empty($current_password) && !empty($new_password)) {
                            // 验证当前密码
                            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch();
                            
                            if ($user && password_verify($current_password, $user['password'])) {
                                if (strlen($new_password) < 6) {
                                    $error = "New password must be at least 6 characters.";
                                } elseif ($new_password !== $confirm_password) {
                                    $error = "New passwords do not match.";
                                } else {
                                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                                    $stmt->execute([$hashed, $user_id]);
                                    
                                    $success = "Profile and password updated successfully!";
                                }
                            } else {
                                $error = "Current password is incorrect.";
                            }
                        } else {
                            $success = "Profile updated successfully!";
                        }
                    } else {
                        $error = "Failed to update profile. Please try again.";
                    }
                }
            }
            
        } catch (Exception $e) {
            $error = "Update failed. Please try again.";
            error_log("Update error: " . $e->getMessage());
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">My Profile</h1>
    <p class="page-subtitle">Manage your account information</p>
</div>

<div class="admin-container">
    <!-- 用户类型标识 -->
    <div class="card-hover" style="background: <?= $is_subscriber ? 'var(--success-light)' : 'var(--primary-50)' ?>; padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>Account Type:</strong> 
                <span style="color: <?= $is_subscriber ? 'var(--success)' : 'var(--primary)' ?>;">
                    <?= $is_subscriber ? 'Subscriber Customer' : 'Regular Customer' ?>
                </span>
            </div>
            <div>
                <strong>Member Since:</strong> 
                <?= date('M j, Y', strtotime($current_user['created_at'])) ?>
            </div>
        </div>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <h3 style="color: var(--primary); margin-bottom: 1.5rem;">Account Information</h3>
            
            <div class="form-group">
                <label class="form-label" for="name">Full Name:</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?= htmlspecialchars($current_user['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address:</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" required>
            </div>
            
            <?php if ($is_subscriber): ?>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" 
                           <?= $current_user['phone'] === 'Not available' ? '' : 'required' ?>>
                    <small style="color: var(--gray-500);">Phone number from WordPress subscription</small>
                </div>
            <?php endif; ?>

            <?php if (!$is_subscriber): ?>
                <!-- 普通用户的密码修改部分 -->
                <h3 style="color: var(--primary); margin: 2rem 0 1.5rem;">Change Password (Optional)</h3>
                
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" 
                           class="form-control" placeholder="Leave blank to skip password change">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="form-control" placeholder="At least 6 characters">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
            <?php else: ?>
                <!-- 订阅者的密码提示 -->
                <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius); margin: 1.5rem 0;">
                    <p style="margin: 0; color: var(--gray-600);">
                        <strong>Note:</strong> As a WordPress subscriber, your password is managed through 
                        <a href="https://hhh.free.nf/WP/" target="_blank" style="color: var(--primary);">WordPress</a>. 
                        Please login there to change your password.
                    </p>
                </div>
            <?php endif; ?>

            <div class="form-group" style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="/techbuild-pro/" class="btn btn-outline">← Back to Home</a>
                <?php if ($is_subscriber): ?>
                    <a href="https://hhh.free.nf/WP/" target="_blank" class="btn btn-secondary">
                        Manage WordPress Account
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- 账户信息概览 -->
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--gray-200);">
            <h4 style="color: var(--gray-700); margin-bottom: 1rem;">Account Overview</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius);">
                    <div style="font-size: 0.875rem; color: var(--gray-600);">User ID</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($current_user['id']) ?></div>
                </div>
                <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius);">
                    <div style="font-size: 0.875rem; color: var(--gray-600);">Account Status</div>
                    <div style="font-weight: 600; color: var(--success);">
                        <?= ucfirst(htmlspecialchars($current_user['status'] ?? 'active')) ?>
                    </div>
                </div>
                <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius);">
                    <div style="font-size: 0.875rem; color: var(--gray-600);">Database Source</div>
                    <div style="font-weight: 600;">
                        <?= $is_subscriber ? 'WordPress FluentCRM' : 'Main Database' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 安全提醒 -->
        <div style="background: var(--primary-50); padding: 1rem; border-radius: var(--radius); margin-top: 1.5rem;">
            <div style="display: flex; gap: 0.75rem; align-items: start;">
                <span style="color: var(--primary); font-size: 1.25rem;">🔒</span>
                <div>
                    <strong>Security Note</strong>
                    <p style="margin: 0.25rem 0 0; color: var(--gray-600); font-size: 0.875rem;">
                        Your personal information is securely stored. 
                        <?php if ($is_subscriber): ?>
                            As a subscriber, you can manage additional preferences in your WordPress account.
                        <?php else: ?>
                            Please ensure your password is strong and unique.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 表单验证
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (!name) {
            alert('Please enter your name');
            e.preventDefault();
            return false;
        }
        
        if (!email) {
            alert('Please enter your email');
            e.preventDefault();
            return false;
        }
        
        // 邮箱格式验证
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            e.preventDefault();
            return false;
        }
        
        // 如果是订阅者，验证电话
        <?php if ($is_subscriber && $current_user['phone'] !== 'Not available'): ?>
            const phone = document.getElementById('phone').value.trim();
            if (!phone) {
                alert('Phone number is required for subscribers');
                e.preventDefault();
                return false;
            }
        <?php endif; ?>
        
        // 如果是普通用户，验证密码修改
        <?php if (!$is_subscriber): ?>
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // 如果用户试图修改密码
            if (currentPassword || newPassword || confirmPassword) {
                // 必须填写所有密码字段
                if (!currentPassword || !newPassword || !confirmPassword) {
                    alert('Please fill all password fields if you want to change password');
                    e.preventDefault();
                    return false;
                }
                
                // 新密码至少6位
                if (newPassword.length < 6) {
                    alert('New password must be at least 6 characters');
                    e.preventDefault();
                    return false;
                }
                
                // 确认密码匹配
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match');
                    e.preventDefault();
                    return false;
                }
            }
        <?php endif; ?>
        
        return true;
    });
});
</script>

<?php include '../includes/footer.php'; ?>