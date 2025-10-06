<?php
// filepath: src/login.php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cybersecurity Portal</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body class="african-login-body">
    <div class="login-container african-card">
        <div class="login-header african-header">
            <h1>Sawubona - Welcome</h1>
            <p>Cybersecurity Portal</p>
            <div class="african-pattern-border"></div>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="african-error-message">
                    <?php echo htmlspecialchars(urldecode($error)); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="authenticate.php">
                <div class="african-form-group">
                    <label for="username">Username or Email <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required maxlength="100" autofocus class="african-input">
                </div>
                
                <div class="african-form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required maxlength="255" class="african-input">
                </div>
                
                <button type="submit" class="african-btn african-btn-primary">Sign In</button>
                
                <div class="african-demo-info">
                    <h4>Demo Accounts:</h4>
                    <p><strong>System Admin:</strong> admin@platform.com / password123</p>
                    <p><strong>Org Admin (TechCorp):</strong> admin@techcorp.co.za / password123</p>
                    <p><strong>Org Admin (SafeGuard):</strong> admin@safeguard.co.za / password123</p>
                    <p><strong>Employee (TechCorp):</strong> mary.jones@techcorp.co.za / password123</p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>