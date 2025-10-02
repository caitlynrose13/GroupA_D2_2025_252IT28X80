<?php
// filepath: src/authenticate.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if (empty($username) || empty($password)) {
    header('Location: login.php?error=' . urlencode('Please enter both username and password'));
    exit();
}

try {
    // Check if user exists and is active, get role information
    $stmt = $pdo->prepare("
        SELECT u.id, u.organization_id, u.username, u.email, u.password_hash, 
               u.first_name, u.last_name, r.name as role, u.is_active 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE (u.username = :username OR u.email = :username) AND u.is_active = 1
        LIMIT 1
    ");
    
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['organization_id'] = $user['organization_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Update last login time
        $update_stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
        $update_stmt->execute(['id' => $user['id']]);
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        // Login failed
        header('Location: login.php?error=' . urlencode('Invalid username/email or password'));
        exit();
    }
    
} catch (PDOException $e) {
    // Database error
    error_log("Login error: " . $e->getMessage());
    header('Location: login.php?error=' . urlencode('System error. Please try again later.'));
    exit();
}
?>