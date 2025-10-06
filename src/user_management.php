<?php
// filepath: src/user_management.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in and has admin permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied. Administrator permission required.'));
    exit();
}

// Get users based on role
try {
    if ($_SESSION['role'] === 'system_admin') {
        // System admin can see all users
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, o.name as organization_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN organizations o ON u.organization_id = o.id 
            WHERE u.is_active = 1 
            ORDER BY u.organization_id, u.role_id, u.last_name
        ");
        $stmt->execute();
    } else {
        // Org admin can only see users in their organization
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, o.name as organization_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN organizations o ON u.organization_id = o.id 
            WHERE u.is_active = 1 AND u.organization_id = :org_id 
            ORDER BY u.role_id, u.last_name
        ");
        $stmt->execute(['org_id' => $_SESSION['organization_id']]);
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = "Error loading users: " . $e->getMessage();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SA SMME Cybersecurity Platform</title>
    <!-- African-Inspired Styling -->
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <!-- African Pattern Border -->
    <div class="african-border"></div>
    
    <div class="header">
        <h1>👥 User Management</h1>
        <div class="nav-links">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="content_list.php">📚 Content</a>
            <a href="quiz_list.php">📝 Quizzes</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Placeholder Notice -->
        <div class="placeholder-notice">
            <h3>🚧 User Management Interface</h3>
            <p><strong>Status:</strong> Basic user listing implemented. Full CRUD operations can be added as needed.</p>
            <p><strong>Current Functionality:</strong> View users based on your role permissions</p>
            <p><strong>System Admin:</strong> Can see all users across all organizations</p>
            <p><strong>Organization Admin:</strong> Can see users in their organization only</p>
        </div>

        <h3>
            <?php if ($_SESSION['role'] === 'system_admin'): ?>
                All Platform Users
            <?php else: ?>
                Users in Your Organization
            <?php endif; ?>
        </h3>

        <?php if (empty($users)): ?>
            <div class="users-table">
                <p style="padding: 20px; text-align: center; color: #666;">No users found.</p>
            </div>
        <?php else: ?>
            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <?php if ($_SESSION['role'] === 'system_admin'): ?>
                                <th>Organization</th>
                            <?php endif; ?>
                            <th>Department</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role_name']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $user['role_name'])); ?>
                                    </span>
                                </td>
                                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                                    <td><?php echo htmlspecialchars($user['organization_name'] ?? 'System'); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                                <td><?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4>📊 User Statistics</h4>
            <p><strong>Total Users:</strong> <?php echo count($users); ?></p>
            <?php
                $role_counts = array_count_values(array_column($users, 'role_name'));
                foreach ($role_counts as $role => $count) {
                    echo "<p><strong>" . ucwords(str_replace('_', ' ', $role)) . ":</strong> $count</p>";
                }
            ?>
        </div>
    </div>
</body>
</html>