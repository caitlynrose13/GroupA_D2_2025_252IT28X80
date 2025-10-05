<?php
// filepath: src/submit_quiz.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: quiz_list.php');
    exit();
}

$quiz_id = $_POST['quiz_id'] ?? '';
$answers = $_POST['answers'] ?? [];

if (empty($quiz_id) || empty($answers)) {
    header('Location: quiz_list.php?error=' . urlencode('Invalid quiz submission'));
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Get quiz details
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id AND is_active = 1 LIMIT 1");
    $stmt->execute(['id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        $pdo->rollBack();
        header('Location: quiz_list.php?error=' . urlencode('Quiz not found'));
        exit();
    }
    
    // Get quiz questions with correct answers and points
    $stmt = $pdo->prepare("
        SELECT id, question_text, correct_answer, points 
        FROM quiz_questions 
        WHERE quiz_id = :quiz_id 
        ORDER BY question_order ASC
    ");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($questions)) {
        $pdo->rollBack();
        header('Location: quiz_list.php?error=' . urlencode('Quiz has no questions'));
        exit();
    }
    
    // Determine attempt number for this user
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(attempt_number), 0) + 1 as next_attempt
        FROM quiz_attempts 
        WHERE user_id = :user_id AND quiz_id = :quiz_id
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'quiz_id' => $quiz_id]);
    $attempt_number = $stmt->fetchColumn();
    
    // Get assessment_statuses id for 'completed'
    $stmt = $pdo->prepare("SELECT id FROM assessment_statuses WHERE name = 'completed' LIMIT 1");
    $stmt->execute();
    $completed_status_id = $stmt->fetchColumn();
    
    // Create quiz attempt record
    $start_time = new DateTime();
    $start_time->modify('-5 minutes');
    $stmt = $pdo->prepare("
        INSERT INTO quiz_attempts (user_id, quiz_id, attempt_number, status_id, started_at, completed_at, time_taken_minutes)
        VALUES (:user_id, :quiz_id, :attempt_number, :status_id, :started_at, CURRENT_TIMESTAMP, 5)
    ");
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'quiz_id' => $quiz_id,
        'attempt_number' => $attempt_number,
        'status_id' => $completed_status_id,
        'started_at' => $start_time->format('Y-m-d H:i:s')
    ]);
    $attempt_id = $pdo->lastInsertId();
    
    // Calculate score and store individual answers
    $total_questions = count($questions);
    $correct_answers = 0;
    $total_points = 0;
    $earned_points = 0;
    $question_results = [];
    
    foreach ($questions as $question) {
        $question_id = $question['id'];
        $user_answer = $answers[$question_id] ?? '';
        $correct_answer = $question['correct_answer'];
        $is_correct = ($user_answer === $correct_answer);
        $points = $question['points'] ?? 1;
        $total_points += $points;
        
        if ($is_correct) {
            $correct_answers++;
            $earned_points += $points;
        }
        
        // Store individual answer
        $stmt = $pdo->prepare("
            INSERT INTO quiz_question_answers (attempt_id, question_id, user_answer, is_correct, points_earned, answered_at)
            VALUES (:attempt_id, :question_id, :user_answer, :is_correct, :points_earned, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'attempt_id' => $attempt_id,
            'question_id' => $question_id,
            'user_answer' => $user_answer,
            'is_correct' => $is_correct ? 1 : 0,
            'points_earned' => $is_correct ? $points : 0
        ]);
        
        $question_results[] = [
            'question_id' => $question_id,
            'question_text' => $question['question_text'],
            'user_answer' => $user_answer,
            'correct_answer' => $correct_answer,
            'is_correct' => $is_correct
        ];
    }
    
    $percentage = round(($correct_answers / $total_questions) * 100, 2);
    $passed = ($percentage >= $quiz['passing_score']);
    
    // Store quiz result
    $stmt = $pdo->prepare("
        INSERT INTO quiz_results (attempt_id, quiz_id, user_id, score, correct_answers, total_questions, percentage, passed, completed_at) 
        VALUES (:attempt_id, :quiz_id, :user_id, :score, :correct_answers, :total_questions, :percentage, :passed, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        'attempt_id' => $attempt_id,
        'quiz_id' => $quiz_id,
        'user_id' => $_SESSION['user_id'],
        'score' => $earned_points,
        'correct_answers' => $correct_answers,
        'total_questions' => $total_questions,
        'percentage' => $percentage,
        'passed' => $passed ? 1 : 0
    ]);
    
    $pdo->commit();
    
    // Store in session for results page
    $_SESSION['quiz_results'] = [
        'quiz_id' => $quiz_id,
        'quiz_title' => $quiz['title'],
        'score' => $earned_points,
        'correct_answers' => $correct_answers,
        'total_questions' => $total_questions,
        'percentage' => $percentage,
        'passed' => $passed,
        'passing_score' => $quiz['passing_score'],
        'attempt_number' => $attempt_number,
        'question_results' => $question_results
    ];
    
    header('Location: quiz_results.php');
    exit();
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Quiz submission error: " . $e->getMessage());
    header('Location: quiz_list.php?error=' . urlencode('Error submitting quiz. Please try again.'));
    exit();
}
?>