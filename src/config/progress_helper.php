<?php

function updateEmployeeProgress($pdo, $user_id, $month_number) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_content
            FROM content
            WHERE month_number = :month_number 
            AND is_active = 1
            AND (organization_id IS NULL OR organization_id = (
                SELECT organization_id FROM users WHERE id = :user_id
            ))
        ");
        $stmt->execute(['month_number' => $month_number, 'user_id' => $user_id]);
        $content_total = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT content_id) as completed_content
            FROM content_access_logs
            WHERE user_id = :user_id 
            AND access_type = 'complete'
            AND content_id IN (
                SELECT id FROM content 
                WHERE month_number = :month_number 
                AND is_active = 1
                AND (organization_id IS NULL OR organization_id = (
                    SELECT organization_id FROM users WHERE id = :user_id2
                ))
            )
        ");
        $stmt->execute(['user_id' => $user_id, 'month_number' => $month_number, 'user_id2' => $user_id]);
        $content_completed = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT qr.passed
            FROM quiz_results qr
            JOIN quizzes q ON qr.quiz_id = q.id
            WHERE qr.user_id = :user_id 
            AND q.month_number = :month_number
            ORDER BY qr.completed_at DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $user_id, 'month_number' => $month_number]);
        $quiz_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $quiz_completed = $quiz_result ? 1 : 0;
        $quiz_passed = $quiz_result ? $quiz_result['passed'] : 0;
        
        $completion_percentage = 0;
        if ($content_total > 0) {
            $content_percent = ($content_completed / $content_total) * 70;
            $quiz_percent = $quiz_passed ? 30 : 0;
            $completion_percentage = $content_percent + $quiz_percent;
        } elseif ($quiz_completed) {
            $completion_percentage = $quiz_passed ? 100 : 0;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO employee_progress (user_id, month_number, content_completed, content_total, quiz_completed, quiz_passed, completion_percentage, last_activity, updated_at)
            VALUES (:user_id, :month_number, :content_completed, :content_total, :quiz_completed, :quiz_passed, :completion_percentage, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON CONFLICT(user_id, month_number) DO UPDATE SET
                content_completed = :content_completed,
                content_total = :content_total,
                quiz_completed = :quiz_completed,
                quiz_passed = :quiz_passed,
                completion_percentage = :completion_percentage,
                last_activity = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            'user_id' => $user_id,
            'month_number' => $month_number,
            'content_completed' => $content_completed,
            'content_total' => $content_total,
            'quiz_completed' => $quiz_completed,
            'quiz_passed' => $quiz_passed,
            'completion_percentage' => round($completion_percentage, 2)
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Progress update error: " . $e->getMessage());
        return false;
    }
}

