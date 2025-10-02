<?php
// filepath: src/dashboard.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Get user's organization info if applicable
$organization_name = null;
if ($_SESSION['organization_id']) {
    try {
        $org_stmt = $pdo->prepare("SELECT name FROM organizations WHERE id = :id LIMIT 1");
        $org_stmt->execute(['id' => $_SESSION['organization_id']]);
        $org = $org_stmt->fetch(PDO::FETCH_ASSOC);
        $organization_name = $org ? $org['name'] : 'Unknown Organization';
    } catch (PDOException $e) {
        $organization_name = 'Error loading organization';
    }
}

// Get some basic stats for the dashboard
try {
    // Count total content available to user
    $content_where = ($_SESSION['role'] === 'system_admin') ? '1=1' : 
                    '(organization_id IS NULL OR organization_id = :org_id)';
    $content_params = ($_SESSION['role'] === 'system_admin') ? [] : ['org_id' => $_SESSION['organization_id']];
    
    $content_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content WHERE is_active = 1 AND $content_where");
    $content_stmt->execute($content_params);
    $content_count = $content_stmt->fetchColumn();
    
    // Count quizzes
    $quiz_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quizzes WHERE $content_where");
    $quiz_stmt->execute($content_params);
    $quiz_count = $quiz_stmt->fetchColumn();
    
} catch (PDOException $e) {
    $content_count = 0;
    $quiz_count = 0;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cybersecurity Awareness</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: #f5f7fa;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 { margin: 0; font-size: 28px; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .nav-links a:hover { background: rgba(255,255,255,0.1); }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .action-btn {
            display: block;
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            border-radius: 6px;
            text-align: center;
            transition: background 0.2s;
        }
        .action-btn:hover { background: #2980b9; }
        .role-badge {
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .role-badge.system_admin { background: #9b59b6; }
        .role-badge.org_admin { background: #e67e22; }
        .role-badge.employee { background: #27ae60; }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛡️ Cybersecurity Awareness Platform</h1>
        <div class="user-info">
            <div class="nav-links">
                <a href="content_list.php">📚 Content</a>
                <a href="quiz_list.php">📝 Quizzes</a>
                <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                    <a href="content_upload.php">⬆️ Upload</a>
                <?php endif; ?>
            </div>
            <div>
                <strong><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></strong>
                <span class="role-badge <?php echo $_SESSION['role']; ?>">
                    <?php echo ucwords(str_replace('_', ' ', $_SESSION['role'])); ?>
                </span>
            </div>
            <a href="logout.php" style="color: #ecf0f1; text-decoration: none;">🚪 Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h2>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
            <p><strong>Role:</strong> <?php echo ucwords(str_replace('_', ' ', $_SESSION['role'])); ?></p>
            <?php if ($organization_name): ?>
                <p><strong>Organization:</strong> <?php echo htmlspecialchars($organization_name); ?></p>
            <?php else: ?>
                <p><strong>Access Level:</strong> Platform Administrator</p>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $content_count; ?></div>
                <div class="stat-label">Available Content</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $quiz_count; ?></div>
                <div class="stat-label">Available Quizzes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">12</div>
                <div class="stat-label">Program Months</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">3</div>
                <div class="stat-label">Program Cycles</div>
            </div>
        </div>

        <h3>📅 12-Month Program Overview</h3>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <?php foreach (PROGRAM_CYCLES as $cycle): ?>
                <div style="margin-bottom: 20px; padding: 15px; border-left: 4px solid #3498db; background: #f8f9fa;">
                    <h4><?php echo htmlspecialchars($cycle['title']); ?> (Months <?php echo $cycle['start_month']; ?>-<?php echo $cycle['end_month']; ?>)</h4>
                    <p><?php echo htmlspecialchars($cycle['description']); ?></p>
                    <small><em><?php echo htmlspecialchars($cycle['focus']); ?></em></small>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>🚀 Quick Actions</h3>
        <div class="quick-actions">
            <a href="content_list.php" class="action-btn">📚 Browse Content</a>
            <a href="quiz_list.php" class="action-btn">📝 Take Quizzes</a>
            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                <a href="content_upload.php" class="action-btn">⬆️ Upload Content</a>
                <a href="quiz_create.php" class="action-btn">➕ Create Quiz</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>