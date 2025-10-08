<?php
// filepath: src/edit_organization.php
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
$organization = null;

// Get organization ID from URL
$org_id = $_GET['id'] ?? '';

if (empty($org_id) || !is_numeric($org_id)) {
    header('Location: organization_management.php?error=' . urlencode('Invalid organization ID.'));
    exit();
}

// Fetch organization details
try {
    $stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = :id AND is_active = 1");
    $stmt->execute(['id' => $org_id]);
    $organization = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$organization) {
        header('Location: organization_management.php?error=' . urlencode('Organization not found.'));
        exit();
    }
} catch (PDOException $e) {
    header('Location: organization_management.php?error=' . urlencode('Error loading organization: ' . $e->getMessage()));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
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
            // Check if organization name already exists (excluding current organization)
            $check_stmt = $pdo->prepare("SELECT id FROM organizations WHERE name = :name AND id != :id AND is_active = 1");
            $check_stmt->execute(['name' => $name, 'id' => $org_id]);
            
            if ($check_stmt->fetch()) {
                $error = 'An organization with this name already exists.';
            } else {
                // Update organization
                $stmt = $pdo->prepare("
                    UPDATE organizations 
                    SET name = :name, 
                        industry = :industry, 
                        size_category = :size_category, 
                        contact_email = :contact_email, 
                        contact_phone = :contact_phone, 
                        address = :address, 
                        updated_at = :updated_at
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    'name' => $name,
                    'industry' => $industry ?: null,
                    'size_category' => $size_category ?: null,
                    'contact_email' => $contact_email,
                    'contact_phone' => $contact_phone ?: null,
                    'address' => $address ?: null,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id' => $org_id
                ]);
                
                $success = 'Organization updated successfully!';
                
                // Redirect to organization management page with success message
                header('Location: organization_management.php?success=' . urlencode($success));
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Error updating organization: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Organization - SA SMME Cybersecurity Platform</title>
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
        
        .organization-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-dark);
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.9em;
        }
        
        .info-label {
            color: var(--text-medium);
            font-weight: 500;
        }
        
        .info-value {
            color: var(--text-dark);
            font-weight: bold;
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
                <h2 class="page-title">Edit Organization</h2>
                <p class="page-subtitle">Update organization information and contact details</p>
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

            <!-- Current Organization Info -->
            <div class="organization-info">
                <h4 style="margin: 0 0 10px 0; color: var(--primary-dark);">Current Information</h4>
                <div class="info-item">
                    <span class="info-label">Organization ID:</span>
                    <span class="info-value">#<?php echo $organization['id']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Created:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime($organization['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Updated:</span>
                    <span class="info-value"><?php echo date('M d, Y H:i', strtotime($organization['updated_at'])); ?></span>
                </div>
            </div>

            <form method="POST" action="edit_organization.php?id=<?php echo $org_id; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Organization Name *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               placeholder="Enter organization name"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? $organization['name']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Contact Email *</label>
                        <input type="email" 
                               id="contact_email" 
                               name="contact_email" 
                               class="form-control" 
                               placeholder="contact@organization.com"
                               value="<?php echo htmlspecialchars($_POST['contact_email'] ?? $organization['contact_email']); ?>" 
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
                               value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? $organization['contact_phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="industry">Industry</label>
                        <input type="text" 
                               id="industry" 
                               name="industry" 
                               class="form-control" 
                               placeholder="e.g., Technology, Healthcare, Finance"
                               value="<?php echo htmlspecialchars($_POST['industry'] ?? $organization['industry']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="size_category">Organization Size</label>
                        <select id="size_category" name="size_category" class="form-control">
                            <option value="">Select size category</option>
                            <option value="micro" <?php echo ($_POST['size_category'] ?? $organization['size_category']) === 'micro' ? 'selected' : ''; ?>>Micro (1-5 employees)</option>
                            <option value="small" <?php echo ($_POST['size_category'] ?? $organization['size_category']) === 'small' ? 'selected' : ''; ?>>Small (6-50 employees)</option>
                            <option value="medium" <?php echo ($_POST['size_category'] ?? $organization['size_category']) === 'medium' ? 'selected' : ''; ?>>Medium (51-250 employees)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               class="form-control" 
                               placeholder="Organization address"
                               value="<?php echo htmlspecialchars($_POST['address'] ?? $organization['address']); ?>">
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--light-cream);">
                    <a href="organization_management.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Organization</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>