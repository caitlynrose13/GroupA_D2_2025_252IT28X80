<?php
// filepath: src/approve_user.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in and is a system admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'system_admin') {
    header('Location: dashboard.php?error=' . urlencode('Access denied. System administrator permission required.'));
    exit();
}

// Get parameters
$user_id = $_GET['user_id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$user_id || !$action || !in_array($action, ['approve', 'reject'])) {
    header('Location: user_management.php?error=' . urlencode('Invalid request parameters.'));
    exit();
}

try {
    // Get user details to verify they are pending approval
    $user_stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, es.name as status_name, o.name as organization_name
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        LEFT JOIN employee_statuses es ON u.status_id = es.id
        LEFT JOIN organizations o ON u.organization_id = o.id
        WHERE u.id = :user_id AND es.name = 'pending_approval' AND r.name = 'org_admin'
    ");
    $user_stmt->execute(['user_id' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: user_management.php?error=' . urlencode('User not found or not pending approval.'));
        exit();
    }
    
    if ($action === 'approve') {
        // Approve the user - set status to active
        $approve_stmt = $pdo->prepare("
            UPDATE users 
            SET status_id = (SELECT id FROM employee_statuses WHERE name = 'active'),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id
        ");
        $approve_stmt->execute(['user_id' => $user_id]);
        
        $success_message = "Organization administrator account for " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " has been approved successfully.";
        
        // Log the approval action
        error_log("SYSTEM ADMIN APPROVAL: User {$user['username']} (ID: {$user_id}) approved by system admin {$_SESSION['username']} (ID: {$_SESSION['user_id']})");
        
    } elseif ($action === 'reject') {
        // Reject the user - set status to inactive and update comment
        $reject_stmt = $pdo->prepare("
            UPDATE users 
            SET status_id = (SELECT id FROM employee_statuses WHERE name = 'inactive'),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id
        ");
        $reject_stmt->execute(['user_id' => $user_id]);
        
        $success_message = "Organization administrator account for " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " has been rejected and deactivated.";
        
        // Log the rejection action
        error_log("SYSTEM ADMIN REJECTION: User {$user['username']} (ID: {$user_id}) rejected by system admin {$_SESSION['username']} (ID: {$_SESSION['user_id']})");
    }
    
    header('Location: user_management.php?success=' . urlencode($success_message));
    exit();
    
} catch (PDOException $e) {
    error_log("User approval error: " . $e->getMessage());
    header('Location: user_management.php?error=' . urlencode('Database error occurred while processing the request.'));
    exit();
}
?>