<?php
// filepath: src/take_quiz.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';
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
    // Get quiz details with multi-tenant security
    if ($_SESSION['role'] === 'system_admin') {
        // System admin can access all quizzes
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id AND is_active = 1 LIMIT 1");
        $stmt->execute(['id' => $quiz_id]);
    } else {
        // Regular users can only access global quizzes + their organization's quizzes
        $stmt = $pdo->prepare("
            SELECT * FROM quizzes 
            WHERE id = :id AND is_active = 1 
            AND (organization_id IS NULL OR organization_id = :org_id)
            LIMIT 1
        ");
        $stmt->execute(['id' => $quiz_id, 'org_id' => $_SESSION['organization_id']]);
    }
    
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        header('Location: quiz_list.php?error=' . urlencode('Quiz not found or access denied'));
        exit();
    }
    
    // Professional access control - only allow if user has permission (except for admins)
    if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
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
    
    // Check if user has already taken this quiz (not applicable for admins in preview mode)
    $previous_attempt = null;
    $is_admin_preview = in_array($_SESSION['role'], ['system_admin', 'org_admin']);
    
    if (!$is_admin_preview) {
        $stmt = $pdo->prepare("
            SELECT * FROM quiz_results 
            WHERE quiz_id = :quiz_id AND user_id = :user_id 
            ORDER BY completed_at DESC LIMIT 1
        ");
        $stmt->execute(['quiz_id' => $quiz_id, 'user_id' => $_SESSION['user_id']]);
        $previous_attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
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
    <title><?php echo htmlspecialchars($quiz['title']); ?> - South African SMME Cybersecurity Portal</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <div class="african-border"></div>
    <div class="header">
        <div class="header-left">
            <h1><?php echo $is_admin_preview ? '👁️ Preview Quiz' : '📝 Take Quiz'; ?></h1>
        </div>
        <div class="header-right">
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="quiz_list.php">Back to Quizzes</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Quiz Information -->
        <div class="quiz-info-card">
            <h1 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <div class="quiz-badges">
                <?php 
                $cycle = getCycleByMonth($quiz['month_number']);
                if ($cycle): ?>
                    <span class="badge badge-month">Cycle <?php echo $cycle['cycle_number']; ?></span>
                <?php endif; ?>
                <?php if ($quiz['month_number']): ?>
                    <span class="badge badge-month">Month <?php echo $quiz['month_number']; ?></span>
                <?php endif; ?>
                <span class="badge badge-type"><?php echo count($questions); ?> Questions</span>
            </div>
            
            <?php if ($quiz['description']): ?>
                <div class="quiz-description"><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></div>
            <?php endif; ?>
            
            <div class="quiz-stats-grid">
                <div class="quiz-stat">
                    <span class="stat-icon">📝</span>
                    <span class="stat-text"><?php echo count($questions); ?> questions</span>
                </div>
                <div class="quiz-stat">
                    <span class="stat-icon">🎯</span>
                    <span class="stat-text"><?php echo $quiz['passing_score']; ?>% to pass</span>
                </div>
                <div class="quiz-stat">
                    <span class="stat-icon">⏱️</span>
                    <span class="stat-text">
                        <?php if ($quiz['time_limit_minutes']): ?>
                            <?php echo $quiz['time_limit_minutes']; ?> minutes
                            <div id="timer" class="quiz-timer">Time remaining: <span id="time-display"><?php echo $quiz['time_limit_minutes']; ?>:00</span></div>
                        <?php else: ?>
                            No time limit
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($previous_attempt): ?>
                <div class="previous-attempt-card <?php echo $previous_attempt['score'] >= $quiz['passing_score'] ? 'passed' : 'failed'; ?>">
                    <div class="attempt-header">
                        <strong>Previous Attempt:</strong> 
                        <span class="attempt-score"><?php echo $previous_attempt['score']; ?>%</span>
                        <span class="attempt-status">
                            <?php echo $previous_attempt['score'] >= $quiz['passing_score'] ? '✅ Passed' : '❌ Failed'; ?>
                        </span>
                    </div>
                    <div class="attempt-details">
                        <?php echo $previous_attempt['correct_answers']; ?>/<?php echo $previous_attempt['total_questions']; ?> correct
                        • Completed: <?php echo date('M j, Y g:i A', strtotime($previous_attempt['completed_at'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quiz Form -->
        <div class="quiz-form-card">
            <?php if ($is_admin_preview): ?>
                <div class="message warning">
                    <strong>📋 Admin Preview Mode:</strong> You are viewing this quiz in preview mode. 
                    <?php echo $_SESSION['role'] === 'org_admin' ? 'Organization administrators' : 'System administrators'; ?> 
                    cannot submit quiz answers. This preview allows you to review the quiz content and questions.
                </div>
            <?php elseif ($previous_attempt && $previous_attempt['score'] >= $quiz['passing_score']): ?>
                <div class="message warning">
                    <strong>Note:</strong> You have already passed this quiz with <?php echo $previous_attempt['score']; ?>%. 
                    You can retake it, but your highest score will be recorded.
                </div>
            <?php endif; ?>
            
            <?php if ($is_admin_preview): ?>
                <!-- Admin Preview - Questions without form submission -->
                <?php foreach ($questions as $index => $question): ?>
                    <div class="quiz-question-card preview-mode">
                        <div class="question-header-bar">
                            <span class="question-number-badge">Question <?php echo $index + 1; ?></span>
                        </div>
                        <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                        
                        <div class="question-options">
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <div class="quiz-option preview-option">
                                    <span class="option-letter">A)</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_a']); ?></span>
                                    <?php if ($question['correct_answer'] === 'A'): ?>
                                        <span class="correct-indicator">✓ Correct Answer</span>
                                    <?php endif; ?>
                                </div>
                                <div class="quiz-option preview-option">
                                    <span class="option-letter">B)</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_b']); ?></span>
                                    <?php if ($question['correct_answer'] === 'B'): ?>
                                        <span class="correct-indicator">✓ Correct Answer</span>
                                    <?php endif; ?>
                                </div>
                                <div class="quiz-option preview-option">
                                    <span class="option-letter">C)</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_c']); ?></span>
                                    <?php if ($question['correct_answer'] === 'C'): ?>
                                        <span class="correct-indicator">✓ Correct Answer</span>
                                    <?php endif; ?>
                                </div>
                                <div class="quiz-option preview-option">
                                    <span class="option-letter">D)</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_d']); ?></span>
                                    <?php if ($question['correct_answer'] === 'D'): ?>
                                        <span class="correct-indicator">✓ Correct Answer</span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($question['question_type'] === 'true_false'): ?>
                                <div class="true-false-options preview-tf">
                                    <div class="tf-option preview-option">
                                        <span class="option-text">True</span>
                                        <?php if ($question['correct_answer'] === 'TRUE'): ?>
                                            <span class="correct-indicator">✓ Correct Answer</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tf-option preview-option">
                                        <span class="option-text">False</span>
                                        <?php if ($question['correct_answer'] === 'FALSE'): ?>
                                            <span class="correct-indicator">✓ Correct Answer</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="form-actions">
                    <a href="quiz_list.php" class="btn-secondary">Back to Quiz List</a>
                </div>
            <?php else: ?>
                <!-- Regular Quiz Form for Employees -->
                <form method="POST" action="submit_quiz.php" id="quizForm">
                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="quiz-question-card">
                            <div class="question-header-bar">
                                <span class="question-number-badge">Question <?php echo $index + 1; ?></span>
                            </div>
                            <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                            
                            <div class="question-options">
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <div class="quiz-option">
                                        <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="A" id="q<?php echo $question['id']; ?>_a" required>
                                        <label for="q<?php echo $question['id']; ?>_a">A) <?php echo htmlspecialchars($question['option_a']); ?></label>
                                    </div>
                                    <div class="quiz-option">
                                        <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="B" id="q<?php echo $question['id']; ?>_b" required>
                                        <label for="q<?php echo $question['id']; ?>_b">B) <?php echo htmlspecialchars($question['option_b']); ?></label>
                                    </div>
                                    <div class="quiz-option">
                                        <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="C" id="q<?php echo $question['id']; ?>_c" required>
                                        <label for="q<?php echo $question['id']; ?>_c">C) <?php echo htmlspecialchars($question['option_c']); ?></label>
                                    </div>
                                    <div class="quiz-option">
                                        <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="D" id="q<?php echo $question['id']; ?>_d" required>
                                        <label for="q<?php echo $question['id']; ?>_d">D) <?php echo htmlspecialchars($question['option_d']); ?></label>
                                    </div>
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <div class="true-false-options">
                                        <div class="tf-option">
                                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="TRUE" id="q<?php echo $question['id']; ?>_true" required>
                                            <label for="q<?php echo $question['id']; ?>_true" class="tf-label tf-true">True</label>
                                        </div>
                                        <div class="tf-option">
                                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="FALSE" id="q<?php echo $question['id']; ?>_false" required>
                                            <label for="q<?php echo $question['id']; ?>_false" class="tf-label tf-false">False</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary submit-quiz-btn">Submit Quiz</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Quiz timer functionality
        <?php if (!$is_admin_preview && $quiz['time_limit_minutes']): ?>
        let timeRemaining = <?php echo $quiz['time_limit_minutes']; ?> * 60; // Convert to seconds
        const timerDisplay = document.getElementById('time-display');
        const quizForm = document.getElementById('quizForm');
        
        function updateTimer() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color when time is running low
            if (timeRemaining <= 300) { // 5 minutes
                timerDisplay.style.color = '#e74c3c';
                timerDisplay.style.fontWeight = 'bold';
            } else if (timeRemaining <= 600) { // 10 minutes
                timerDisplay.style.color = '#f39c12';
                timerDisplay.style.fontWeight = 'bold';
            }
            
            if (timeRemaining <= 0) {
                alert('Time is up! Your quiz will be submitted automatically.');
                quizForm.submit();
                return;
            }
            
            timeRemaining--;
        }
        
        // Start the timer
        const timerInterval = setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        // Form validation (only for regular quiz taking, not preview mode)
        <?php if (!$is_admin_preview): ?>
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            // Clear timer when submitting
            <?php if ($quiz['time_limit_minutes']): ?>
            clearInterval(timerInterval);
            <?php endif; ?>
            
            const questions = document.querySelectorAll('.quiz-question-card');
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
        <?php endif; ?>
    </script>
                return false;
            }
        });
    </script>
</body>
</html>