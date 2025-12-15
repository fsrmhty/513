<?php
require_once '../config/session.php';
if (isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/");
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            header("Location: /techbuild-pro/");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } catch (Exception $e) {
        $error = "Login failed. Please try again.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Login to Your Account</h1>
    <p class="page-subtitle">Access your orders, track repairs, and manage your profile</p>
</div>

<div style="max-width: 400px; margin: 0 auto;">
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 1rem;">
            <p>Don't have an account? <a href="/techbuild-pro/auth/register.php" style="color: var(--primary);">Register here</a></p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>