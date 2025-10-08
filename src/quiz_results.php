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
    if ($user_id != $_SESSION['user_id'] && !in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
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
    <title>Quiz Results - <?php echo htmlspecialchars($results['quiz_title']); ?> - SA SMME Cybersecurity Platform</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
    <style>
        .results-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .passed {
            color: #28a745;
        }
        
        .failed {
            color: #dc3545;
        }
        
        .result-title {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary-dark);
        }
        
        .result-subtitle {
            font-size: 1.2em;
            color: var(--text-medium);
            margin-bottom: 30px;
        }
        
        .score-display {
            font-size: 3.5em;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .score-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .score-item {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, var(--light-cream), var(--cream-bg));
            border-radius: 8px;
            border: 1px solid rgba(212, 98, 26, 0.1);
        }
        
        .score-number {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .score-label {
            font-size: 0.9em;
            color: var(--text-medium);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .passing-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            font-size: 1.1em;
        }
        
        .question-review {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .question-review h3 {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--light-cream);
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.3em;
        }
        
        .question-item {
            border-bottom: 1px solid #f1f3f4;
            padding: 20px 0;
        }
        
        .question-item:last-child {
            border-bottom: none;
        }
        
        .question-text {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-dark);
            font-size: 1.1em;
        }
        
        .answer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .answer-correct {
            color: #28a745;
            font-weight: 600;
        }
        
        .answer-incorrect {
            color: #dc3545;
            font-weight: 600;
        }
        
        .explanation-box {
            background: linear-gradient(135deg, #e3f2fd, #f0f9ff);
            border-left: 4px solid var(--accent-gold);
            padding: 15px 20px;
            margin-top: 15px;
            border-radius: 6px;
        }
        
        .explanation-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
            display: block;
        }
        
        .explanation-text {
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-medium), var(--primary-dark));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }
        
        @media (max-width: 768px) {
            .results-card {
                padding: 25px;
            }
            
            .score-display {
                font-size: 2.5em;
            }
            
            .result-title {
                font-size: 2em;
            }
            
            .score-details {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            
            .answer-row {
                flex-direction: column;
                align-items: flex-start;
            }
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
                <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                    <?php if ($_SESSION['role'] === 'system_admin'): ?>
                        <a href="organization_management.php">Organizations</a>
                    <?php endif; ?>
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
                <h2 class="page-title">Quiz Results</h2>
                <p class="page-subtitle">Your cybersecurity assessment results</p>
            </div>
        </div>
        <!-- Results Summary -->
        <div class="form-card">
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
                    <small style="color: var(--text-medium);">Completed: <?php echo date('M j, Y g:i A', strtotime($results['completed_at'])); ?></small>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Question Review (if available) -->
        <?php if (isset($results['question_results'])): ?>
            <div class="form-card">
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
                            
                            <?php if (!empty($question['explanation'])): ?>
                                <div class="explanation-box">
                                    <span class="explanation-label">💡 Explanation:</span>
                                    <div class="explanation-text"><?php echo htmlspecialchars($question['explanation']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="form-card">
            <div class="actions">
                <?php if (!$results['passed']): ?>
                    <a href="take_quiz.php?id=<?php echo $results['quiz_id']; ?>" class="btn btn-success">Retake Quiz</a>
                <?php endif; ?>
                <a href="quiz_list.php" class="btn btn-primary">Back to Quizzes</a>
                <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>