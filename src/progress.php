<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';
require_once __DIR__ . '/config/progress_helper.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$view_user_id = $_GET['user_id'] ?? $user_id;

if ($view_user_id != $user_id && !in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: progress.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $view_user_id]);
    $view_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$view_user) {
        header('Location: dashboard.php?error=' . urlencode('User not found'));
        exit();
    }
    
    if ($view_user['organization_id'] && $_SESSION['organization_id'] && 
        $view_user['organization_id'] != $_SESSION['organization_id'] && 
        $_SESSION['role'] != 'system_admin') {
        header('Location: progress.php');
        exit();
    }
    
    foreach (range(1, 12) as $month) {
        updateEmployeeProgress($pdo, $view_user_id, $month);
    }
    
    $progress_data = getEmployeeProgress($pdo, $view_user_id);
    $overall_progress = getOverallProgress($pdo, $view_user_id);
    
} catch (PDOException $e) {
    error_log("Progress page error: " . $e->getMessage());
    header('Location: dashboard.php?error=' . urlencode('Error loading progress'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Progress - Cybersecurity Awareness</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: #f5f7fa;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { margin: 0; font-size: 24px; }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .nav-links a:hover { background: rgba(255,255,255,0.1); }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .progress-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .progress-header h2 { margin: 0 0 10px 0; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .cycle-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .cycle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        .cycle-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }
        .cycle-progress {
            font-size: 18px;
            font-weight: bold;
            color: #3498db;
        }
        .month-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .month-card {
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            padding: 15px;
            background: #f8f9fa;
        }
        .month-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .progress-bar {
            background: #ecf0f1;
            height: 24px;
            border-radius: 12px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-complete { background: #2ecc71; color: white; }
        .status-partial { background: #f39c12; color: white; }
        .status-pending { background: #95a5a6; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Learning Progress</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="content_list.php">Content</a>
            <a href="quiz_list.php">Quizzes</a>
            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                <a href="analytics.php">Analytics</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="progress-header">
            <h2><?php echo htmlspecialchars($view_user['first_name'] . ' ' . $view_user['last_name']); ?>'s Progress</h2>
            <?php if ($view_user_id != $user_id): ?>
                <p style="color: #7f8c8d; margin: 5px 0 0 0;">Employee ID: <?php echo htmlspecialchars($view_user['employee_id'] ?? 'N/A'); ?></p>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_progress['overall_percentage']; ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_progress['quizzes_passed']; ?>/12</div>
                <div class="stat-label">Quizzes Passed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_progress['months_started']; ?>/12</div>
                <div class="stat-label">Months Started</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $cycles_completed = 0;
                    foreach ($progress_data as $p) {
                        if (in_array($p['month_number'], [4, 8, 12]) && $p['quiz_passed']) {
                            $cycles_completed++;
                        }
                    }
                    echo $cycles_completed;
                    ?>/3
                </div>
                <div class="stat-label">Cycles Completed</div>
            </div>
        </div>

        <?php foreach (PROGRAM_CYCLES as $cycle): ?>
            <?php
            $cycle_months = getMonthsByCycle($cycle['cycle_number']);
            $cycle_progress_sum = 0;
            $cycle_month_count = count($cycle_months);
            
            foreach ($cycle_months as $month) {
                $month_progress = null;
                foreach ($progress_data as $p) {
                    if ($p['month_number'] == $month['month_number']) {
                        $month_progress = $p;
                        $cycle_progress_sum += $p['completion_percentage'];
                        break;
                    }
                }
            }
            $cycle_avg_progress = $cycle_month_count > 0 ? round($cycle_progress_sum / $cycle_month_count, 1) : 0;
            ?>
            
            <div class="cycle-section">
                <div class="cycle-header">
                    <div>
                        <div class="cycle-title"><?php echo htmlspecialchars($cycle['title']); ?></div>
                        <small style="color: #7f8c8d;">Months <?php echo $cycle['start_month']; ?>-<?php echo $cycle['end_month']; ?></small>
                    </div>
                    <div class="cycle-progress"><?php echo $cycle_avg_progress; ?>%</div>
                </div>

                <div class="month-grid">
                    <?php foreach ($cycle_months as $month): ?>
                        <?php
                        $month_progress = null;
                        foreach ($progress_data as $p) {
                            if ($p['month_number'] == $month['month_number']) {
                                $month_progress = $p;
                                break;
                            }
                        }
                        
                        $completion = $month_progress ? $month_progress['completion_percentage'] : 0;
                        $status = 'pending';
                        if ($completion >= 100) {
                            $status = 'complete';
                        } elseif ($completion > 0) {
                            $status = 'partial';
                        }
                        ?>
                        <div class="month-card">
                            <div class="month-title">Month <?php echo $month['month_number']; ?>: <?php echo htmlspecialchars($month['title']); ?></div>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $completion; ?>%;">
                                    <?php if ($completion > 15): ?><?php echo round($completion); ?>%<?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                                <?php if ($month_progress && $month_progress['quiz_passed']): ?>
                                    <span style="color: #2ecc71; font-weight: bold;">✓ Quiz Passed</span>
                                <?php elseif ($month_progress && $month_progress['quiz_completed']): ?>
                                    <span style="color: #e74c3c; font-weight: bold;">✗ Quiz Failed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
