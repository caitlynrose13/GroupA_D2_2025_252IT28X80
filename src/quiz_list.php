<?php
// filepath: src/quiz_list.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/quiz_helper.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Handle delete action (admin/org_admin only)
if (isset($_GET['delete']) && in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    $delete_id = $_GET['delete'];
    
    try {
        // Security check: ensure user can only delete quizzes they have access to
        if ($_SESSION['role'] === 'system_admin') {
            $security_where = '1=1';
            $security_params = ['id' => $delete_id];
        } else {
            $security_where = '(organization_id IS NULL OR organization_id = :org_id)';
            $security_params = ['id' => $delete_id, 'org_id' => $_SESSION['organization_id']];
        }
        
        // Check if quiz exists and user has access
        $check_stmt = $pdo->prepare("SELECT id FROM quizzes WHERE id = :id AND $security_where LIMIT 1");
        $check_stmt->execute($security_params);
        
        if (!$check_stmt->fetch()) {
            header('Location: quiz_list.php?error=' . urlencode('Quiz not found or access denied'));
            exit();
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete quiz question answers first
        $stmt = $pdo->prepare("DELETE FROM quiz_question_answers WHERE question_id IN (SELECT id FROM quiz_questions WHERE quiz_id = :id)");
        $stmt->execute(['id' => $delete_id]);
        
        // Delete quiz questions
        $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE quiz_id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        // Delete quiz results
        $stmt = $pdo->prepare("DELETE FROM quiz_results WHERE quiz_id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        // Delete quiz attempts
        $stmt = $pdo->prepare("DELETE FROM quiz_attempts WHERE quiz_id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        // Delete quiz
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = :id AND $security_where");
        $stmt->execute($security_params);
        
        $pdo->commit();
        header('Location: quiz_list.php?success=' . urlencode('Quiz deleted successfully'));
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: quiz_list.php?error=' . urlencode('Error deleting quiz'));
        exit();
    }
}

// Get filter parameters
$filter_cycle = $_GET['cycle'] ?? '';
$filter_month = $_GET['month'] ?? '';

// Build query with filters and multi-tenant security
$where_conditions = ['q.is_active = 1'];
$params = [];

// Multi-tenant security
if ($_SESSION['role'] === 'system_admin') {
    // System admin can see all quizzes
} else {
    // Regular users can only see global quizzes + their organization's quizzes
    $where_conditions[] = '(q.organization_id IS NULL OR q.organization_id = :org_id)';
    $params['org_id'] = $_SESSION['organization_id'];
}

// Add filters
if (!empty($filter_month)) {
    $where_conditions[] = 'q.month_number = :month';
    $params['month'] = $filter_month;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get quizzes with question count and organization info
    $stmt = $pdo->prepare("
        SELECT q.*, 
               COUNT(qq.id) as question_count,
               COUNT(DISTINCT qr.user_id) as attempts_count,
               o.name as organization_name,
               qs.name as status_name
        FROM quizzes q 
        LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
        LEFT JOIN quiz_results qr ON q.id = qr.quiz_id
        LEFT JOIN organizations o ON q.organization_id = o.id
        LEFT JOIN quiz_statuses qs ON q.status_id = qs.id
        WHERE $where_clause 
        GROUP BY q.id
        ORDER BY q.month_number ASC, q.created_at DESC
    ");
    $stmt->execute($params);
    $quiz_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $quiz_list = [];
    $error = "Error loading quizzes: " . $e->getMessage();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Library - South African SMME Cybersecurity Portal</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: var(--light-cream);
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { margin: 0; font-size: 24px; }
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
            margin: 40px auto;
            padding: 20px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }
        .filter-group {
            flex: 1;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
        }
        .filter-btn, .clear-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .filter-btn {
            background: #3498db;
            color: white;
        }
        .clear-btn {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .quiz-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .quiz-card:hover {
            transform: translateY(-5px);
        }
        .quiz-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .quiz-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .quiz-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .meta-badge {
            background: #ecf0f1;
            color: #555;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .meta-badge.cycle {
            background: #3498db;
            color: white;
        }
        .meta-badge.month {
            background: #9b59b6;
            color: white;
        }
        .quiz-stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .stat {
            font-size: 14px;
            color: #666;
        }
        .quiz-description {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }
        .quiz-actions {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
        }
        .take-quiz-btn, .view-btn, .delete-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            text-align: center;
        }
        .take-quiz-btn {
            background: #27ae60;
            color: white;
            flex: 1;
        }
        .take-quiz-btn:hover { background: #219a52; }
        .view-btn {
            background: #3498db;
            color: white;
        }
        .view-btn:hover { background: #2980b9; }
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        .delete-btn:hover { background: #c0392b; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="african-header">
        <div class="african-border"></div>
        <div class="african-header-content">
            <h1>🎯 Quiz Library</h1>
            <p>Test your cybersecurity knowledge</p>
        </div>
        <div class="african-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <?php if (in_array($_SESSION['role'], ['admin', 'org_admin'])): ?>
                <a href="quiz_create.php">Create Quiz</a>
            <?php endif; ?>
            <a href="content_list.php">Content Library</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="african-container">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="african-card african-filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="cycle">Cycle</label>
                        <select id="cycle" name="cycle">
                            <option value="">All Cycles</option>
                            <option value="1" <?php echo $filter_cycle === '1' ? 'selected' : ''; ?>>Cycle 1</option>
                            <option value="2" <?php echo $filter_cycle === '2' ? 'selected' : ''; ?>>Cycle 2</option>
                            <option value="3" <?php echo $filter_cycle === '3' ? 'selected' : ''; ?>>Cycle 3</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="month">Month</label>
                        <select id="month" name="month">
                            <option value="">All Months</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filter_month === (string)$i ? 'selected' : ''; ?>>Month <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="filter-btn">Filter</button>
                        <a href="quiz_list.php" class="clear-btn">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quiz Grid -->
        <?php if (empty($quiz_list)): ?>
            <div class="empty-state">
                <h3>No quizzes found</h3>
                <p>Try adjusting your filters or create some quizzes to get started.</p>
                <?php if (in_array($_SESSION['role'], ['admin', 'org_admin'])): ?>
                    <p><a href="quiz_create.php" style="color: #3498db;">Create your first quiz</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="quiz-grid">
                <?php foreach ($quiz_list as $quiz): ?>
                    <div class="quiz-card">
                        <div class="quiz-header">
                            <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
                            <div class="quiz-meta">
                                <?php if ($quiz['month_number']): ?>
                                    <span class="meta-badge month">Month <?php echo $quiz['month_number']; ?></span>
                                <?php endif; ?>
                                <span class="meta-badge"><?php echo $quiz['question_count']; ?> Questions</span>
                                
                                <?php if (in_array($_SESSION['role'], ['admin', 'org_admin'])): ?>
                                    <!-- Admin sees status information -->
                                    <?php 
                                    $status_color = [
                                        'draft' => '#95a5a6',
                                        'scheduled' => '#f39c12', 
                                        'published' => '#27ae60'
                                    ][$quiz['status']] ?? '#27ae60';
                                    ?>
                                    <span class="meta-badge" style="background: <?php echo $status_color; ?>; color: white;">
                                        <?php echo ucfirst($quiz['status']); ?>
                                    </span>
                                    
                                    <?php if ($quiz['status'] === 'scheduled' && $quiz['release_date']): ?>
                                        <span class="meta-badge">Releases: <?php echo date('M j, Y', strtotime($quiz['release_date'])); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Employee sees access status -->
                                    <?php 
                                    $can_access = canUserAccessQuiz($pdo, $_SESSION['user_id'], $quiz['id']);
                                    $has_completed = false;
                                    $completion_info = null;
                                    
                                    if ($can_access) {
                                        // Check if user has completed this quiz
                                        $completion_stmt = $pdo->prepare("
                                            SELECT score, percentage, passed, completed_at
                                            FROM quiz_results 
                                            WHERE user_id = :user_id AND quiz_id = :quiz_id
                                            ORDER BY completed_at DESC
                                            LIMIT 1
                                        ");
                                        $completion_stmt->execute([
                                            'user_id' => $_SESSION['user_id'],
                                            'quiz_id' => $quiz['id']
                                        ]);
                                        $completion_info = $completion_stmt->fetch(PDO::FETCH_ASSOC);
                                        $has_completed = !empty($completion_info);
                                    }
                                    ?>
                                    
                                    <?php if ($can_access): ?>
                                        <?php if ($has_completed): ?>
                                            <span class="meta-badge" style="background: <?php echo $completion_info['passed'] ? '#27ae60' : '#e74c3c'; ?>; color: white;">
                                                <?php echo $completion_info['passed'] ? 'Passed' : 'Failed'; ?> (<?php echo $completion_info['percentage']; ?>%)
                                            </span>
                                        <?php else: ?>
                                            <span class="meta-badge" style="background: #3498db; color: white;">Available</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="meta-badge" style="background: #95a5a6; color: white;">Not Available</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="quiz-stats">
                                <?php if (in_array($_SESSION['role'], ['admin', 'org_admin'])): ?>
                                    <span class="stat">📝 <?php echo $quiz['attempts_count'] ?? 0; ?> attempts</span>
                                <?php endif; ?>
                                <span class="stat">🎯 <?php echo $quiz['passing_score']; ?>% to pass</span>
                                
                                <?php if ($quiz['requires_previous_completion']): ?>
                                    <span class="stat">🔒 Prerequisites required</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($quiz['description']): ?>
                                <div class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="quiz-actions">
                            <?php if (in_array($_SESSION['role'], ['admin', 'org_admin'])): ?>
                                <!-- Admin actions -->
                                <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="take-quiz-btn">Preview Quiz</a>
                                <a href="quiz_results.php?id=<?php echo $quiz['id']; ?>" class="view-btn">View Results</a>
                                <a href="quiz_list.php?delete=<?php echo $quiz['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this quiz? This will also delete all results.')">Delete</a>
                            <?php else: ?>
                                <!-- Employee actions -->
                                <?php 
                                $can_access = canUserAccessQuiz($pdo, $_SESSION['user_id'], $quiz['id']);
                                $prerequisite_msg = getPrerequisiteMessage($pdo, $_SESSION['user_id'], $quiz['id']);
                                ?>
                                
                                <?php if ($can_access): ?>
                                    <?php if ($has_completed): ?>
                                        <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="take-quiz-btn">Retake Quiz</a>
                                        <a href="quiz_results.php?id=<?php echo $quiz['id']; ?>&user=<?php echo $_SESSION['user_id']; ?>" class="view-btn">View Results</a>
                                    <?php else: ?>
                                        <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="take-quiz-btn">Start Quiz</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="take-quiz-btn" style="background: #95a5a6; cursor: not-allowed;" disabled>
                                        Not Available
                                    </button>
                                    <?php if ($prerequisite_msg): ?>
                                        <small style="color: #e74c3c; display: block; margin-top: 5px; font-size: 12px;">
                                            <?php echo $prerequisite_msg; ?>
                                        </small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>