function getEmployeeProgress($pdo, $user_id, $month_number = null) {
    try {
        if ($month_number) {
            $stmt = $pdo->prepare("
                SELECT * FROM employee_progress
                WHERE user_id = :user_id AND month_number = :month_number
            ");
            $stmt->execute(['user_id' => $user_id, 'month_number' => $month_number]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM employee_progress
                WHERE user_id = :user_id
                ORDER BY month_number ASC
            ");
            $stmt->execute(['user_id' => $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Get progress error: " . $e->getMessage());
        return null;
    }
}

function getOverallProgress($pdo, $user_id) {
    try {
        // Get overall completion across all 12 months
        $stmt = $pdo->prepare("
            SELECT AVG(completion_percentage) as overall_percentage
            FROM employee_progress
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $user_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Count only actual quiz passes (months 4, 8, 12 - the cycle assessments)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as quizzes_passed
            FROM employee_progress
            WHERE user_id = :user_id 
            AND month_number IN (4, 8, 12)
            AND quiz_passed = 1
        ");
        $stmt->execute(['user_id' => $user_id]);
        $quiz_count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Count content accessed per month
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT month_number) as months_with_content
            FROM employee_progress
            WHERE user_id = :user_id 
            AND content_completed > 0
        ");
        $stmt->execute(['user_id' => $user_id]);
        $content_access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'overall_percentage' => round($progress['overall_percentage'] ?? 0, 2),
            'quizzes_passed' => $quiz_count['quizzes_passed'] ?? 0,
            'months_with_content' => $content_access['months_with_content'] ?? 0
        ];
    } catch (PDOException $e) {
        error_log("Overall progress error: " . $e->getMessage());
        return null;
    }
}

function updateOrganizationAnalytics($pdo, $organization_id, $month_number) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_employees
            FROM users
            WHERE organization_id = :organization_id 
            AND is_active = 1
            AND role_id = (SELECT id FROM roles WHERE name = 'employee')
        ");
        $stmt->execute(['organization_id' => $organization_id]);
        $total_employees = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) as active_employees
            FROM employee_progress
            WHERE user_id IN (
                SELECT id FROM users WHERE organization_id = :organization_id
            )
            AND month_number = :month_number
            AND last_activity >= DATE('now', '-30 days')
        ");
        $stmt->execute(['organization_id' => $organization_id, 'month_number' => $month_number]);
        $active_employees = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT AVG(completion_percentage) as avg_completion
            FROM employee_progress
            WHERE user_id IN (
                SELECT id FROM users WHERE organization_id = :organization_id
            )
            AND month_number = :month_number
        ");
        $stmt->execute(['organization_id' => $organization_id, 'month_number' => $month_number]);
        $content_completion_rate = $stmt->fetchColumn() ?? 0;
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_attempts,
                AVG(percentage) as avg_score
            FROM quiz_results qr
            JOIN quizzes q ON qr.quiz_id = q.id
            WHERE qr.user_id IN (
                SELECT id FROM users WHERE organization_id = :organization_id
            )
            AND q.month_number = :month_number
        ");
        $stmt->execute(['organization_id' => $organization_id, 'month_number' => $month_number]);
        $quiz_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $quiz_completion_rate = $total_employees > 0 ? ($quiz_stats['total_attempts'] / $total_employees) * 100 : 0;
        $quiz_pass_rate = $quiz_stats['total_attempts'] > 0 ? ($quiz_stats['passed_attempts'] / $quiz_stats['total_attempts']) * 100 : 0;
        
        $compliance_status = 'pending';
        if ($content_completion_rate >= 80 && $quiz_pass_rate >= 70) {
            $compliance_status = 'compliant';
        } elseif ($content_completion_rate >= 50 && $quiz_pass_rate >= 50) {
            $compliance_status = 'in_progress';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO organization_analytics (
                organization_id, month_number, total_employees, active_employees,
                content_completion_rate, quiz_completion_rate, quiz_pass_rate,
                average_quiz_score, compliance_status, last_updated
            )
            VALUES (
                :organization_id, :month_number, :total_employees, :active_employees,
                :content_completion_rate, :quiz_completion_rate, :quiz_pass_rate,
                :average_quiz_score, :compliance_status, CURRENT_TIMESTAMP
            )
            ON CONFLICT(organization_id, month_number) DO UPDATE SET
                total_employees = :total_employees,
                active_employees = :active_employees,
                content_completion_rate = :content_completion_rate,
                quiz_completion_rate = :quiz_completion_rate,
                quiz_pass_rate = :quiz_pass_rate,
                average_quiz_score = :average_quiz_score,
                compliance_status = :compliance_status,
                last_updated = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            'organization_id' => $organization_id,
            'month_number' => $month_number,
            'total_employees' => $total_employees,
            'active_employees' => $active_employees,
            'content_completion_rate' => round($content_completion_rate, 2),
            'quiz_completion_rate' => round($quiz_completion_rate, 2),
            'quiz_pass_rate' => round($quiz_pass_rate, 2),
            'average_quiz_score' => round($quiz_stats['avg_score'] ?? 0, 2),
            'compliance_status' => $compliance_status
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Analytics update error: " . $e->getMessage());
        return false;
    }
}
?>
