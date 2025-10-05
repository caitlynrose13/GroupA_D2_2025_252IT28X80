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
 * Check if user has completed all prerequisite quizzes for a given cycle
 */
function hasCompletedPrerequisites($pdo, $user_id, $current_month) {
    // For month 1, no prerequisites needed
    if ($current_month <= 1) {
        return true;
    }
    
    // Check if user has passed all previous months
    for ($month = 1; $month < $current_month; $month++) {
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
            'month_number' => $month
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no attempt found or didn't pass, prerequisites not met
        if (!$result || !$result['passed']) {
            return false;
        }
    }
    
    return true; // All prerequisites met
}

/**
 * Get prerequisite status message for a quiz
 */
function getPrerequisiteMessage($pdo, $user_id, $quiz_id) {
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
    
    $missing_months = [];
    for ($month = 1; $month < $quiz['month_number']; $month++) {
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
            'month_number' => $month
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result || !$result['passed']) {
            $missing_months[] = $month;
        }
    }
    
    if (!empty($missing_months)) {
        return "You must complete and pass Month " . implode(', ', $missing_months) . " quiz(s) first.";
    }
    
    return '';
}
?>