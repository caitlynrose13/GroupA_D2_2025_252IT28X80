<?php
// filepath: src/content_upload.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';

// Check if user is logged in and has upload permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied. Upload permission required.'));
    exit();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content - Cybersecurity Platform</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <!-- Pattern Border -->
    <div class="african-border"></div>
    
    <div class="header">
        <div class="header-left">
            <h1>Cybersecurity Platform</h1>
        </div>
        <div class="header-right">
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="content_list.php">Content</a>
                <a href="quiz_list.php">Quizzes</a>
                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                    <a href="organization_management.php">Organizations</a>
                <?php elseif ($_SESSION['role'] === 'org_admin'): ?>
                    <a href="user_management.php">Users</a>
                <?php endif; ?>
            </nav>
            <div class="user-section">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Upload Content</h2>
            <p>Share cybersecurity training materials with your <?php echo $_SESSION['role'] === 'system_admin' ? 'platform' : 'organization'; ?></p>
        </div>
        
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars(urldecode($error)); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" action="process_upload.php" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Content Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required maxlength="255" placeholder="Enter content title">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Brief description of the content..."></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="file">Upload File <span class="required">*</span></label>
                        <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi">
                        <small class="form-help">Supported: PDF, Word, PowerPoint, Images, Videos (Max: 10MB)</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="content_type">Content Type <span class="required">*</span></label>
                        <select id="content_type" name="content_type" required>
                            <option value="">Select Type...</option>
                            <option value="document">Document</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                            <option value="misc">Miscellaneous</option>
                        </select>
                    </div>
                    
                    <div class="form-group half-width">
                        <label for="month_number">Program Month</label>
                        <select id="month_number" name="month_number">
                            <option value="">Select Month...</option>
                            <?php 
                            foreach (PROGRAM_MONTHS as $month_num => $month_info): 
                            ?>
                                <option value="<?php echo $month_num; ?>">
                                    Month <?php echo $month_num; ?>: <?php echo htmlspecialchars($month_info['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Leave blank for general content</small>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                <div class="form-row">
                    <div class="admin-info">
                        <div class="info-badge">
                            <strong>System Administrator Upload</strong>
                            <p>This content will be available to all organizations on the platform</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="form-row">
                    <div class="admin-info">
                        <div class="info-badge org-admin">
                            <strong>Organization Upload</strong>
                            <p>This content will be available only to your organization</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Upload Content</button>
                    <a href="content_list.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>