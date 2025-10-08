<?php
// filepath: src/create_organization.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in and is system admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'system_admin') {
    header('Location: dashboard.php?error=' . urlencode('Access denied. System administrator permission required.'));
    exit();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $size_category = trim($_POST['size_category'] ?? '');
    
    // Validation
    if (empty($name)) {
        $error = 'Organization name is required.';
    } elseif (empty($contact_email)) {
        $error = 'Contact email is required.';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if organization name already exists
            $check_stmt = $pdo->prepare("SELECT id FROM organizations WHERE name = :name AND is_active = 1");
            $check_stmt->execute(['name' => $name]);
            
            if ($check_stmt->fetch()) {
                $error = 'An organization with this name already exists.';
            } else {
                // Insert new organization
                $stmt = $pdo->prepare("
                    INSERT INTO organizations (name, industry, size_category, contact_email, contact_phone, address, is_active, created_at, updated_at) 
                    VALUES (:name, :industry, :size_category, :contact_email, :contact_phone, :address, 1, :created_at, :updated_at)
                ");
                
                $stmt->execute([
                    'name' => $name,
                    'industry' => $industry ?: null,
                    'size_category' => $size_category ?: null,
                    'contact_email' => $contact_email,
                    'contact_phone' => $contact_phone ?: null,
                    'address' => $address ?: null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $success = 'Organization created successfully!';
                
                // Redirect to organization management page with success message
                header('Location: organization_management.php?success=' . urlencode($success));
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Error creating organization: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Organization - SA SMME Cybersecurity Platform</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s ease;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
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
                <a href="organization_management.php">Organizations</a>
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
                <h2 class="page-title">Create New Organization</h2>
                <p class="page-subtitle">Add a new organization to the cybersecurity training platform</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3 style="margin-bottom: 25px; color: var(--primary-dark); border-bottom: 2px solid var(--light-cream); padding-bottom: 10px;">
                Organization Details
            </h3>

            <form method="POST" action="create_organization.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Organization Name *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               placeholder="Enter organization name"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Contact Email *</label>
                        <input type="email" 
                               id="contact_email" 
                               name="contact_email" 
                               class="form-control" 
                               placeholder="contact@organization.com"
                               value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>" 
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="tel" 
                               id="contact_phone" 
                               name="contact_phone" 
                               class="form-control" 
                               placeholder="+27 11 123 4567"
                               value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="industry">Industry</label>
                        <input type="text" 
                               id="industry" 
                               name="industry" 
                               class="form-control" 
                               placeholder="e.g., Technology, Healthcare, Finance"
                               value="<?php echo htmlspecialchars($_POST['industry'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="size_category">Organization Size</label>
                        <select id="size_category" name="size_category" class="form-control">
                            <option value="">Select size category</option>
                            <option value="micro" <?php echo ($_POST['size_category'] ?? '') === 'micro' ? 'selected' : ''; ?>>Micro (1-5 employees)</option>
                            <option value="small" <?php echo ($_POST['size_category'] ?? '') === 'small' ? 'selected' : ''; ?>>Small (6-50 employees)</option>
                            <option value="medium" <?php echo ($_POST['size_category'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium (51-250 employees)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               class="form-control" 
                               placeholder="Organization address"
                               value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              placeholder="Brief description of the organization (optional)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--light-cream);">
                    <a href="organization_management.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Organization</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>