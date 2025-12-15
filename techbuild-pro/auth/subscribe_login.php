<?php
require_once '../config/session.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/");
    exit;
}

// WordPress FluentCRM Database Configuration
$wp_host = 'sql308.infinityfree.com';
$wp_dbname = 'if0_37528983_wp458';
$wp_username = 'if0_37528983';
$wp_password = 'cH97l2BhUUqrMGF';

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($email) || empty($phone)) {
        $error = "Please enter both email and phone number";
    } else {
        try {
            // Connect to WordPress database
            $wp_pdo = new PDO("mysql:host=$wp_host;dbname=$wp_dbname;charset=utf8mb4", $wp_username, $wp_password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // Clean phone number - remove all non-digit characters
            $clean_phone = preg_replace('/\D/', '', $phone);
            
            // Query subscriber information
            $stmt = $wp_pdo->prepare("
                SELECT id, email, phone, first_name, last_name, status, created_at 
                FROM wpgu_fc_subscribers 
                WHERE email = ? AND phone LIKE ? AND status = 'subscribed'
            ");
            $stmt->execute([$email, "%$clean_phone%"]);
            $subscriber = $stmt->fetch();
            
            if ($subscriber) {
                // ✅ IMPORTANT: Set session as regular customer, not subscriber
                $_SESSION['user_id'] = $subscriber['id'] + 100000; // Add offset to avoid conflict with main users table
                $_SESSION['user_name'] = $subscriber['first_name'] . ' ' . $subscriber['last_name'];
                $_SESSION['user_email'] = $subscriber['email'];
                $_SESSION['user_role'] = 'customer'; // Set as customer role
                $_SESSION['login_source'] = 'subscriber'; // Mark as from subscriber login
                
                $success = "Login successful! Welcome back " . ($subscriber['first_name'] ?: 'Customer');
                
                // Redirect to home page immediately
                header("Location: /techbuild-pro/");
                exit;
                
            } else {
                $error = "Invalid email or phone number, or you are not subscribed";
            }
            
        } catch (PDOException $e) {
            $error = "Database connection error, please try again later";
            error_log("WordPress DB Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriber Login - TechBuild Pro</title>
    <link rel="stylesheet" href="/techbuild-pro/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="page-header">
        <h1 class="page-title">Customer Login</h1>
        <p class="page-subtitle">Login using your subscription email and phone number</p>
    </div>

    <div style="max-width: 400px; margin: 0 auto;">
        <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address:</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your subscription email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="Enter your phone number"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-full">Login as Customer</button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                <p style="color: var(--gray-600); margin-bottom: 0.5rem;">Not subscribed yet?</p>
                <a href="https://hhh.free.nf/WP/email/" class="btn btn-outline" target="_blank">Subscribe Now</a>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="/techbuild-pro/auth/login.php" style="color: var(--primary); font-size: 0.875rem;">
                    ↗ Regular Account Login
                </a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
    });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>