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
    // Check if user exists and is active
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, first_name, last_name, role, is_active 
        FROM users 
        WHERE (username = :username OR email = :username) AND is_active = 1
        LIMIT 1
    ");
    
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
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