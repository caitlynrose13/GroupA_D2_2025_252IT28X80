<?php
// filepath: src/quiz_list.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';
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
if (!empty($filter_cycle)) {
    // Map cycle to month range
    $cycle_ranges = [
        '1' => [1, 4],   // Cycle 1: Months 1-4
        '2' => [5, 8],   // Cycle 2: Months 5-8
        '3' => [9, 12]   // Cycle 3: Months 9-12
    ];
    
    if (isset($cycle_ranges[$filter_cycle])) {
        $where_conditions[] = 'q.month_number BETWEEN :cycle_start AND :cycle_end';
        $params['cycle_start'] = $cycle_ranges[$filter_cycle][0];
        $params['cycle_end'] = $cycle_ranges[$filter_cycle][1];
    }
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get quizzes with question count, organization info, and user completion status
    $stmt = $pdo->prepare("
        SELECT q.*, 
               COUNT(DISTINCT qq.id) as question_count,
               COUNT(DISTINCT qr.id) as attempts_count,
               o.name as organization_name,
               qs.name as status_name,
               user_results.best_score,
               user_results.attempts_by_user,
               user_results.completed,
               user_results.last_attempt_date
        FROM quizzes q 
        LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
        LEFT JOIN quiz_results qr ON q.id = qr.quiz_id
        LEFT JOIN organizations o ON q.organization_id = o.id
        LEFT JOIN quiz_statuses qs ON q.status_id = qs.id
        LEFT JOIN (
            SELECT 
                quiz_id,
                MAX(score) as best_score,
                COUNT(*) as attempts_by_user,
                MAX(CASE WHEN score >= (SELECT passing_score FROM quizzes WHERE id = quiz_id) THEN 1 ELSE 0 END) as completed,
                MAX(completed_at) as last_attempt_date
            FROM quiz_results 
            WHERE user_id = :current_user_id
            GROUP BY quiz_id
        ) user_results ON q.id = user_results.quiz_id
        WHERE $where_clause 
        GROUP BY q.id
        ORDER BY q.month_number ASC, q.created_at DESC
    ");
    $params['current_user_id'] = $_SESSION['user_id'];
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
    <title>Quiz Library - SA SMME Cybersecurity Platform</title>
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
                <h2 class="page-title">Quiz Library</h2>
                <p class="page-subtitle">Cybersecurity assessments for South African SMMEs</p>
            </div>
            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                <div class="page-actions">
                    <a href="quiz_create.php" class="btn-primary create-quiz-btn">
                        <span class="btn-icon">➕</span>
                        <span class="btn-text">Create New Quiz</span>
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

        <!-- Filters -->
        <div class="form-card" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px; color: var(--primary-dark);">Filter Quizzes</h3>
            <form method="GET" action="">
                <div class="african-filter-row">
                    <div class="african-filter-group">
                        <label for="cycle">Program Cycle</label>
                        <select id="cycle" name="cycle" class="african-select">
                            <option value="">All Cycles</option>
                            <option value="1" <?php echo $filter_cycle === '1' ? 'selected' : ''; ?>>Cycle 1 (Months 1-4)</option>
                            <option value="2" <?php echo $filter_cycle === '2' ? 'selected' : ''; ?>>Cycle 2 (Months 5-8)</option>
                            <option value="3" <?php echo $filter_cycle === '3' ? 'selected' : ''; ?>>Cycle 3 (Months 9-12)</option>
                        </select>
                    </div>
                    <div class="african-filter-actions">
                        <button type="submit" class="btn-primary african-btn-filter">
                            Apply Filters
                        </button>
                        <a href="quiz_list.php" class="btn-secondary african-btn-filter">
                            Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quiz Grid -->
        <?php if (empty($quiz_list)): ?>
            <div class="info-card" style="text-align: center; padding: 40px;">
                <h3>No quizzes found</h3>
                <p>Try adjusting your filters or create some quizzes to get started.</p>
                <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                    <div style="margin-top: 20px;">
                        <a href="quiz_create.php" class="btn-primary">Create your first quiz</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="quiz-grid">
                <?php foreach ($quiz_list as $quiz): ?>
                    <div class="quiz-card <?php echo $quiz['completed'] ? 'quiz-completed' : 'quiz-incomplete'; ?>">
                        <div class="quiz-header">
                            <div class="quiz-title-row">
                                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                <?php if ($quiz['completed']): ?>
                                    <span class="completion-indicator completed" title="Quiz Completed">✓</span>
                                <?php else: ?>
                                    <span class="completion-indicator incomplete" title="Quiz Not Completed">○</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="quiz-badges">
                                <?php if ($quiz['month_number']): ?>
                                    <span class="badge badge-month">Month <?php echo $quiz['month_number']; ?></span>
                                <?php endif; ?>
                                <span class="badge badge-questions"><?php echo $quiz['question_count']; ?> Questions</span>
                                
                                <?php if ($quiz['organization_name']): ?>
                                    <span class="badge badge-org">
                                        <?php echo htmlspecialchars($quiz['organization_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-global">Global</span>
                                <?php endif; ?>
                                
                                <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                                    <?php 
                                    $status_class = [
                                        'draft' => 'badge-draft',
                                        'scheduled' => 'badge-scheduled', 
                                        'published' => 'badge-published'
                                    ][$quiz['status_name']] ?? 'badge-published';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($quiz['status_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($quiz['description']): ?>
                                <p class="quiz-description">
                                    <?php echo htmlspecialchars($quiz['description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="quiz-meta">
                                <span>Passing Score: <?php echo $quiz['passing_score']; ?>%</span>
                                <?php if ($quiz['attempts_count'] > 0): ?>
                                    <span><?php echo $quiz['attempts_count']; ?> total attempts</span>
                                <?php endif; ?>
                                <?php if ($quiz['time_limit_minutes']): ?>
                                    <span><?php echo $quiz['time_limit_minutes']; ?> minutes</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- User Progress Section -->
                            <?php if ($quiz['attempts_by_user'] > 0): ?>
                                <div class="user-progress">
                                    <div class="progress-row">
                                        <span class="progress-label">Your Progress:</span>
                                        <span class="progress-details">
                                            <?php if ($quiz['completed']): ?>
                                                <strong style="color: #27ae60;">✓ Completed</strong> - Best Score: <?php echo $quiz['best_score']; ?>%
                                            <?php else: ?>
                                                <strong style="color: #e74c3c;">Incomplete</strong> - Best Score: <?php echo $quiz['best_score']; ?>%
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="progress-row">
                                        <span class="progress-label">Attempts:</span>
                                        <span><?php echo $quiz['attempts_by_user']; ?></span>
                                    </div>
                                    <?php if ($quiz['last_attempt_date']): ?>
                                        <div class="progress-row">
                                            <span class="progress-label">Last Attempt:</span>
                                            <span><?php echo date('M j, Y', strtotime($quiz['last_attempt_date'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="user-progress">
                                    <div class="progress-row">
                                        <span class="progress-label">Status:</span>
                                        <span class="not-attempted">Not Started</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="quiz-actions">
                            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                                <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-secondary">Preview</a>
                                <a href="quiz_results.php?id=<?php echo $quiz['id']; ?>" class="btn-secondary">Results</a>
                                <a href="quiz_list.php?delete=<?php echo $quiz['id']; ?>" 
                                   class="btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this quiz? This will also delete all related questions and results.')">
                                    Delete
                                </a>
                            <?php else: ?>
                                <?php if ($quiz['completed']): ?>
                                    <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-secondary">Retake Quiz</a>
                                    <a href="quiz_results.php?id=<?php echo $quiz['id']; ?>" class="btn-primary">View Results</a>
                                <?php elseif ($quiz['attempts_by_user'] > 0): ?>
                                    <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-primary">Continue Quiz</a>
                                    <a href="quiz_results.php?id=<?php echo $quiz['id']; ?>" class="btn-secondary">View Attempts</a>
                                <?php else: ?>
                                    <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-primary">Start Quiz</a>
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