<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';
require_once __DIR__ . '/config/progress_helper.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied'));
    exit();
}

$organization_id = $_SESSION['organization_id'];
$selected_month = $_GET['month'] ?? date('n');

try {
    if ($organization_id) {
        foreach (range(1, 12) as $month) {
            updateOrganizationAnalytics($pdo, $organization_id, $month);
        }
    }
    
    $where_clause = $organization_id ? "organization_id = :org_id AND" : "";
    $params = $organization_id ? ['org_id' => $organization_id] : [];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_employees
        FROM users
        WHERE $where_clause is_active = 1
        AND role_id = (SELECT id FROM roles WHERE name = 'employee')
    ");
    $stmt->execute($params);
    $total_employees = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT qr.user_id) as active_users
        FROM quiz_results qr
        JOIN users u ON qr.user_id = u.id
        WHERE qr.completed_at >= DATE('now', '-30 days')
        " . ($organization_id ? "AND u.organization_id = :org_id" : "")
    );
    $stmt->execute($params);
    $active_users = $stmt->fetchColumn();
    
    $org_where = $organization_id ? "WHERE u.organization_id = :org_id" : "WHERE 1=1";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT qr.user_id) as users_with_attempts,
            SUM(CASE WHEN qr.passed = 1 THEN 1 ELSE 0 END) as total_passes
        FROM quiz_results qr
        JOIN users u ON qr.user_id = u.id
        $org_where
    ");
    $stmt->execute($params);
    $quiz_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT * FROM organization_analytics
        " . ($organization_id ? "WHERE organization_id = :org_id" : "") . "
        ORDER BY month_number ASC
    ");
    $stmt->execute($params);
    $monthly_analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT 
            u.first_name,
            u.last_name,
            u.email,
            ep.month_number,
            ep.completion_percentage,
            ep.quiz_passed
        FROM users u
        LEFT JOIN employee_progress ep ON u.id = ep.user_id
        WHERE u.role_id = (SELECT id FROM roles WHERE name = 'employee')
        " . ($organization_id ? "AND u.organization_id = :org_id" : "") . "
        AND ep.month_number = :month
        ORDER BY ep.completion_percentage DESC
        LIMIT 10
    ");
    $stmt->execute(array_merge($params, ['month' => $selected_month]));
    $top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT 
            u.first_name,
            u.last_name,
            u.email,
            COALESCE(AVG(ep.completion_percentage), 0) as avg_progress
        FROM users u
        LEFT JOIN employee_progress ep ON u.id = ep.user_id
        WHERE u.role_id = (SELECT id FROM roles WHERE name = 'employee')
        " . ($organization_id ? "AND u.organization_id = :org_id" : "") . "
        GROUP BY u.id
        HAVING avg_progress < 30
        ORDER BY avg_progress ASC
        LIMIT 10
    ");
    $stmt->execute($params);
    $at_risk = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Analytics error: " . $e->getMessage());
    $total_employees = 0;
    $active_users = 0;
    $quiz_stats = ['users_with_attempts' => 0, 'total_passes' => 0];
    $monthly_analytics = [];
    $top_performers = [];
    $at_risk = [];
}

$engagement_rate = $total_employees > 0 ? round(($active_users / $total_employees) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Analytics - Cybersecurity Awareness</title>
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .compliance-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .compliance-compliant { background: #2ecc71; color: white; }
        .compliance-in_progress { background: #f39c12; color: white; }
        .compliance-pending { background: #e74c3c; color: white; }
        .progress-mini {
            height: 6px;
            background: #ecf0f1;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-mini-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📈 Organization Analytics</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="content_list.php">Content</a>
            <a href="quiz_list.php">Quizzes</a>
            <a href="progress.php">My Progress</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_employees; ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $engagement_rate; ?>%</div>
                <div class="stat-label">Engagement Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $quiz_stats['users_with_attempts']; ?></div>
                <div class="stat-label">Active Learners</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $quiz_stats['total_passes']; ?></div>
                <div class="stat-label">Total Quiz Passes</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Monthly Progress Overview</div>
            <div class="filter-bar">
                <label>View Month:</label>
                <select onchange="window.location.href='?month=' + this.value">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                            Month <?php echo $m; ?>: <?php echo getMonthInfo($m)['title']; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <?php if (!empty($monthly_analytics)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Active</th>
                            <th>Content</th>
                            <th>Quiz Completion</th>
                            <th>Quiz Pass Rate</th>
                            <th>Avg Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_analytics as $analytics): ?>
                            <tr>
                                <td>
                                    <strong>Month <?php echo $analytics['month_number']; ?></strong><br>
                                    <small><?php echo getMonthInfo($analytics['month_number'])['title']; ?></small>
                                </td>
                                <td><?php echo $analytics['active_employees']; ?>/<?php echo $analytics['total_employees']; ?></td>
                                <td><?php echo round($analytics['content_completion_rate'], 1); ?>%</td>
                                <td><?php echo round($analytics['quiz_completion_rate'], 1); ?>%</td>
                                <td><?php echo round($analytics['quiz_pass_rate'], 1); ?>%</td>
                                <td><?php echo round($analytics['average_quiz_score'], 1); ?>%</td>
                                <td>
                                    <span class="compliance-badge compliance-<?php echo $analytics['compliance_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $analytics['compliance_status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">No analytics data available yet.</p>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="section">
                <div class="section-title">Top Performers (Month <?php echo $selected_month; ?>)</div>
                <?php if (!empty($top_performers)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_performers as $performer): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($performer['first_name'] . ' ' . $performer['last_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($performer['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo round($performer['completion_percentage'], 1); ?>%
                                        <div class="progress-mini">
                                            <div class="progress-mini-fill" style="width: <?php echo $performer['completion_percentage']; ?>%;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($performer['quiz_passed']): ?>
                                            <span style="color: #2ecc71;">✓ Passed</span>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No data for this month.</p>
                <?php endif; ?>
            </div>

            <div class="section">
                <div class="section-title">At-Risk Employees</div>
                <?php if (!empty($at_risk)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Avg Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($at_risk as $employee): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($employee['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo round($employee['avg_progress'], 1); ?>%
                                        <div class="progress-mini">
                                            <div class="progress-mini-fill" style="width: <?php echo $employee['avg_progress']; ?>%; background: #e74c3c;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="color: #e74c3c; font-weight: bold;">Needs Support</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #2ecc71; padding: 20px;">All employees are on track!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
