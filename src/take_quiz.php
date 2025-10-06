<?php
// filepath: src/take_quiz.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/quiz_helper.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

$quiz_id = $_GET['id'] ?? '';
if (empty($quiz_id)) {
    header('Location: quiz_list.php');
    exit();
}

try {
    // Get quiz details
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id AND is_active = 1 LIMIT 1");
    $stmt->execute(['id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        header('Location: quiz_list.php?error=' . urlencode('Quiz not found'));
        exit();
    }
    
    // Professional access control - only allow if user has permission (except for admins)
    if (!in_array($_SESSION['role'], ['admin', 'org_admin'])) {
        if (!canUserAccessQuiz($pdo, $_SESSION['user_id'], $quiz_id)) {
            $prerequisite_msg = getPrerequisiteMessage($pdo, $_SESSION['user_id'], $quiz_id);
            $error_msg = $prerequisite_msg ?: 'This quiz is not currently available to you.';
            header('Location: quiz_list.php?error=' . urlencode($error_msg));
            exit();
        }
    }
    
    // Get quiz questions
    $stmt = $pdo->prepare("
        SELECT * FROM quiz_questions 
        WHERE quiz_id = :quiz_id 
        ORDER BY question_order ASC
    ");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($questions)) {
        header('Location: quiz_list.php?error=' . urlencode('This quiz has no questions'));
        exit();
    }
    
    // Check if user has already taken this quiz
    $stmt = $pdo->prepare("
        SELECT * FROM quiz_results 
        WHERE quiz_id = :quiz_id AND user_id = :user_id 
        ORDER BY completed_at DESC LIMIT 1
    ");
    $stmt->execute(['quiz_id' => $quiz_id, 'user_id' => $_SESSION['user_id']]);
    $previous_attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    header('Location: quiz_list.php?error=' . urlencode('Error loading quiz'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - Take Quiz</title>
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
        .quiz-info {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .quiz-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .quiz-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .meta-badge {
            background: #ecf0f1;
            color: #555;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
        }
        .meta-badge.cycle {
            background: #3498db;
            color: white;
        }
        .quiz-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .quiz-stats {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .stat {
            font-size: 14px;
            color: #666;
        }
        .previous-attempt {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .previous-attempt.passed {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        .quiz-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .question-card {
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .question-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .question-number {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-right: 15px;
        }
        .question-text {
            font-size: 18px;
            font-weight: 500;
            color: #2c3e50;
        }
        .options {
            margin-top: 20px;
        }
        .option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .option input[type="radio"] {
            margin-right: 12px;
            transform: scale(1.2);
        }
        .option label {
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn {
            background: #27ae60;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 500;
            width: 100%;
            margin-top: 20px;
        }
        .submit-btn:hover {
            background: #219a52;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .required {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Take Quiz</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="quiz_list.php">Back to Quizzes</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Quiz Information -->
        <div class="quiz-info">
            <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
            <div class="quiz-meta">
                <span class="meta-badge cycle">Cycle <?php echo ceil($quiz['month_number'] / 4); ?></span>
                <?php if ($quiz['month_number']): ?>
                    <span class="meta-badge">Month <?php echo $quiz['month_number']; ?></span>
                <?php endif; ?>
                <span class="meta-badge"><?php echo count($questions); ?> Questions</span>
            </div>
            
            <?php if ($quiz['description']): ?>
                <div class="quiz-description"><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></div>
            <?php endif; ?>
            
            <div class="quiz-stats">
                <span class="stat">📝 <?php echo count($questions); ?> questions</span>
                <span class="stat">🎯 <?php echo $quiz['passing_score']; ?>% required to pass</span>
                <span class="stat">⏱️ No time limit</span>
            </div>
            
            <?php if ($previous_attempt): ?>
                <div class="previous-attempt <?php echo $previous_attempt['score'] >= $quiz['passing_score'] ? 'passed' : ''; ?>">
                    <strong>Previous Attempt:</strong> 
                    <?php echo $previous_attempt['score']; ?>% 
                    (<?php echo $previous_attempt['correct_answers']; ?>/<?php echo $previous_attempt['total_questions']; ?> correct)
                    - <?php echo $previous_attempt['score'] >= $quiz['passing_score'] ? '✅ Passed' : '❌ Failed'; ?>
                    <br>
                    <small>Completed: <?php echo date('M j, Y g:i A', strtotime($previous_attempt['completed_at'])); ?></small>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quiz Form -->
        <div class="quiz-form">
            <?php if ($previous_attempt && $previous_attempt['score'] >= $quiz['passing_score']): ?>
                <div class="warning">
                    <strong>Note:</strong> You have already passed this quiz with <?php echo $previous_attempt['score']; ?>%. 
                    You can retake it, but your highest score will be recorded.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="submit_quiz.php" id="quizForm">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <span class="question-number">Question <?php echo $index + 1; ?></span>
                            <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                        </div>
                        
                        <div class="options">
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="A" id="q<?php echo $question['id']; ?>_a" required>
                                    <label for="q<?php echo $question['id']; ?>_a">A) <?php echo htmlspecialchars($question['option_a']); ?></label>
                                </div>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="B" id="q<?php echo $question['id']; ?>_b" required>
                                    <label for="q<?php echo $question['id']; ?>_b">B) <?php echo htmlspecialchars($question['option_b']); ?></label>
                                </div>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="C" id="q<?php echo $question['id']; ?>_c" required>
                                    <label for="q<?php echo $question['id']; ?>_c">C) <?php echo htmlspecialchars($question['option_c']); ?></label>
                                </div>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="D" id="q<?php echo $question['id']; ?>_d" required>
                                    <label for="q<?php echo $question['id']; ?>_d">D) <?php echo htmlspecialchars($question['option_d']); ?></label>
                                </div>
                            <?php elseif ($question['question_type'] === 'true_false'): ?>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="TRUE" id="q<?php echo $question['id']; ?>_true" required>
                                    <label for="q<?php echo $question['id']; ?>_true">True</label>
                                </div>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="FALSE" id="q<?php echo $question['id']; ?>_false" required>
                                    <label for="q<?php echo $question['id']; ?>_false">False</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="submit-btn">Submit Quiz</button>
            </form>
        </div>
    </div>
    
    <script>
        // Form validation
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            const questions = document.querySelectorAll('.question-card');
            let allAnswered = true;
            
            questions.forEach(function(question) {
                const radios = question.querySelectorAll('input[type="radio"]');
                const answered = Array.from(radios).some(radio => radio.checked);
                
                if (!answered) {
                    allAnswered = false;
                }
            });
            
            if (!allAnswered) {
                e.preventDefault();
                alert('Please answer all questions before submitting.');
                return false;
            }
            
            // Confirm submission
            if (!confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>