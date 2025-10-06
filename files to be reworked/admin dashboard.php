<?php
require_once '../includes/header.php';

// Enhanced permission check for governance
if (!isSystemAdmin() && !isOrgAdmin()) {
    header('Location: ../employee/dashboard.php');
    exit();
}

require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Get role-based statistics for governance
$employeeRoleId = $pdo->query("SELECT id FROM roles WHERE name = 'employee'")->fetchColumn();
$adminRoleId = $pdo->query("SELECT id FROM roles WHERE name = 'org_admin'")->fetchColumn();

if (isSystemAdmin()) {
    // System Admin - Multi-tenant overview
    $totalOrganizations = $pdo->query("SELECT COUNT(*) FROM organizations WHERE is_active = 1")->fetchColumn();
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    $activeSubscriptions = $pdo->query("SELECT COUNT(*) FROM organizations WHERE subscription_status = 'active'")->fetchColumn();
    $totalContent = $pdo->query("SELECT COUNT(*) FROM content WHERE is_active = 1")->fetchColumn();
    
    $stats = [
        'organizations' => $totalOrganizations,
        'users' => $totalUsers,
        'subscriptions' => $activeSubscriptions,
        'content' => $totalContent
    ];
    
    $dashboardTitle = "System Administrator Dashboard";
    $dashboardType = 'system';
    
} else {
    // Organization Admin - Single tenant overview
    $orgId = getCurrentOrganizationId();
    
    $totalEmployees = $pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = ? AND role_id = ? AND is_active = 1");
    $totalEmployees->execute([$orgId, $employeeRoleId]);
    $totalEmployees = $totalEmployees->fetchColumn();
    
    $totalQuizzes = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE organization_id = ? AND is_active = 1");
    $totalQuizzes->execute([$orgId]);
    $totalQuizzes = $totalQuizzes->fetchColumn();
    
    $totalContent = $pdo->prepare("SELECT COUNT(*) FROM content WHERE (organization_id = ? OR organization_id IS NULL) AND is_active = 1");
    $totalContent->execute([$orgId]);
    $totalContent = $totalContent->fetchColumn();
    
    // Get completion rate for governance reporting
    $completionRate = $pdo->prepare("
        SELECT AVG(completion_percentage) 
        FROM employee_progress 
        WHERE user_id IN (SELECT id FROM users WHERE organization_id = ?)
    ");
    $completionRate->execute([$orgId]);
    $avgCompletion = round($completionRate->fetchColumn() ?? 0);
    
    $stats = [
        'employees' => $totalEmployees,
        'quizzes' => $totalQuizzes,
        'content' => $totalContent,
        'completion' => $avgCompletion
    ];
    
    $dashboardTitle = "Organization Admin Dashboard";
    $dashboardType = 'organization';
}

// Get recent activity for both dashboard types
$recentOrgs = $pdo->query("
    SELECT name, subscription_plan, subscription_status, created_at 
    FROM organizations 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-shield-alt me-2"></i><?php echo $dashboardTitle; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportComplianceReport()">
                <i class="fas fa-download me-1"></i>Export Report
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards - Dynamic based on user role -->
<div class="row mb-4">
    <?php if ($dashboardType === 'system'): ?>
        <!-- System Admin Cards -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Organizations</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['organizations']; ?></div>
                            <div class="mt-2 text-xs text-success">
                                <i class="fas fa-building me-1"></i>Active Tenants
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['users']; ?></div>
                            <div class="mt-2 text-xs text-info">
                                <i class="fas fa-users me-1"></i>Platform Users
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Subscriptions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['subscriptions']; ?></div>
                            <div class="mt-2 text-xs text-success">
                                <i class="fas fa-check-circle me-1"></i>Compliant
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Content Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['content']; ?></div>
                            <div class="mt-2 text-xs text-primary">
                                <i class="fas fa-book me-1"></i>Training Materials
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Organization Admin Cards -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Employees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['employees']; ?></div>
                            <div class="mt-2 text-xs text-success">
                                <i class="fas fa-user-check me-1"></i>Active
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Assessments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['quizzes']; ?></div>
                            <div class="mt-2 text-xs text-info">
                                <i class="fas fa-clipboard-check me-1"></i>Available
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Content Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['content']; ?></div>
                            <div class="mt-2 text-xs text-primary">
                                <i class="fas fa-book me-1"></i>Training Materials
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Completion Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completion']; ?>%</div>
                            <div class="mt-2 text-xs text-warning">
                                <i class="fas fa-chart-line me-1"></i>Overall Progress
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>











  

    




























