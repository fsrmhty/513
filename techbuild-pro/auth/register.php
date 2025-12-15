<?php
require_once '../config/session.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            // 检查邮箱是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                // 插入新用户
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
                $stmt->execute([$name, $email, $hashed]);
                $_SESSION['success'] = "Registration successful! Please log in.";
                header("Location: login.php");
                exit;
            }
        } catch (Exception $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Create Your Account</h1>
    <p class="page-subtitle">Join TechBuild Pro to start building and repairing</p>
</div>

<div style="max-width: 400px; margin: 0 auto;">
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name:</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-full">Register</button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 1rem;">
            <p>Already have an account? <a href="/techbuild-pro/auth/login.php" style="color: var(--primary);">Login here</a></p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>