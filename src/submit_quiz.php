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
    // Get quiz details
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id AND is_active = 1 LIMIT 1");
    $stmt->execute(['id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        header('Location: quiz_list.php?error=' . urlencode('Quiz not found'));
        exit();
    }
    
    // Get quiz questions with correct answers
    $stmt = $pdo->prepare("
        SELECT id, question_text, correct_answer 
        FROM quiz_questions 
        WHERE quiz_id = :quiz_id 
        ORDER BY question_order ASC
    ");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($questions)) {
        header('Location: quiz_list.php?error=' . urlencode('Quiz has no questions'));
        exit();
    }
    
    // Calculate score
    $total_questions = count($questions);
    $correct_answers = 0;
    $question_results = [];
    
    foreach ($questions as $question) {
        $question_id = $question['id'];
        $user_answer = $answers[$question_id] ?? '';
        $correct_answer = $question['correct_answer'];
        $is_correct = ($user_answer === $correct_answer);
        
        if ($is_correct) {
            $correct_answers++;
        }
        
        $question_results[] = [
            'question_id' => $question_id,
            'question_text' => $question['question_text'],
            'user_answer' => $user_answer,
            'correct_answer' => $correct_answer,
            'is_correct' => $is_correct
        ];
    }
    
    $score = round(($correct_answers / $total_questions) * 100);
    $passed = ($score >= $quiz['passing_score']);
    
    // Store quiz result
    $stmt = $pdo->prepare("
        INSERT INTO quiz_results (quiz_id, user_id, score, correct_answers, total_questions, passed, completed_at) 
        VALUES (:quiz_id, :user_id, :score, :correct_answers, :total_questions, :passed, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        'quiz_id' => $quiz_id,
        'user_id' => $_SESSION['user_id'],
        'score' => $score,
        'correct_answers' => $correct_answers,
        'total_questions' => $total_questions,
        'passed' => $passed ? 1 : 0
    ]);
    
    // Store in session for results page
    $_SESSION['quiz_results'] = [
        'quiz_id' => $quiz_id,
        'quiz_title' => $quiz['title'],
        'score' => $score,
        'correct_answers' => $correct_answers,
        'total_questions' => $total_questions,
        'passed' => $passed,
        'passing_score' => $quiz['passing_score'],
        'question_results' => $question_results
    ];
    
    header('Location: quiz_results.php');
    exit();
    
} catch (PDOException $e) {
    error_log("Quiz submission error: " . $e->getMessage());
    header('Location: quiz_list.php?error=' . urlencode('Error submitting quiz. Please try again.'));
    exit();
}
?>