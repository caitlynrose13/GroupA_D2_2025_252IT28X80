<?php
// filepath: src/quiz_helper.php
// Helper functions for professional quiz access control

/**
 * Check if a user can access a specific quiz based on prerequisites and scheduling
 */
function canUserAccessQuiz($pdo, $user_id, $quiz_id) {
    // Get quiz details
    $stmt = $pdo->prepare("
        SELECT id, cycle_number, month_number, status, release_date, requires_previous_completion
        FROM quizzes 
        WHERE id = :quiz_id AND is_active = 1
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
        return hasCompletedPrerequisites($pdo, $user_id, $quiz['cycle_number']);
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
    for ($cycle = 1; $cycle < $current_cycle; $cycle++) {
        $stmt = $pdo->prepare("
            SELECT qr.passed 
            FROM quiz_results qr
            JOIN quizzes q ON qr.quiz_id = q.id
            WHERE qr.user_id = :user_id AND q.cycle_number = :cycle_number
            ORDER BY qr.completed_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'cycle_number' => $cycle
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
        SELECT cycle_number, requires_previous_completion
        FROM quizzes 
        WHERE id = :quiz_id
    ");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz || !$quiz['requires_previous_completion']) {
        return '';
    }
    
    $missing_cycles = [];
    for ($cycle = 1; $cycle < $quiz['cycle_number']; $cycle++) {
        $stmt = $pdo->prepare("
            SELECT qr.passed 
            FROM quiz_results qr
            JOIN quizzes q ON qr.quiz_id = q.id
            WHERE qr.user_id = :user_id AND q.cycle_number = :cycle_number
            ORDER BY qr.completed_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'cycle_number' => $cycle
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