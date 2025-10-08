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
$content_count = 0;
$quiz_count = 0;
$user_count = 0;

try {
    $role = $_SESSION['role'];
    $org_id = $_SESSION['organization_id'];

    // Define WHERE clause and parameters based on role
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

    // Count users for administrators
    if (in_array($role, ['system_admin', 'org_admin'])) {
        $user_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1 AND $user_where");
        $user_stmt->execute($user_params);
        $user_count = $user_stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    // Log error but maintain clean dashboard display
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
    <style>
        .stat-note {
            font-size: 0.8em;
            color: #666;
            margin-top: 4px;
            font-style: italic;
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
                <a href="content_list.php">Content</a>
                <a href="quiz_list.php">Quizzes</a>
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    <a href="organization_management.php">Organizations</a>
                    <a href="user_management.php">Users</a>
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

        <!-- Role-based statistics display -->
        <!-- Provides relevant metrics for each user type -->
        <?php if ($role === 'employee'): ?>
            <?php
                // Get REAL employee progress data based on actual interactions
                $content_accessed = 0;
                $quizzes_passed = 0;
                $total_quizzes = 0;
                $months_completed = 0;
                $avg_quiz_score = 0;
                
                try {
                    // Count unique content pieces accessed (viewed or downloaded)
                    $content_stmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT content_id) as count 
                        FROM content_access_logs 
                        WHERE user_id = :user_id
                    ");
                    $content_stmt->execute(['user_id' => $_SESSION['user_id']]);
                    $content_accessed = $content_stmt->fetchColumn() ?: 0;
                    
                    // Count total available quizzes for this user
                    $total_quiz_stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM quizzes q
                        WHERE (q.organization_id IS NULL OR q.organization_id = :org_id)
                    ");
                    $total_quiz_stmt->execute(['org_id' => $_SESSION['organization_id']]);
                    $total_quizzes = $total_quiz_stmt->fetchColumn() ?: 0;
                    
                    // Count UNIQUE quizzes passed (only count each quiz once, even if passed multiple times)
                    $quiz_stmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT qr.quiz_id) as count 
                        FROM quiz_results qr 
                        JOIN quizzes q ON qr.quiz_id = q.id 
                        WHERE qr.user_id = :user_id AND qr.passed = 1
                        AND (q.organization_id IS NULL OR q.organization_id = :org_id)
                    ");
                    $quiz_stmt->execute(['user_id' => $_SESSION['user_id'], 'org_id' => $_SESSION['organization_id']]);
                    $quizzes_passed = $quiz_stmt->fetchColumn() ?: 0;
                    
                    // Get average quiz score for passed quizzes (best attempt per quiz)
                    $score_stmt = $pdo->prepare("
                        SELECT AVG(best_scores.max_percentage) as avg_score 
                        FROM (
                            SELECT qr.quiz_id, MAX(qr.percentage) as max_percentage
                            FROM quiz_results qr
                            JOIN quizzes q ON qr.quiz_id = q.id
                            WHERE qr.user_id = :user_id AND qr.passed = 1
                            AND (q.organization_id IS NULL OR q.organization_id = :org_id)
                            GROUP BY qr.quiz_id
                        ) best_scores
                    ");
                    $score_stmt->execute(['user_id' => $_SESSION['user_id'], 'org_id' => $_SESSION['organization_id']]);
                    $avg_score_result = $score_stmt->fetch();
                    $avg_quiz_score = $avg_score_result && $avg_score_result['avg_score'] ? round($avg_score_result['avg_score'], 0) : 0;
                    
                    // Calculate months completed based on REAL criteria:
                    // A month is "completed" if user has:
                    // 1. Accessed the content for that month AND
                    // 2. For assessment months (4,8,12): passed the quiz
                    // 3. For content months: just accessed the content
                    
                    $months_completed_stmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT c.month_number) as completed_months
                        FROM content c
                        JOIN content_access_logs cal ON c.id = cal.content_id
                        WHERE cal.user_id = :user_id 
                        AND c.month_number IS NOT NULL
                        AND (
                            -- For assessment months, user must have passed the quiz
                            (c.month_number IN (4, 8, 12) AND EXISTS (
                                SELECT 1 FROM quiz_results qr 
                                JOIN quizzes q ON qr.quiz_id = q.id 
                                WHERE qr.user_id = :user_id 
                                AND q.month_number = c.month_number 
                                AND qr.passed = 1
                            ))
                            OR
                            -- For content months, just need to access content
                            (c.month_number NOT IN (4, 8, 12))
                        )
                    ");
                    $months_completed_stmt->execute(['user_id' => $_SESSION['user_id']]);
                    $months_completed = $months_completed_stmt->fetchColumn() ?: 0;
                    
                } catch (PDOException $e) {
                    // Keep zeros as fallback values
                    error_log("Dashboard progress error: " . $e->getMessage());
                }
            ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $content_accessed; ?>/<?php echo $content_count; ?></div>
                    <div class="stat-label">Content Accessed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $months_completed; ?>/12</div>
                    <div class="stat-label">Months Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $quizzes_passed; ?>/<?php echo $total_quizzes; ?></div>
                    <div class="stat-label">Quizzes Passed</div>
                    <?php if ($total_quizzes > $quizzes_passed): ?>
                        <div class="stat-note"><?php echo $total_quizzes - $quizzes_passed; ?> remaining</div>
                    <?php endif; ?>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $avg_quiz_score; ?>%</div>
                    <div class="stat-label">Best Quiz Average</div>
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
                        // Get REALISTIC completion rate for organization based on actual interactions
                        $completion_rate = 0;
                        try {
                            // Calculate average completion rate based on real employee interactions
                            // For each employee: (content_accessed + quizzes_passed) / (total_content + total_quizzes) * 100
                            $comp_stmt = $pdo->prepare("
                                SELECT 
                                    AVG(
                                        CASE 
                                            WHEN total_available > 0 THEN 
                                                ((content_accessed + quizzes_passed) * 100.0 / total_available)
                                            ELSE 0 
                                        END
                                    ) as avg_completion
                                FROM (
                                    SELECT 
                                        u.id,
                                        COALESCE(content_accessed.count, 0) as content_accessed,
                                        COALESCE(quizzes_passed.count, 0) as quizzes_passed,
                                        (
                                            SELECT COUNT(*) FROM content WHERE (organization_id IS NULL OR organization_id = :org_id) AND is_active = 1
                                        ) + (
                                            SELECT COUNT(*) FROM quizzes WHERE (organization_id IS NULL OR organization_id = :org_id)
                                        ) as total_available
                                    FROM users u
                                    LEFT JOIN (
                                        SELECT user_id, COUNT(DISTINCT content_id) as count
                                        FROM content_access_logs cal
                                        JOIN content c ON cal.content_id = c.id
                                        WHERE (c.organization_id IS NULL OR c.organization_id = :org_id)
                                        GROUP BY user_id
                                    ) content_accessed ON u.id = content_accessed.user_id
                                    LEFT JOIN (
                                        SELECT user_id, COUNT(*) as count
                                        FROM quiz_results qr
                                        JOIN quizzes q ON qr.quiz_id = q.id
                                        WHERE qr.passed = 1 AND (q.organization_id IS NULL OR q.organization_id = :org_id)
                                        GROUP BY user_id
                                    ) quizzes_passed ON u.id = quizzes_passed.user_id
                                    WHERE u.organization_id = :org_id 
                                    AND u.is_active = 1 
                                    AND u.role_id = (SELECT id FROM roles WHERE name = 'employee')
                                ) employee_progress
                            ");
                            $comp_stmt->execute(['org_id' => $_SESSION['organization_id']]);
                            $comp_result = $comp_stmt->fetch();
                            $completion_rate = $comp_result && $comp_result['avg_completion'] ? round($comp_result['avg_completion'], 0) : 0;
                        } catch (PDOException $e) {
                            error_log("Org completion rate error: " . $e->getMessage());
                            $completion_rate = 0;
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