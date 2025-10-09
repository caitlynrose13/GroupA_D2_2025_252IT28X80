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
    // Build security-aware query for multilingual content
    if ($_SESSION['role'] === 'system_admin') {
        // System admin can view all content
        $stmt = $pdo->prepare("
            SELECT c.*, cg.*, ct.name as content_type_name, o.name as organization_name,
                   l.code as language_code, l.name as language_name,
                   u.first_name || ' ' || u.last_name as uploaded_by_name
            FROM content c
            INNER JOIN content_groups cg ON c.content_group_id = cg.id
            INNER JOIN languages l ON c.language_id = l.id
            LEFT JOIN content_types ct ON cg.content_type_id = ct.id
            LEFT JOIN organizations o ON cg.organization_id = o.id
            LEFT JOIN users u ON c.uploaded_by = u.id
            WHERE c.id = :id AND c.is_active = 1 AND cg.is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['id' => $content_id]);
    } else {
        // Regular users can only view global content + their organization's content
        $stmt = $pdo->prepare("
            SELECT c.*, cg.*, ct.name as content_type_name, o.name as organization_name,
                   l.code as language_code, l.name as language_name,
                   u.first_name || ' ' || u.last_name as uploaded_by_name
            FROM content c
            INNER JOIN content_groups cg ON c.content_group_id = cg.id
            INNER JOIN languages l ON c.language_id = l.id
            LEFT JOIN content_types ct ON cg.content_type_id = ct.id
            LEFT JOIN organizations o ON cg.organization_id = o.id
            LEFT JOIN users u ON c.uploaded_by = u.id
            WHERE c.id = :id AND c.is_active = 1 AND cg.is_active = 1
            AND (cg.organization_id IS NULL OR cg.organization_id = :org_id)
            LIMIT 1
        ");
        $stmt->execute(['id' => $content_id, 'org_id' => $_SESSION['organization_id']]);
    }
    
    $content = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$content) {
        header('Location: content_list.php?error=' . urlencode('Content not found or access denied'));
        exit();
    }
    
    // Get other available languages for this content group
    $lang_stmt = $pdo->prepare("
        SELECT c.id, c.title, l.code, l.name as language_name
        FROM content c
        INNER JOIN languages l ON c.language_id = l.id
        WHERE c.content_group_id = :group_id AND c.is_active = 1 AND c.id != :current_id
        ORDER BY l.name
    ");
    $lang_stmt->execute(['group_id' => $content['content_group_id'], 'current_id' => $content_id]);
    $other_languages = $lang_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log content access for tracking
    try {
        $access_stmt = $pdo->prepare("
            INSERT INTO content_access_logs (user_id, content_id, content_group_id, language_id, access_type, ip_address, user_agent, accessed_at)
            VALUES (:user_id, :content_id, :content_group_id, :language_id, 'view', :ip_address, :user_agent, :accessed_at)
        ");
        $access_stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'content_id' => $content_id,
            'content_group_id' => $content['content_group_id'],
            'language_id' => $content['language_id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'accessed_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        // Log error but don't prevent content viewing
        error_log("Error logging content access: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    header('Location: content_list.php?error=' . urlencode('Error loading content'));
    exit();
}

// Check if file exists
$file_error = null;
if ($content['file_path']) {
    $file_path = __DIR__ . '/' . $content['file_path'];
    if (!file_exists($file_path)) {
        $file_error = "File not found on server";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content['title']); ?> - South African SMME Cybersecurity Portal</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <div class="african-border"></div>
    <div class="header">
        <div class="header-left">
            <h1>👁️ View Content</h1>
        </div>
        <div class="header-right">
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="content_list.php">Back to Library</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="content-card">
            <div class="content-header">
                <h1 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h1>
                <div class="content-badges">
                    <?php if (!empty($content['content_type_name'])): ?>
                        <span class="badge badge-type"><?php echo ucfirst($content['content_type_name']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($content['language_name'])): ?>
                        <span class="badge badge-lang"><?php echo htmlspecialchars($content['language_name']); ?></span>
                    <?php endif; ?>
                    <?php 
                    $file_ext = strtolower(pathinfo($content['file_path'], PATHINFO_EXTENSION));
                    if ($file_ext): 
                    ?>
                        <span class="badge badge-type"><?php echo strtoupper($file_ext); ?> File</span>
                    <?php endif; ?>
                    <?php if (!empty($content['month_number'])): 
                        require_once __DIR__ . '/config/program_structure.php';
                        $cycle_info = getCycleByMonth($content['month_number']);
                        if ($cycle_info):
                    ?>
                        <span class="badge badge-month">Cycle <?php echo $cycle_info['cycle_number']; ?></span>
                    <?php endif; endif; ?>
                    <?php if (!empty($content['month_number'])): ?>
                        <span class="badge badge-month">Month <?php echo $content['month_number']; ?></span>
                    <?php endif; ?>
                    <?php if (empty($content['organization_id'])): ?>
                        <span class="badge badge-global">Global Content</span>
                    <?php elseif (!empty($content['organization_name'])): ?>
                        <span class="badge badge-org"><?php echo htmlspecialchars($content['organization_name']); ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Language Switcher -->
                <?php if (!empty($other_languages)): ?>
                    <div class="language-switcher" style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
                        <strong>Available in other languages:</strong>
                        <div style="margin-top: 8px;">
                            <?php foreach ($other_languages as $lang): ?>
                                <a href="view_content.php?id=<?php echo $lang['id']; ?>" 
                                   class="btn-secondary" style="margin-right: 10px; margin-bottom: 5px; display: inline-block;">
                                    <?php echo htmlspecialchars($lang['language_name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($content['description']): ?>
                    <div class="content-description"><?php echo nl2br(htmlspecialchars($content['description'])); ?></div>
                <?php endif; ?>
                
                <div class="content-meta" style="margin-top: 10px; font-size: 0.9em; color: #666;">
                    <?php if ($content['uploaded_by_name']): ?>
                        <span>Uploaded by <?php echo htmlspecialchars($content['uploaded_by_name']); ?></span>
                    <?php endif; ?>
                    <span>Created: <?php echo date('M j, Y', strtotime($content['created_at'])); ?></span>
                    <?php if ($content['file_size']): ?>
                        <span>Size: <?php echo number_format($content['file_size'] / 1024, 1); ?> KB</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="content-viewer">
                <?php if (isset($file_error)): ?>
                    <div class="message error"><?php echo $file_error; ?></div>
                <?php else: ?>
                    <a href="download_content.php?id=<?php echo $content['id']; ?>" class="btn-primary download-btn">📥 Download File</a>
                    
                    <div class="file-preview">
                        <?php
                        $file_type = strtolower(pathinfo($content['file_path'], PATHINFO_EXTENSION));
                        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])):
                        ?>
                            <!-- Image Display -->
                            <img src="<?php echo htmlspecialchars($content['file_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($content['title']); ?>" 
                                 style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        
                        <?php elseif ($file_type === 'pdf'): ?>
                            <!-- PDF Viewer - Full Browser Display -->
                            <div class="pdf-viewer-container">
                                <iframe src="pdf_viewer.php?id=<?php echo $content['id']; ?>" 
                                        title="<?php echo htmlspecialchars($content['title']); ?>">
                                    <p>Your browser does not support PDF viewing. 
                                       <a href="download_content.php?id=<?php echo $content['id']; ?>" class="african-link">Download the PDF</a> 
                                       to view it.</p>
                                </iframe>
                            </div>
                        
                        <?php elseif (in_array($file_type, ['mp4', 'mov', 'avi', 'webm'])): ?>
                            <!-- Video Player -->
                            <video controls class="video-player">
                                <source src="<?php echo htmlspecialchars($content['file_path']); ?>" 
                                        type="video/<?php echo $file_type === 'mov' ? 'quicktime' : $file_type; ?>">
                                Your browser does not support the video tag. 
                                <a href="download_content.php?id=<?php echo $content['id']; ?>" class="african-link">Download the video</a> instead.
                            </video>
                        
                        <?php elseif (in_array($file_type, ['doc', 'docx'])): ?>
                            <!-- Word Document Preview -->
                            <div class="file-preview-message">
                                <div class="file-icon">📝</div>
                                <h3>Word Document</h3>
                                <p>Microsoft Word documents cannot be displayed in the browser.</p>
                                <p>Please use the download button above to view this document.</p>
                            </div>
                        
                        <?php elseif (in_array($file_type, ['ppt', 'pptx'])): ?>
                            <!-- PowerPoint Preview -->
                            <div class="file-preview-message">
                                <div class="file-icon">📊</div>
                                <h3>PowerPoint Presentation</h3>
                                <p>PowerPoint presentations cannot be displayed in the browser.</p>
                                <p>Please use the download button above to view this presentation.</p>
                            </div>
                        
                        <?php else: ?>
                            <!-- Generic File -->
                            <div class="file-preview-message">
                                <div class="file-icon">📁</div>
                                <h3><?php echo strtoupper($file_type); ?> File</h3>
                                <p>This file type cannot be previewed in the browser.</p>
                                <p>Please use the download button above to view this file.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>