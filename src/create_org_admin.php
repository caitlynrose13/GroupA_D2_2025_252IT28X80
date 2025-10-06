<?php
// filepath: src/create_org_admin.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in and is a system admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'system_admin') {
    header('Location: dashboard.php?error=' . urlencode('Access denied. System administrator permission required.'));
    exit();
}

// Get organizations for dropdown
$organizations = [];
try {
    $org_stmt = $pdo->prepare("SELECT id, name FROM organizations WHERE is_active = 1 ORDER BY name");
    $org_stmt->execute();
    $organizations = $org_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $organizations = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $organization_id = $_POST['organization_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($organization_id)) $errors[] = "Organization is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $check_stmt->execute(['username' => $username, 'email' => $email]);
            if ($check_stmt->fetch()) {
                $errors[] = "Username or email already exists";
            } else {
                // Get role ID for org_admin
                $role_stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'org_admin'");
                $role_stmt->execute();
                $org_admin_role = $role_stmt->fetch();
                
                // Get pending_approval status ID
                $status_stmt = $pdo->prepare("SELECT id FROM employee_statuses WHERE name = 'pending_approval'");
                $status_stmt->execute();
                $pending_status = $status_stmt->fetch();
                
                if ($org_admin_role && $pending_status) {
                    // Create the user with pending approval status
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO users (
                            organization_id, username, email, password_hash, 
                            first_name, last_name, role_id, status_id, 
                            is_active, created_at, updated_at
                        ) VALUES (
                            :organization_id, :username, :email, :password_hash,
                            :first_name, :last_name, :role_id, :status_id,
                            1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                        )
                    ");
                    
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert_stmt->execute([
                        'organization_id' => $organization_id,
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => $password_hash,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'role_id' => $org_admin_role['id'],
                        'status_id' => $pending_status['id']
                    ]);
                    
                    // Log the creation
                    error_log("ORG ADMIN CREATED: New org admin account created for {$username} by system admin {$_SESSION['username']} - Status: PENDING APPROVAL");
                    
                    header('Location: user_management.php?success=' . urlencode('Organization administrator account created successfully. Account is pending approval.'));
                    exit();
                } else {
                    $errors[] = "System configuration error: Cannot find required role or status";
                }
            }
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            $errors[] = "Database error occurred while creating the account";
        }
    }
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Organization Admin - SA SMME Cybersecurity Platform</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <!-- Pattern Border -->
    <div class="african-border"></div>
    
    <div class="header">
        <div class="header-left">
            <h1>SA SMME Cybersecurity Platform</h1>
        </div>
        <div class="header-right">
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="content_list.php">Content</a>
                <a href="quiz_list.php">Quizzes</a>
                <a href="user_management.php">Users</a>
            </nav>
            <div class="user-section">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header-section">
            <div class="page-title-area">
                <h2 class="page-title">Create Organization Administrator</h2>
                <p class="page-subtitle">Create a new organization admin account (will require system admin approval)</p>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Create User Form -->
        <div class="form-card">
            <h3 style="margin-bottom: 25px; color: var(--primary-dark); border-bottom: 2px solid var(--light-cream); padding-bottom: 10px;">
                New Organization Administrator Details
            </h3>
            
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="organization_id">Organization *</label>
                    <select id="organization_id" name="organization_id" required>
                        <option value="">Select Organization</option>
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo $org['id']; ?>" 
                                    <?php echo (($_POST['organization_id'] ?? '') == $org['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                        <small style="color: var(--text-medium); font-size: 12px;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid var(--accent-orange);">
                    <h4 style="color: var(--accent-orange); margin-bottom: 10px;">⚠️ Important Note</h4>
                    <p style="margin: 0; font-size: 14px; color: var(--text-dark);">
                        The organization administrator account will be created with <strong>pending approval</strong> status. 
                        The account will need to be approved by a system administrator before the user can access the platform.
                    </p>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <a href="user_management.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Administrator Account</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>