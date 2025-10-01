<?php
require_once '../includes/header.php';

if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: ../employee/dashboard.php');
    exit();
}

require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Get users based on admin type
if ($_SESSION['role'] == 'system_admin') {
    $stmt = $pdo->query("SELECT u.*, o.name as organization_name FROM users u LEFT JOIN organizations o ON u.organization_id = o.id");
} else {
    $stmt = $pdo->prepare("SELECT u.*, o.name as organization_name FROM users u LEFT JOIN organizations o ON u.organization_id = o.id WHERE u.organization_id = ?");
    $stmt->execute([$_SESSION['organization_id']]);
}
$users = $stmt->fetchAll();
?>

<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Users</h1>
        <a href="create_user.php" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Create New User
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Organization</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['role'] == 'system_admin' ? 'danger' : 
                                         ($user['role'] == 'org_admin' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td><?php echo $user['organization_name'] ?? 'N/A'; ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
