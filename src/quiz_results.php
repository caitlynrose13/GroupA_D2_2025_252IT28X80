<?php
// filepath: src/quiz_results.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Get results from session (just submitted) or from database
$results = $_SESSION['quiz_results'] ?? null;
$quiz_id = $_GET['quiz_id'] ?? ($results['quiz_id'] ?? '');

// If no session results, get from database (for admins viewing results)
if (!$results && $quiz_id) {
    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
    
    // Check if admin is viewing other user's results
    if ($user_id != $_SESSION['user_id'] && !in_array($_SESSION['role'], ['admin', 'org_admin'])) {
        header('Location: quiz_list.php?error=' . urlencode('Access denied'));
        exit();
    }
    
    try {
        // Get quiz and user details
        $stmt = $pdo->prepare("
            SELECT q.title, q.passing_score, qr.*, u.first_name, u.last_name
            FROM quiz_results qr
            JOIN quizzes q ON qr.quiz_id = q.id
            JOIN users u ON qr.user_id = u.id
            WHERE qr.quiz_id = :quiz_id AND qr.user_id = :user_id
            ORDER BY qr.completed_at DESC LIMIT 1
        ");
        $stmt->execute(['quiz_id' => $quiz_id, 'user_id' => $user_id]);
        $db_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($db_result) {
            $results = [
                'quiz_id' => $quiz_id,
                'quiz_title' => $db_result['title'],
                'score' => $db_result['score'],
                'correct_answers' => $db_result['correct_answers'],
                'total_questions' => $db_result['total_questions'],
                'passed' => $db_result['passed'],
                'passing_score' => $db_result['passing_score'],
                'completed_at' => $db_result['completed_at'],
                'user_name' => $db_result['first_name'] . ' ' . $db_result['last_name']
            ];
        }
    } catch (PDOException $e) {
        header('Location: quiz_list.php?error=' . urlencode('Error loading results'));
        exit();
    }
}

if (!$results) {
    header('Location: quiz_list.php');
    exit();
}

// Clear session results after displaying
if (isset($_SESSION['quiz_results'])) {
    unset($_SESSION['quiz_results']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($results['quiz_title']); ?> - South African SMME Cybersecurity Portal</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
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
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        .results-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .passed {
            color: #27ae60;
        }
        .failed {
            color: #e74c3c;
        }
        .result-title {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .result-subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }
        .score-display {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .score-details {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 30px;
        }
        .score-item {
            text-align: center;
        }
        .score-number {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }
        .score-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .passing-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .question-review {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .question-item {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        .question-item:last-child {
            border-bottom: none;
        }
        .question-text {
            font-weight: 500;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .answer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .answer-correct {
            color: #27ae60;
        }
        .answer-incorrect {
            color: #e74c3c;
        }
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover { background: #2980b9; }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover { background: #219a52; }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover { background: #7f8c8d; }
    </style>
</head>
<body>
    <div class="african-header">
        <div class="african-border"></div>
        <div class="african-header-content">
            <h1>📊 Quiz Results</h1>
            <p>Your cybersecurity assessment results</p>
        </div>
        <div class="african-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="quiz_list.php">Quiz Library</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="african-container">
        <!-- Results Summary -->
        <div class="results-card">
            <div class="result-icon <?php echo $results['passed'] ? 'passed' : 'failed'; ?>">
                <?php echo $results['passed'] ? '🎉' : '📚'; ?>
            </div>
            
            <div class="result-title <?php echo $results['passed'] ? 'passed' : 'failed'; ?>">
                <?php echo $results['passed'] ? 'Congratulations!' : 'Keep Learning!'; ?>
            </div>
            
            <div class="result-subtitle">
                <?php echo htmlspecialchars($results['quiz_title']); ?>
                <?php if (isset($results['user_name'])): ?>
                    <br><small>Results for: <?php echo htmlspecialchars($results['user_name']); ?></small>
                <?php endif; ?>
            </div>
            
            <div class="score-display <?php echo $results['passed'] ? 'passed' : 'failed'; ?>">
                <?php echo $results['score']; ?>%
            </div>
            
            <div class="score-details">
                <div class="score-item">
                    <div class="score-number"><?php echo $results['correct_answers']; ?></div>
                    <div class="score-label">Correct</div>
                </div>
                <div class="score-item">
                    <div class="score-number"><?php echo $results['total_questions'] - $results['correct_answers']; ?></div>
                    <div class="score-label">Incorrect</div>
                </div>
                <div class="score-item">
                    <div class="score-number"><?php echo $results['total_questions']; ?></div>
                    <div class="score-label">Total</div>
                </div>
            </div>
            
            <div class="passing-info">
                <strong>Passing Score:</strong> <?php echo $results['passing_score']; ?>% 
                <span class="<?php echo $results['passed'] ? 'passed' : 'failed'; ?>">
                    (<?php echo $results['passed'] ? '✅ Passed' : '❌ Failed'; ?>)
                </span>
            </div>
            
            <?php if (isset($results['completed_at'])): ?>
                <small>Completed: <?php echo date('M j, Y g:i A', strtotime($results['completed_at'])); ?></small>
            <?php endif; ?>
        </div>
        
        <!-- Question Review (if available) -->
        <?php if (isset($results['question_results'])): ?>
            <div class="question-review">
                <h3>Question Review</h3>
                <?php foreach ($results['question_results'] as $index => $question): ?>
                    <div class="question-item">
                        <div class="question-text">
                            <?php echo ($index + 1); ?>. <?php echo htmlspecialchars($question['question_text']); ?>
                        </div>
                        <div class="answer-row">
                            <span>Your Answer: <strong><?php echo htmlspecialchars($question['user_answer']); ?></strong></span>
                            <span class="<?php echo $question['is_correct'] ? 'answer-correct' : 'answer-incorrect'; ?>">
                                <?php echo $question['is_correct'] ? '✅ Correct' : '❌ Incorrect'; ?>
                            </span>
                        </div>
                        <?php if (!$question['is_correct']): ?>
                            <div class="answer-row">
                                <span>Correct Answer: <strong class="answer-correct"><?php echo htmlspecialchars($question['correct_answer']); ?></strong></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="actions">
            <?php if (!$results['passed']): ?>
                <a href="take_quiz.php?id=<?php echo $results['quiz_id']; ?>" class="btn btn-success">Retake Quiz</a>
            <?php endif; ?>
            <a href="quiz_list.php" class="btn btn-primary">Back to Quizzes</a>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
        </div>
    </div>
</body>
</html>