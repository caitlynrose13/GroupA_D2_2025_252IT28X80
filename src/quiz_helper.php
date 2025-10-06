<?php
// filepath: src/quiz_helper.php
// Helper functions for professional quiz access control

/**
 * Check if a user can access a specific quiz based on prerequisites and scheduling
 */
function canUserAccessQuiz($pdo, $user_id, $quiz_id) {
    // Get quiz details
    $stmt = $pdo->prepare("
        SELECT q.id, q.month_number, q.release_date, q.requires_previous_completion, qs.name as status
        FROM quizzes q
        LEFT JOIN quiz_statuses qs ON q.status_id = qs.id
        WHERE q.id = :quiz_id AND q.is_active = 1
    ");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        return false; // Quiz doesn't exist or is inactive
    }
    
    // Check if quiz is published or scheduled and ready
    if ($quiz['status'] === 'draft') {
        return false; // Draft quizzes are not accessible
    }
    
    if ($quiz['status'] === 'scheduled') {
        $today = date('Y-m-d');
        if (!$quiz['release_date'] || $quiz['release_date'] > $today) {
            return false; // Not yet released
        }
    }
    
    // Check prerequisites if required
    if ($quiz['requires_previous_completion']) {
        return hasCompletedPrerequisites($pdo, $user_id, $quiz['month_number']);
    }
    
    return true; // All checks passed
}

/**
 * Check if user has completed prerequisite quiz for the current cycle
 * Only checks the previous cycle's assessment quiz (month 4, 8, or 12)
 */
function hasCompletedPrerequisites($pdo, $user_id, $current_month) {
    require_once __DIR__ . '/config/program_structure.php';
    
    // Get the current cycle
    $current_cycle = getCycleByMonth($current_month);
    if (!$current_cycle) {
        // If month is not in known cycles, allow access (fail open for flexibility)
        return true;
    }
    
    // Cycle 1 has no prerequisites
    if ($current_cycle['cycle_number'] == 1) {
        return true;
    }
    
    // Get the previous cycle's quiz month
    $previous_cycle_number = $current_cycle['cycle_number'] - 1;
    $previous_cycle = PROGRAM_CYCLES[$previous_cycle_number] ?? null;
    if (!$previous_cycle) {
        // If no previous cycle found, allow access (fail open)
        return true;
    }
    
    $prerequisite_month = $previous_cycle['quiz_month'];
    
    // Check if user passed the previous cycle's assessment quiz
    $stmt = $pdo->prepare("
        SELECT qr.passed 
        FROM quiz_results qr
        JOIN quizzes q ON qr.quiz_id = q.id
        WHERE qr.user_id = :user_id AND q.month_number = :month_number
        ORDER BY qr.completed_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'month_number' => $prerequisite_month
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // User must have passed the prerequisite quiz
    return ($result && $result['passed']);
}

/**
 * Get prerequisite status message for a quiz
 * Returns a user-friendly message about which cycle assessment needs to be completed
 */
function getPrerequisiteMessage($pdo, $user_id, $quiz_id) {
    require_once __DIR__ . '/config/program_structure.php';
    
    $stmt = $pdo->prepare("
        SELECT month_number, requires_previous_completion
        FROM quizzes 
        WHERE id = :quiz_id
    ");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz || !$quiz['requires_previous_completion']) {
        return '';
    }
    
    // Get the current cycle
    $current_cycle = getCycleByMonth($quiz['month_number']);
    if (!$current_cycle || $current_cycle['cycle_number'] == 1) {
        return '';
    }
    
    // Get the previous cycle information
    $previous_cycle_number = $current_cycle['cycle_number'] - 1;
    $previous_cycle = PROGRAM_CYCLES[$previous_cycle_number] ?? null;
    if (!$previous_cycle) {
        return '';
    }
    
    // Check if user passed the prerequisite
    if (!hasCompletedPrerequisites($pdo, $user_id, $quiz['month_number'])) {
        $cycle_title = $previous_cycle['title'] ?? "Cycle " . $previous_cycle_number;
        return "You must complete and pass the " . $cycle_title . " assessment quiz (Month " . $previous_cycle['quiz_month'] . ") first.";
    }
    
    return '';
}
?>