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

if (!in_array($_SESSION['role'], ['admin', 'org_admin'])) {
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
    <title>Upload Content - Cybersecurity Awareness</title>
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
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .upload-btn {
            background: #27ae60;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }
        .upload-btn:hover { background: #219a52; }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .required { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Upload Content</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="content_list.php">View Content</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="form-card">
            <h2>Upload New Content</h2>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars(urldecode($success)); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars(urldecode($error)); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="process_upload.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Content Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Brief description of the content..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="file">Upload File <span class="required">*</span></label>
                    <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi">
                    <small>Supported: PDF, Word, PowerPoint, Images, Videos (Max: 10MB)</small>
                </div>
                
                <div class="form-group">
                    <label for="content_type">Content Type <span class="required">*</span></label>
                    <select id="content_type" name="content_type" required>
                        <option value="">Select Type...</option>
                        <option value="document">Document</option>
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                        <option value="misc">Miscellaneous</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="month_number">Program Month</label>
                    <select id="month_number" name="month_number">
                        <option value="">Select Month...</option>
                        <?php 
                        foreach (PROGRAM_MONTHS as $month_num => $month_info): 
                        ?>
                            <option value="<?php echo $month_num; ?>">
                                Month <?php echo $month_num; ?>: <?php echo htmlspecialchars($month_info['title']); ?>
                                (<?php echo htmlspecialchars($month_info['theme']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="upload-btn">Upload Content</button>
            </form>
        </div>
    </div>
</body>
</html>