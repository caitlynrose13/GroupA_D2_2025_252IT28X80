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
$pending_approvals = [];
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
        
        // Also get pending approvals for organization admins
        $pending_stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, o.name as organization_name, es.name as status_name
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN organizations o ON u.organization_id = o.id 
            LEFT JOIN employee_statuses es ON u.status_id = es.id
            WHERE u.is_active = 1 AND es.name = 'pending_approval' AND r.name = 'org_admin'
            ORDER BY u.created_at DESC
        ");
        $pending_stmt->execute();
        $pending_approvals = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <!-- Pattern Border -->
    <div class="african-border"></div>
    
    <div class="header">
        <div class="header-left">
            <h1>SA SMME Cybersecurity Platform</h1>
        </div>
        <div class="header-right">
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="content_list.php">Content</a>
                <a href="quiz_list.php">Quizzes</a>
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    <a href="organization_management.php">Organizations</a>
                <?php elseif ($_SESSION['role'] === 'org_admin'): ?>
                    <a href="user_management.php">Users</a>
                <?php endif; ?>
            </nav>
            <div class="user-section">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header-section">
            <div class="page-title-area">
                <h2 class="page-title">User Management</h2>
                <p class="page-subtitle">
                    <?php if ($_SESSION['role'] === 'system_admin'): ?>
                        Manage users across all organizations
                    <?php else: ?>
                        Manage users in your organization
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($_SESSION['role'] === 'system_admin'): ?>
                <div class="page-actions">
                    <a href="create_org_admin.php" class="btn btn-primary">
                        👤 Create Organization Admin
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- User Management Info -->
        <div class="info-card">
            <h3>User Management Overview</h3>
            <p><strong>Current Access Level:</strong> 
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    System Administrator - Full platform access
                <?php else: ?>
                    Organization Administrator - Organization-specific access
                <?php endif; ?>
            </p>
            <div class="program-highlights">
                <div class="highlight-item">
                    <strong><?php echo count($users); ?></strong>
                    <span>Total Users</span>
                </div>
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    <div class="highlight-item">
                        <strong><?php echo count(array_unique(array_column($users, 'organization_name'))); ?></strong>
                        <span>Organizations</span>
                    </div>
                    <div class="highlight-item">
                        <strong><?php echo count($pending_approvals); ?></strong>
                        <span>Pending Approvals</span>
                    </div>
                <?php endif; ?>
                <div class="highlight-item">
                    <strong><?php echo count(array_unique(array_column($users, 'role_name'))); ?></strong>
                    <span>User Roles</span>
                </div>
            </div>
        </div>

        <!-- Pending Approvals Section (System Admin Only) -->
        <?php if ($_SESSION['role'] === 'system_admin' && !empty($pending_approvals)): ?>
            <div class="form-card" style="border-left: 4px solid var(--warning-color); background: #fff8e1;">
                <h3 style="margin-bottom: 25px; color: var(--warning-color); border-bottom: 2px solid var(--warning-color); padding-bottom: 10px;">
                    ⚠️ Pending Organization Admin Approvals
                </h3>
                <div class="approvals-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Organization</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_approvals as $pending): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pending['first_name'] . ' ' . $pending['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($pending['email']); ?></td>
                                    <td><?php echo htmlspecialchars($pending['organization_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($pending['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="approve_user.php?user_id=<?php echo $pending['id']; ?>&action=approve" 
                                               class="btn btn-success btn-sm"
                                               onclick="return confirm('Approve this organization admin account?')">
                                                ✅ Approve
                                            </a>
                                            <a href="approve_user.php?user_id=<?php echo $pending['id']; ?>&action=reject" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Reject this organization admin account? This will deactivate the account.')">
                                                ❌ Reject
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="form-card">
            <h3 style="margin-bottom: 25px; color: var(--primary-dark); border-bottom: 2px solid var(--light-cream); padding-bottom: 10px;">
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    All Platform Users
                <?php else: ?>
                    Users in Your Organization
                <?php endif; ?>
            </h3>
            
            <?php if (empty($users)): ?>
                <div class="info-card" style="text-align: center; padding: 40px;">
                    <h3>No users found</h3>
                    <p>There are currently no active users to display.</p>
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
        </div>
    </div>
</body>
</html>