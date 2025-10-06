<?php
// filepath: src/view_content.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

$content_id = $_GET['id'] ?? '';
if (empty($content_id)) {
    header('Location: content_list.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, ct.name as content_type
        FROM content c
        LEFT JOIN content_types ct ON c.content_type_id = ct.id
        WHERE c.id = :id AND c.is_active = 1 
        LIMIT 1
    ");
    $stmt->execute(['id' => $content_id]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$content) {
        header('Location: content_list.php?error=' . urlencode('Content not found'));
        exit();
    }
} catch (PDOException $e) {
    header('Location: content_list.php?error=' . urlencode('Error loading content'));
    exit();
}

// Check if file exists
$file_path = __DIR__ . '/' . $content['file_path'];
if (!file_exists($file_path)) {
    $file_error = "File not found on server";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content['title']); ?> - Cybersecurity Awareness</title>
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
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .content-header {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }
        .content-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .content-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .meta-badge {
            background: #ecf0f1;
            color: #555;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
        }
        .content-description {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }
        .content-viewer {
            padding: 30px;
        }
        .download-btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 20px;
            display: inline-block;
        }
        .download-btn:hover {
            background: #2980b9;
        }
        .file-preview {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            background: #f9f9f9;
        }
        .file-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>View Content</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="content_list.php">Back to Library</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="content-card">
            <div class="content-header">
                <div class="content-title"><?php echo htmlspecialchars($content['title']); ?></div>
                <div class="content-meta">
                    <span class="meta-badge"><?php echo ucfirst($content['content_type'] ?? 'content'); ?></span>
                    <span class="meta-badge"><?php echo strtoupper($content['file_type'] ?? 'file'); ?> File</span>
                    <?php if ($content['cycle_number']): ?>
                        <span class="meta-badge">Cycle <?php echo $content['cycle_number']; ?></span>
                    <?php endif; ?>
                    <?php if ($content['month_number']): ?>
                        <span class="meta-badge">Month <?php echo $content['month_number']; ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($content['description']): ?>
                    <div class="content-description"><?php echo nl2br(htmlspecialchars($content['description'])); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="content-viewer">
                <?php if (isset($file_error)): ?>
                    <div class="error-message"><?php echo $file_error; ?></div>
                <?php else: ?>
                    <a href="download_content.php?id=<?php echo $content['id']; ?>" class="download-btn">Download File</a>
                    
                    <div class="file-preview">
                        <?php
                        $file_type = $content['file_type'];
                        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])):
                        ?>
                            <img src="<?php echo htmlspecialchars($content['file_path']); ?>" alt="<?php echo htmlspecialchars($content['title']); ?>" style="max-width: 100%; height: auto;">
                        <?php elseif ($file_type === 'pdf'): ?>
                            <div class="file-icon">📄</div>
                            <p>PDF Document</p>
                            <p>Click download to view this PDF file.</p>
                        <?php elseif (in_array($file_type, ['doc', 'docx'])): ?>
                            <div class="file-icon">📝</div>
                            <p>Word Document</p>
                            <p>Click download to view this document.</p>
                        <?php elseif (in_array($file_type, ['ppt', 'pptx'])): ?>
                            <div class="file-icon">📊</div>
                            <p>PowerPoint Presentation</p>
                            <p>Click download to view this presentation.</p>
                        <?php elseif (in_array($file_type, ['mp4', 'mov', 'avi'])): ?>
                            <div class="file-icon">🎥</div>
                            <p>Video File</p>
                            <p>Click download to view this video.</p>
                        <?php else: ?>
                            <div class="file-icon">📁</div>
                            <p><?php echo strtoupper($file_type); ?> File</p>
                            <p>Click download to view this file.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>