<?php
// filepath: src/pdf_viewer.php
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

// Set headers to allow PDF display in browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($content['file_path']) . '"');
header('Content-Length: ' . filesize($file_path));
header('Accept-Ranges: bytes');
header('Content-Security-Policy: frame-ancestors \'self\'');
header('Cache-Control: private, max-age=3600');

// Output the PDF
readfile($file_path);
exit();
?>
