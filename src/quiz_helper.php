<?php
// filepath: src/quiz_helper.php
// Helper functions for professional quiz access control

/**
 * Check if a user can access a specific quiz based on prerequisites and scheduling
 */
function canUserAccessQuiz($pdo, $user_id, $quiz_id) {
    // Get quiz details
    $stmt = $pdo->prepare("
        SELECT id, month_number, status_id, release_date, requires_previous_completion
        FROM quizzes 
        WHERE id = :quiz_id AND is_active = 1
    ");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        return false; // Quiz doesn't exist or is inactive
    }
    
    // Validate month_number - deny access if invalid
    if (!$quiz['month_number'] || $quiz['month_number'] < 1 || $quiz['month_number'] > 12) {
        error_log("Quiz {$quiz_id} has invalid month_number: " . ($quiz['month_number'] ?? 'NULL'));
        return false; // Deny access to quizzes with invalid month data
    }
    
    // Calculate cycle from month number
    $cycle_info = getCycleByMonth($quiz['month_number']);
    if (!$cycle_info) {
        error_log("Cannot determine cycle for quiz {$quiz_id} with month_number {$quiz['month_number']}");
        return false; // Deny access if cycle cannot be determined
    }
    $cycle_number = $cycle_info['cycle_number'];
    
    // Check if quiz is published or scheduled and ready
    if ($quiz['status_id'] == 1) { // 1 = draft
        return false; // Draft quizzes are not accessible
    }
    
    if ($quiz['status_id'] == 2) { // 2 = scheduled
        $today = date('Y-m-d');
        if (!$quiz['release_date'] || $quiz['release_date'] > $today) {
            return false; // Not yet released
        }
    }
    
    // Check prerequisites if required
    if ($quiz['requires_previous_completion']) {
        return hasCompletedPrerequisites($pdo, $user_id, $cycle_number);
    }
    
    return true; // All checks passed
}

/**
 * Check if user has completed all prerequisite quizzes for a given cycle
 */
function hasCompletedPrerequisites($pdo, $user_id, $current_cycle) {
    // For cycle 1, no prerequisites needed
    if ($current_cycle <= 1) {
        return true;
    }
    
    // Check if user has passed all previous cycles
    // Cycle assessments are at months 4, 8, 12 (month_number = cycle * 4)
    for ($cycle = 1; $cycle < $current_cycle; $cycle++) {
        $assessment_month = $cycle * 4;
        
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
            'month_number' => $assessment_month
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
    
    // Calculate cycle from month number
    $cycle_info = getCycleByMonth($quiz['month_number']);
    $current_cycle = $cycle_info ? $cycle_info['cycle_number'] : 1;
    
    $missing_cycles = [];
    for ($cycle = 1; $cycle < $current_cycle; $cycle++) {
        $assessment_month = $cycle * 4;
        
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
            'month_number' => $assessment_month
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result || !$result['passed']) {
            $missing_cycles[] = $cycle;
        }
    }
    
    if (!empty($missing_cycles)) {
        return "You must complete and pass Cycle " . implode(', ', $missing_cycles) . " quiz(s) first.";
    }
    
    return '';
}
?>