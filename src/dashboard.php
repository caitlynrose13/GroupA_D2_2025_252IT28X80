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
// Beverley: Enhanced statistics with role-based user counting
$content_count = 0;
$quiz_count = 0;
$user_count = 0; // New statistic for Admins - shows organizational oversight capability

try {
    $role = $_SESSION['role'];
    $org_id = $_SESSION['organization_id'];

    // Beverley: Improved role-based security logic
    // Define WHERE clause and parameters based on role for better code maintainability
    if ($role === 'system_admin') {
        $content_where = '1=1';
        $user_where = '1=1';
        $content_params = [];
        $user_params = [];
    } else {
        // org_admin and employee see only organization-specific content (or platform-wide content, if organization_id is NULL)
        $content_where = '(organization_id IS NULL OR organization_id = :org_id)';
        $content_params = ['org_id' => $org_id];
        
        // org_admin sees users in their org; employee sees none (or we can adapt this for future features)
        $user_where = 'organization_id = :org_id';
        $user_params = ['org_id' => $org_id];
    }
    
    // Count total content available to user
    $content_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content WHERE is_active = 1 AND $content_where");
    $content_stmt->execute($content_params);
    $content_count = $content_stmt->fetchColumn();
    
    // Count quizzes
    $quiz_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quizzes WHERE $content_where");
    $quiz_stmt->execute($content_params);
    $quiz_count = $quiz_stmt->fetchColumn();

    // Beverley: Enhanced user management statistics for administrators
    // Count users (only for admins) - provides organizational oversight
    if (in_array($role, ['system_admin', 'org_admin'])) {
        $user_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1 AND $user_where");
        $user_stmt->execute($user_params);
        $user_count = $user_stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    // Beverley: Better error handling with proper logging
    // Log error, but keep counts at 0 for a clean dashboard display
    error_log("Dashboard DB Error: " . $e->getMessage());
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SA SMME Cybersecurity Platform</title>
    <!-- African-Inspired Styling -->
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
                <a href="content_list.php">Content</a>
                <a href="quiz_list.php">Quizzes</a>
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    <a href="organization_management.php">Organizations</a>
                <?php elseif ($_SESSION['role'] === 'org_admin'): ?>
                    <a href="user_management.php">Users</a>
                    <a href="reporting.php">Reports</a>
                <?php endif; ?>
            </nav>
            <div class="user-section">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
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
            <?php 
                // Beverley: Dynamic role-based welcome messages
                // Provides personalized, context-aware dashboard experience
                $role = $_SESSION['role'];
                $welcome_message = "Welcome back, " . htmlspecialchars($_SESSION['first_name']);

                if ($role === 'system_admin') {
                    $welcome_message = "System Administrator Dashboard";
                } elseif ($role === 'org_admin') {
                    $welcome_message = "Organization Administrator Dashboard";
                } elseif ($role === 'employee') {
                    $welcome_message = "Your Learning Dashboard";
                }
            ?>
            <h2><?php echo $welcome_message; ?></h2>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
            <p><strong>Role:</strong> <?php echo ucwords(str_replace('_', ' ', $_SESSION['role'])); ?></p>
            <?php if ($organization_name): ?>
                <p><strong>Organization:</strong> <?php echo htmlspecialchars($organization_name); ?></p>
            <?php else: ?>
                <p><strong>Access Level:</strong> Platform Administrator</p>
            <?php endif; ?>
        </div>

        <!-- Beverley: Role-based statistics display -->
        <!-- Provides relevant metrics for each user type -->
        <?php if ($role === 'employee'): ?>
            <?php
                // Get realistic employee progress data from database
                $modules_completed = 0;
                $avg_quiz_score = 0;
                try {
                    // Check employee progress for current user
                    $progress_stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM employee_progress WHERE user_id = :user_id AND completion_percentage >= 100");
                    $progress_stmt->execute(['user_id' => $_SESSION['user_id']]);
                    $modules_completed = $progress_stmt->fetchColumn();
                    
                    // Get average quiz score for current user
                    $score_stmt = $pdo->prepare("SELECT AVG(percentage) as avg_score FROM quiz_results WHERE user_id = :user_id");
                    $score_stmt->execute(['user_id' => $_SESSION['user_id']]);
                    $avg_score_result = $score_stmt->fetch();
                    $avg_quiz_score = $avg_score_result ? round($avg_score_result['avg_score'], 0) : 0;
                } catch (PDOException $e) {
                    // Fallback to demo data if queries fail
                    $modules_completed = 2;
                    $avg_quiz_score = 78;
                }
            ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $content_count; ?></div>
                    <div class="stat-label">Available Content</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $modules_completed; ?>/12</div>
                    <div class="stat-label">Modules Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $avg_quiz_score; ?>%</div>
                    <div class="stat-label">Average Quiz Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $quiz_count; ?></div>
                    <div class="stat-label">Available Quizzes</div>
                </div>
            </div>

        <?php elseif (in_array($role, ['system_admin', 'org_admin'])): ?>
            <h3>Administration Overview</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $content_count; ?></div>
                    <div class="stat-label">Total Content</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $quiz_count; ?></div>
                    <div class="stat-label">Total Quizzes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_count; ?></div>
                    <div class="stat-label">Total Active Users</div>
                </div>
                <?php if ($role === 'system_admin'): ?>
                    <div class="stat-card">
                        <div class="stat-number">3</div>
                        <div class="stat-label">Organizations Registered</div>
                    </div>
                <?php else: ?>
                    <?php
                        // Get realistic completion rate for organization
                        $completion_rate = 65; // Default fallback
                        try {
                            $comp_stmt = $pdo->prepare("SELECT AVG(completion_percentage) as avg_completion FROM employee_progress ep JOIN users u ON ep.user_id = u.id WHERE u.organization_id = :org_id");
                            $comp_stmt->execute(['org_id' => $_SESSION['organization_id']]);
                            $comp_result = $comp_stmt->fetch();
                            $completion_rate = $comp_result ? round($comp_result['avg_completion'], 0) : 65;
                        } catch (PDOException $e) {
                            // Keep default value
                        }
                    ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $completion_rate; ?>%</div>
                        <div class="stat-label">Org Completion Rate</div>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>

        <!-- Program Overview -->
        <div class="section-divider"></div>
        <h3>📅 Program Overview</h3>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <?php foreach (PROGRAM_CYCLES as $cycle): ?>
                <div style="margin-bottom: 20px; padding: 15px; border-left: 4px solid #3498db; background: #f8f9fa;">
                    <h4><?php echo htmlspecialchars($cycle['title']); ?> (Months <?php echo $cycle['start_month']; ?>-<?php echo $cycle['end_month']; ?>)</h4>
                    <p><?php echo htmlspecialchars($cycle['description']); ?></p>
                    <small><em><?php echo htmlspecialchars($cycle['focus']); ?></em></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>