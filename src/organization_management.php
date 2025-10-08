
<?php
// filepath: src/organization_management.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in and is system admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'system_admin') {
    header('Location: dashboard.php?error=' . urlencode('Access denied. System administrator permission required.'));
    exit();
}

// Get organizations with user counts and statistics
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            COUNT(DISTINCT u.id) as total_users,
            COUNT(DISTINCT CASE WHEN r.name = 'org_admin' THEN u.id END) as admin_count,
            COUNT(DISTINCT CASE WHEN r.name = 'employee' THEN u.id END) as employee_count,
            COUNT(DISTINCT CASE WHEN es.name = 'pending_approval' THEN u.id END) as pending_count,
            MAX(u.last_login) as last_activity
        FROM organizations o
        LEFT JOIN users u ON o.id = u.organization_id AND u.is_active = 1
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN employee_statuses es ON u.status_id = es.id
        WHERE o.is_active = 1
        GROUP BY o.id
        ORDER BY o.name
    ");
    $stmt->execute();
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $organizations = [];
    $error = "Error loading organizations: " . $e->getMessage();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Management - SA SMME Cybersecurity Platform</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
    <style>
        .organizations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .org-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-dark);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .org-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .org-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-cream);
        }
        .org-name {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 0;
        }
        .org-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .status-active { background-color: var(--accent-gold); }
        .status-inactive { background-color: var(--accent-red); }
        .org-details {
            margin-bottom: 15px;
        }
        .org-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.9em;
        }
        .org-detail-label {
            color: var(--text-medium);
            font-weight: 500;
        }
        .org-detail-value {
            color: var(--text-dark);
            font-weight: bold;
        }
        .org-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .stat-item {
            text-align: center;
            padding: 12px;
            background: linear-gradient(135deg, var(--light-cream), var(--cream-bg));
            border-radius: 6px;
            border: 1px solid rgba(212, 98, 26, 0.1);
        }
        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 0;
        }
        .stat-label {
            font-size: 0.8em;
            color: var(--text-medium);
            margin-top: 2px;
        }
        .pending-highlight {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
            border: 1px solid #f39c12 !important;
        }
        .pending-highlight .stat-number {
            color: #856404 !important;
        }
        .pending-highlight .stat-label {
            color: #856404 !important;
        }
        .org-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--light-cream);
            flex-wrap: wrap;
        }
        .btn-action {
            flex: 1;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            min-width: 100px;
        }
        .btn-users {
            background: linear-gradient(135deg, var(--accent-orange), var(--pattern-orange));
            color: white;
        }
        .btn-users:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            transform: translateY(-1px);
        }
        .btn-quizzes {
            background: linear-gradient(135deg, var(--accent-gold), var(--pattern-gold));
            color: white;
        }
        .btn-quizzes:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            transform: translateY(-1px);
        }
        .btn-edit {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        .btn-edit:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            transform: translateY(-1px);
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-card h4 {
            margin: 0 0 10px 0;
            font-size: 0.9em;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-card .number {
            font-size: 2.5em;
            font-weight: bold;
            margin: 0;
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
                <a href="organization_management.php">Organizations</a>
                <a href="user_management.php">Users</a>
            </nav>
            <div class="user-section">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header-section">
            <div class="page-title-area">
                <h2 class="page-title">Organization Management</h2>
                <p class="page-subtitle">Manage organizations and monitor their cybersecurity training programs</p>
            </div>
            <div class="page-actions">
                <a href="create_organization.php" class="btn btn-primary">
                    + Create Organization
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Platform Overview Statistics -->
        <div class="summary-cards">
            <div class="summary-card">
                <h4>Organizations</h4>
                <div class="number"><?php echo count($organizations); ?></div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, var(--accent-orange), var(--pattern-orange));">
                <h4>Total Users</h4>
                <div class="number"><?php echo array_sum(array_column($organizations, 'total_users')); ?></div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, var(--accent-gold), var(--pattern-gold));">
                <h4>Administrators</h4>
                <div class="number"><?php echo array_sum(array_column($organizations, 'admin_count')); ?></div>
            </div>
            <?php if (array_sum(array_column($organizations, 'pending_count')) > 0): ?>
                <div class="summary-card" style="background: linear-gradient(135deg, var(--accent-red), #c44a3a);">
                    <h4>Pending Approvals</h4>
                    <div class="number"><?php echo array_sum(array_column($organizations, 'pending_count')); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Organizations Grid -->
        <div class="form-card">
            <h3 style="margin-bottom: 25px; color: var(--primary-dark); border-bottom: 2px solid var(--light-cream); padding-bottom: 10px;">
                <i class="fas fa-building" style="margin-right: 10px; color: var(--accent-orange);"></i>
                Organizations Overview
            </h3>
            
            <?php if (empty($organizations)): ?>
                <div class="info-card" style="text-align: center; padding: 40px;">
                    <h3>No organizations found</h3>
                    <p>There are currently no active organizations to display.</p>
                </div>
            <?php else: ?>
                <div class="organizations-grid">
                    <?php foreach ($organizations as $org): ?>
                        <div class="org-card">
                            <div class="org-header">
                                <h3 class="org-name"><?php echo htmlspecialchars($org['name']); ?></h3>
                                <span class="org-status status-<?php echo $org['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $org['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            
                            <div class="org-details">
                                <div class="org-detail-item">
                                    <span class="org-detail-label">Created:</span>
                                    <span class="org-detail-value"><?php echo date('M d, Y', strtotime($org['created_at'])); ?></span>
                                </div>
                                <?php if ($org['last_activity']): ?>
                                    <div class="org-detail-item">
                                        <span class="org-detail-label">Last Activity:</span>
                                        <span class="org-detail-value"><?php echo date('M d, Y', strtotime($org['last_activity'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="org-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $org['total_users']; ?></div>
                                    <div class="stat-label">Total Users</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $org['admin_count']; ?></div>
                                    <div class="stat-label">Admins</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $org['employee_count']; ?></div>
                                    <div class="stat-label">Employees</div>
                                </div>
                                <?php if ($org['pending_count'] > 0): ?>
                                    <div class="stat-item pending-highlight">
                                        <div class="stat-number"><?php echo $org['pending_count']; ?></div>
                                        <div class="stat-label">Pending</div>
                                    </div>
                                <?php else: ?>
                                    <div class="stat-item">
                                        <div class="stat-number">0</div>
                                        <div class="stat-label">Pending</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="org-actions">
                                <a href="user_management.php?org_filter=<?php echo $org['id']; ?>" class="btn-action btn-users">
                                    👥 Manage Users
                                </a>
                                <a href="quiz_list.php?org_filter=<?php echo $org['id']; ?>" class="btn-action btn-quizzes">
                                    📋 View Quizzes
                                </a>
                                <a href="edit_organization.php?id=<?php echo $org['id']; ?>" class="btn-action btn-edit">
                                    ✏️ Edit Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>