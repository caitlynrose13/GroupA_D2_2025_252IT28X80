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

// Handle delete action for system admins
if ($_SESSION['role'] === 'system_admin' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = $_POST['user_id'] ?? '';
    
    if ($user_id) {
        try {
            // Check if user is org_admin (not employee)
            $check_stmt = $pdo->prepare("
                SELECT u.id, r.name as role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = :user_id AND r.name = 'org_admin'
            ");
            $check_stmt->execute(['user_id' => $user_id]);
            $user_to_delete = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_to_delete) {
                // Delete the organization admin
                $delete_stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = :user_id");
                $delete_stmt->execute(['user_id' => $user_id]);
                
                header('Location: user_management.php?success=' . urlencode('Organization admin deleted successfully.'));
                exit();
            } else {
                header('Location: user_management.php?error=' . urlencode('Cannot delete this user. Only organization admins can be deleted.'));
                exit();
            }
        } catch (PDOException $e) {
            header('Location: user_management.php?error=' . urlencode('Error deleting user: ' . $e->getMessage()));
            exit();
        }
    }
}

// Get filter parameters
$org_filter = $_GET['org_filter'] ?? '';
$search_query = $_GET['search'] ?? '';

// Get users based on role
$pending_approvals = [];
$user_quiz_progress = [];
$organizations = [];

