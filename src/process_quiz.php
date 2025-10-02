<?php
// filepath: src/process_quiz.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: quiz_create.php');
    exit();
}

// Get quiz data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$cycle_number = !empty($_POST['cycle_number']) ? (int)$_POST['cycle_number'] : null;
$month_number = !empty($_POST['month_number']) ? (int)$_POST['month_number'] : null;
$status = $_POST['status'] ?? 'published';
$release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
$requires_previous_completion = isset($_POST['requires_previous_completion']) ? 1 : 0;
$questions = $_POST['questions'] ?? [];

// Validate required fields
if (empty($title) || empty($cycle_number) || empty($month_number) || empty($questions)) {
    header('Location: quiz_create.php?error=' . urlencode('Please fill in all required fields and add at least one question'));
    exit();
}

// Validate scheduling logic
if ($status === 'scheduled' && empty($release_date)) {
    header('Location: quiz_create.php?error=' . urlencode('Scheduled quizzes must have a release date'));
    exit();
}

if ($status === 'scheduled' && strtotime($release_date) < time()) {
    header('Location: quiz_create.php?error=' . urlencode('Release date cannot be in the past'));
    exit();
}

// Validate questions
foreach ($questions as $index => $question) {
    if (empty($question['text']) || empty($question['type']) || empty($question['correct'])) {
        header('Location: quiz_create.php?error=' . urlencode('All questions must have text, type, and correct answer'));
        exit();
    }
    
    // Validate multiple choice options
    if ($question['type'] === 'multiple_choice') {
        if (empty($question['option_a']) || empty($question['option_b']) || 
            empty($question['option_c']) || empty($question['option_d'])) {
            header('Location: quiz_create.php?error=' . urlencode('Multiple choice questions must have all four options'));
            exit();
        }
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert quiz
    $stmt = $pdo->prepare("
        INSERT INTO quizzes (title, description, cycle_number, month_number, status, release_date, requires_previous_completion, is_active, created_at) 
        VALUES (:title, :description, :cycle_number, :month_number, :status, :release_date, :requires_previous_completion, 1, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'cycle_number' => $cycle_number,
        'month_number' => $month_number,
        'status' => $status,
        'release_date' => $release_date,
        'requires_previous_completion' => $requires_previous_completion
    ]);
    
    $quiz_id = $pdo->lastInsertId();
    
    // Insert questions
    $stmt = $pdo->prepare("
        INSERT INTO quiz_questions (quiz_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, question_order) 
        VALUES (:quiz_id, :question_text, :question_type, :option_a, :option_b, :option_c, :option_d, :correct_answer, :question_order)
    ");
    
    foreach ($questions as $index => $question) {
        $questionData = [
            'quiz_id' => $quiz_id,
            'question_text' => $question['text'],
            'question_type' => $question['type'],
            'option_a' => $question['option_a'] ?? null,
            'option_b' => $question['option_b'] ?? null,
            'option_c' => $question['option_c'] ?? null,
            'option_d' => $question['option_d'] ?? null,
            'correct_answer' => $question['correct'],
            'question_order' => (int)$question['order']
        ];
        
        $stmt->execute($questionData);
    }
    
    // Commit transaction
    $pdo->commit();
    
    header('Location: quiz_create.php?success=' . urlencode('Quiz created successfully with ' . count($questions) . ' questions!'));
    exit();
    
} catch (PDOException $e) {
    // Rollback transaction
    $pdo->rollBack();
    error_log("Quiz creation error: " . $e->getMessage());
    header('Location: quiz_create.php?error=' . urlencode('Database error. Please try again.'));
    exit();
}
?>