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
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = :id AND is_active = 1 LIMIT 1");
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

$file_path = __DIR__ . '/' . $content['file_path'];

if (!file_exists($file_path)) {
    header('Location: content_list.php?error=' . urlencode('File not found'));
    exit();
}

// Sanitize filename
$safe_title = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $content['title']);
$safe_title = trim($safe_title);
if (empty($safe_title)) {
    $safe_title = 'content_' . $content['id'];
}

$filename = $safe_title . '.' . $content['file_type'];

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

$content_type = $content_types[$content['file_type']] ?? 'application/octet-stream';

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