try {
    // Get organizations for filter dropdown (system admin only)
    if ($_SESSION['role'] === 'system_admin') {
        $org_stmt = $pdo->prepare("SELECT id, name FROM organizations WHERE is_active = 1 ORDER BY name");
        $org_stmt->execute();
        $organizations = $org_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($_SESSION['role'] === 'system_admin') {
        // System admin can see all users with filters
        $where_conditions = ["u.is_active = 1"];
        $params = [];
        
        // Organization filter
        if ($org_filter) {
            $where_conditions[] = "u.organization_id = :org_filter";
            $params['org_filter'] = $org_filter;
        }
        
        // Search filter
        if ($search_query) {
            $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
            $params['search'] = '%' . $search_query . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, o.name as organization_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN organizations o ON u.organization_id = o.id 
            WHERE {$where_clause}
            ORDER BY u.organization_id, u.role_id, u.last_name
        ");
        $stmt->execute($params);
        
        // Also get pending approvals for organization admins
        $pending_where_conditions = ["u.is_active = 1", "es.name = 'pending_approval'", "r.name = 'org_admin'"];
        $pending_params = [];
        
        if ($org_filter) {
            $pending_where_conditions[] = "u.organization_id = :org_filter";
            $pending_params['org_filter'] = $org_filter;
        }
        
        $pending_where_clause = implode(' AND ', $pending_where_conditions);
        
        $pending_stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, o.name as organization_name, es.name as status_name
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN organizations o ON u.organization_id = o.id 
            LEFT JOIN employee_statuses es ON u.status_id = es.id
            WHERE {$pending_where_clause}
            ORDER BY u.created_at DESC
        ");
        $pending_stmt->execute($pending_params);
        $pending_approvals = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Org admin can only see users in their organization with search
        $where_conditions = ["u.is_active = 1", "u.organization_id = :org_id"];
        $params = ['org_id' => $_SESSION['organization_id']];
        
        // Search filter
        if ($search_query) {
            $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
            $params['search'] = '%' . $search_query . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, o.name as organization_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN organizations o ON u.organization_id = o.id 
            WHERE {$where_clause}
            ORDER BY u.role_id, u.last_name
        ");
        $stmt->execute($params);
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get quiz progress for org admin (only for employees)
    if ($_SESSION['role'] === 'org_admin') {
        $progress_stmt = $pdo->prepare("
            SELECT 
                u.id as user_id,
                COUNT(DISTINCT q.id) as total_quizzes,
                COUNT(DISTINCT CASE WHEN qr.passed = 1 THEN qr.quiz_id END) as passed_quizzes,
                COUNT(DISTINCT qr.quiz_id) as attempted_quizzes,
                ROUND(AVG(CASE WHEN qr.passed = 1 THEN qr.percentage END), 1) as avg_passed_score,
                ROUND(AVG(qr.percentage), 1) as avg_all_scores,
                MAX(qr.completed_at) as last_quiz_date,
                COUNT(qr.id) as total_attempts
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN quizzes q ON (q.organization_id = u.organization_id OR q.organization_id IS NULL)
            LEFT JOIN quiz_results qr ON qr.user_id = u.id AND qr.quiz_id = q.id
            WHERE u.organization_id = :org_id AND u.is_active = 1 AND r.name = 'employee'
            GROUP BY u.id
        ");
        $progress_stmt->execute(['org_id' => $_SESSION['organization_id']]);
        $progress_results = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to associative array for easy lookup
        foreach ($progress_results as $progress) {
            $user_quiz_progress[$progress['user_id']] = $progress;
        }
    }
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
    <style>
        /* Improved table styling for better usability */
        .users-table {
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        
        .users-table table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 14px;
        }
        
        .users-table th {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            color: white;
            font-weight: 600;
            padding: 16px 12px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .users-table tr:nth-child(even) {
            background-color: #fafbfc;
        }
        
        .users-table tr:hover {
            background-color: #e8f4f8;
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        
        /* Enhanced action buttons styling */
        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        
        .protected-text {
            color: #6c757d;
            font-style: italic;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        /* Enhanced role badges */
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            color: white;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Responsive table container */
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Enhanced form card styling */
        .form-card {
            width: 100%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 12px;
            background-color: #fff;
            border: 1px solid #e9ecef;
        }
        
        /* Better spacing and typography */
        .container {
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Enhanced filter form */
        .filter-form {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .filter-form .form-group {
            min-width: 200px;
            flex: 1;
        }
        
        .filter-form label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
            display: block;
        }
        
        /* Enhanced approvals table */
        .approvals-table table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .approvals-table th {
            background: var(--warning-color);
            color: white;
            padding: 12px;
            font-weight: 600;
            text-align: left;
        }
        
        .approvals-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .approvals-table tr:hover {
            background-color: #fff8e1;
        }
    </style>
    <style>
        .progress-info {
            text-align: center;
        }
        .progress-bar-container {
            margin: 0 auto;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
        }
        .progress-bar {
            border-radius: 10px;
        }
        .score-badge {
            font-size: 12px;
            white-space: nowrap;
        }
        .btn-small {
            transition: background-color 0.3s ease;
        }
        .btn-small:hover {
            background-color: var(--primary-dark) !important;
        }
        .users-table th {
            text-align: center;
        }
        .users-table td {
            vertical-align: middle;
        }
    </style>
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
                    <a href="user_management.php">Users</a>
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
                        + Create Organization Admin
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Filters and Search -->
        <div class="form-card" style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 20px; color: var(--primary-dark);">
                Filters & Search
            </h3>
            <form method="GET" action="user_management.php" class="filter-form">
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    <div class="form-group" style="min-width: 200px;">
                        <label for="org_filter">Organization:</label>
                        <select name="org_filter" id="org_filter" class="form-control">
                            <option value="">All Organizations</option>
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?php echo $org['id']; ?>" <?php echo ($org_filter == $org['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($org['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="form-group" style="min-width: 250px; flex: 1;">
                    <label for="search">Search Users:</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search by name, email, or username..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>
                    <?php if ($org_filter || $search_query): ?>
                        <a href="user_management.php" class="btn btn-secondary" style="margin-left: 10px;">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
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

        <!-- Pending Approvals Section (System Admin Only) -->
        <?php if ($_SESSION['role'] === 'system_admin' && !empty($pending_approvals)): ?>
            <div class="form-card" style="border-left: 4px solid var(--warning-color); background: #fff8e1; margin-bottom: 40px;">
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

        <!-- Quiz Performance Summary for Org Admins -->
        <?php if ($_SESSION['role'] === 'org_admin' && !empty($user_quiz_progress)): ?>
            <?php
            // Calculate organization-wide quiz statistics
            $total_employees = count(array_filter($users, function($user) { return $user['role_name'] === 'employee'; }));
            $employees_with_progress = 0;
            $total_pass_rate = 0;
            $total_avg_score = 0;
            $score_count = 0;
            $total_failed_attempts = 0;
            
            foreach ($user_quiz_progress as $progress) {
                if ($progress['total_quizzes'] > 0) {
                    $employees_with_progress++;
                    $pass_rate = ($progress['passed_quizzes'] / $progress['total_quizzes']) * 100;
                    $total_pass_rate += $pass_rate;
                    
                    // Count failed attempts
                    $total_failed_attempts += ($progress['attempted_quizzes'] - $progress['passed_quizzes']);
                    
                    // Use passed scores for average, fall back to all scores if no passes
                    $score_to_use = $progress['avg_passed_score'] ?? $progress['avg_all_scores'];
                    if ($score_to_use) {
                        $total_avg_score += $score_to_use;
                        $score_count++;
                    }
                }
            }
            
            $org_avg_pass_rate = $employees_with_progress > 0 ? round($total_pass_rate / $employees_with_progress) : 0;
            $org_avg_score = $score_count > 0 ? round($total_avg_score / $score_count, 1) : 0;
            ?>
            
            <div class="form-card" style="margin-bottom: 25px;">
                <h3 style="margin-bottom: 20px; color: var(--primary-dark);">Organization Quiz Performance Overview</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium)); color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0;">Total Employees</h4>
                        <div style="font-size: 2em; font-weight: bold;"><?php echo $total_employees; ?></div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, var(--accent-orange), var(--pattern-orange)); color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0;">Avg Pass Rate</h4>
                        <div style="font-size: 2em; font-weight: bold;"><?php echo $org_avg_pass_rate; ?>%</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, var(--accent-gold), var(--pattern-gold)); color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0;">Avg Score</h4>
                        <div style="font-size: 2em; font-weight: bold;"><?php echo $org_avg_score; ?>%</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, var(--accent-red), #c44a3a); color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0;">Failed Attempts</h4>
                        <div style="font-size: 2em; font-weight: bold;"><?php echo $total_failed_attempts; ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="form-card">
            <h3 style="margin-bottom: 25px; color: var(--primary-dark); border-bottom: 2px solid var(--light-cream); padding-bottom: 10px; font-size: 1.3em;">
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    All Platform Users
                <?php else: ?>
                    Users in Your Organization
                <?php endif; ?>
                <small style="display: block; font-size: 0.7em; color: #666; margin-top: 5px; font-weight: normal;">
                    Total: <?php echo count($users); ?> users
                </small>
            </h3>

            <?php if (empty($users)): ?>
                <div class="info-card" style="text-align: center; padding: 60px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 12px; border: 2px dashed #dee2e6;">
                    <h3 style="color: #6c757d; margin-bottom: 10px;">No users found</h3>
                    <p style="color: #868e96;">There are currently no active users to display.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="users-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <?php if ($_SESSION['role'] === 'system_admin'): ?>
                                        <th>Organization</th>
                                    <?php endif; ?>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <?php if ($_SESSION['role'] === 'org_admin'): ?>
                                        <th>Quiz Progress</th>
                                        <th>Avg Score</th>
                                    <?php endif; ?>
                                    <th>Last Login</th>
                                    <?php if ($_SESSION['role'] === 'system_admin'): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php 
                            $progress = $user_quiz_progress[$user['id']] ?? null;
                            $completion_rate = 0;
                            $pass_rate = 0;
                            if ($progress && $progress['total_quizzes'] > 0) {
                                $pass_rate = round(($progress['passed_quizzes'] / $progress['total_quizzes']) * 100);
                                $completion_rate = round(($progress['attempted_quizzes'] / $progress['total_quizzes']) * 100);
                            }
                            // Determine which score to display (passed scores only or all scores)
                            $display_score = $progress['avg_passed_score'] ?? $progress['avg_all_scores'] ?? null;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                                    <td><?php echo htmlspecialchars($user['organization_name'] ?? 'System'); ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="role-badge" style="
                                        padding: 3px 8px; 
                                        border-radius: 12px; 
                                        font-size: 0.9em;
                                        font-weight: bold; 
                                        color: white;
                                        background-color: <?php echo $user['role_name'] === 'system_admin' ? '#6c757d' : ($user['role_name'] === 'org_admin' ? '#007bff' : '#28a745'); ?>;
                                    ">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role_name']))); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                                <?php if ($_SESSION['role'] === 'org_admin' && $user['role_name'] === 'employee'): ?>
                                    <td>
                                        <?php if ($progress): ?>
                                            <div class="progress-info">
                                                <!-- Pass Rate Progress Bar -->
                                                <div class="progress-bar-container" style="width: 100px; height: 15px; background-color: #f0f0f0; border-radius: 8px; overflow: hidden; margin-bottom: 3px;">
                                                    <div class="progress-bar" style="width: <?php echo $pass_rate; ?>%; height: 100%; background-color: <?php echo $pass_rate >= 80 ? '#28a745' : ($pass_rate >= 50 ? '#ffc107' : '#dc3545'); ?>; transition: width 0.3s ease;"></div>
                                                </div>
                                                <small style="font-size: 11px;">
                                                    <strong>Passed:</strong> <?php echo $progress['passed_quizzes']; ?>/<?php echo $progress['total_quizzes']; ?> (<?php echo $pass_rate; ?>%)
                                                    <?php if ($progress['attempted_quizzes'] > $progress['passed_quizzes']): ?>
                                                        <br><span style="color: #dc3545;">⚠️ <?php echo $progress['attempted_quizzes'] - $progress['passed_quizzes']; ?> failed</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #666;">No data</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($progress && $display_score): ?>
                                            <span class="score-badge" style="
                                                padding: 4px 8px; 
                                                border-radius: 12px; 
                                                font-weight: bold; 
                                                color: white;
                                                background-color: <?php echo $display_score >= 70 ? '#28a745' : ($display_score >= 60 ? '#ffc107' : '#dc3545'); ?>;
                                            ">
                                                <?php echo $display_score; ?>%
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #666;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($_SESSION['role'] === 'org_admin'): ?>
                                    <!-- Org admins don't take quizzes, so show empty cells -->
                                    <td style="color: #999; font-style: italic;">N/A (Admin)</td>
                                    <td style="color: #999; font-style: italic;">N/A (Admin)</td>
                                <?php endif; ?>
                                <td><?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                                    <td>
                                        <?php if ($user['role_name'] === 'org_admin'): ?>
                                            <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')" 
                                                    class="btn-delete">
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="protected-text">
                                                <?php echo $user['role_name'] === 'system_admin' ? 'System Admin' : 'Protected'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" action="user_management.php" style="display: none;">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>

    <script>
        function confirmDelete(userId, userName) {
            if (confirm('Are you sure you want to delete the organization admin "' + userName + '"?\n\nThis action cannot be undone.')) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Auto-submit form when organization filter changes
        document.getElementById('org_filter')?.addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>