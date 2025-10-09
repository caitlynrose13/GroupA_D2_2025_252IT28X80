<?php
// filepath: src/download_content.php
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
            SELECT c.*, cg.organization_id as group_org_id, l.code as language_code, l.name as language_name
            FROM content c
            INNER JOIN content_groups cg ON c.content_group_id = cg.id
            INNER JOIN languages l ON c.language_id = l.id
            WHERE c.id = :id AND c.is_active = 1 AND cg.is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['id' => $content_id]);
    } else {
        // Regular users can only view global content + their organization's content
        $stmt = $pdo->prepare("
            SELECT c.*, cg.organization_id as group_org_id, l.code as language_code, l.name as language_name
            FROM content c
            INNER JOIN content_groups cg ON c.content_group_id = cg.id
            INNER JOIN languages l ON c.language_id = l.id
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
    
    // Log content access for tracking
    try {
        $access_stmt = $pdo->prepare("
            INSERT INTO content_access_logs (user_id, content_id, content_group_id, language_id, access_type, ip_address, user_agent, accessed_at)
            VALUES (:user_id, :content_id, :content_group_id, :language_id, 'download', :ip_address, :user_agent, :accessed_at)
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
        // Log error but don't prevent download
        error_log("Error logging content access: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    header('Location: content_list.php?error=' . urlencode('Error loading content'));
    exit();
}

// Extract file extension from path
$file_ext = strtolower(pathinfo($content['file_path'], PATHINFO_EXTENSION));

// Build correct file path - file_path is already relative to src/
$file_path = __DIR__ . '/' . $content['file_path'];

if (!file_exists($file_path)) {
    header('Location: content_list.php?error=' . urlencode('File not found: ' . $content['file_path']));
    exit();
}

// Sanitize filename with language info
$safe_title = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $content['title']);
$safe_title = trim($safe_title);
if (empty($safe_title)) {
    $safe_title = 'content_' . $content['id'];
}

// Include language code in filename for clarity
$filename = $safe_title . '_' . $content['language_code'] . '.' . $file_ext;

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'mp4' => 'video/mp4',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo'
];

$content_type = $content_types[$file_ext] ?? 'application/octet-stream';

// Set headers for download
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file content
readfile($file_path);
exit();
?>