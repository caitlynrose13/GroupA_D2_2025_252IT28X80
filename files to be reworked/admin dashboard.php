<?php
require_once '../includes/header.php';

// Check if user is admin
if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: ../employee/dashboard.php');
    exit();
}

require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Get statistics
$userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$assessmentCount = $pdo->query("SELECT COUNT(*) FROM assessments")->fetchColumn();
$contentCount = $pdo->query("SELECT COUNT(*) FROM content")->fetchColumn();
?>

<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Admin Dashboard</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Employees</h5>
                            <h2><?php echo $userCount; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Assessments</h5>
                            <h2><?php echo $assessmentCount; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Content Items</h5>
                            <h2><?php echo $contentCount; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-book fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="manage_users.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users-cog"></i><br>
                                Manage Users
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="create_user.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-user-plus"></i><br>
                                Create User
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../employee/content.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-book"></i><br>
                                View Content
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../employee/assessments.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-clipboard-list"></i><br>
                                Assessments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
