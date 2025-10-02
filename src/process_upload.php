<?php
// filepath: src/process_upload.php
session_start();
require_once __DIR__ . '/config/db.php';

// Check permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: content_upload.php');
    exit();
}

// Get form data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$content_type = $_POST['content_type'] ?? '';
$cycle_number = !empty($_POST['cycle_number']) ? (int)$_POST['cycle_number'] : null;
$month_number = !empty($_POST['month_number']) ? (int)$_POST['month_number'] : null;

// Validate required fields
if (empty($title) || empty($content_type) || !isset($_FILES['file'])) {
    header('Location: content_upload.php?error=' . urlencode('Please fill in all required fields'));
    exit();
}

// File upload handling
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$file = $_FILES['file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_error = $file['error'];

// Check for upload errors
if ($file_error !== UPLOAD_ERR_OK) {
    header('Location: content_upload.php?error=' . urlencode('File upload failed'));
    exit();
}

// Check file size (10MB limit)
if ($file_size > 10 * 1024 * 1024) {
    header('Location: content_upload.php?error=' . urlencode('File too large. Maximum 10MB allowed'));
    exit();
}

// Get file extension and type
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];

if (!in_array($file_ext, $allowed_extensions)) {
    header('Location: content_upload.php?error=' . urlencode('File type not allowed'));
    exit();
}

// Generate unique filename
$unique_name = time() . '_' . uniqid() . '.' . $file_ext;
$file_path = $upload_dir . $unique_name;

// Move uploaded file
if (!move_uploaded_file($file_tmp, $file_path)) {
    header('Location: content_upload.php?error=' . urlencode('Failed to save file'));
    exit();
}

try {
    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO content (title, description, file_path, file_type, content_type, cycle_number, month_number, is_active) 
        VALUES (:title, :description, :file_path, :file_type, :content_type, :cycle_number, :month_number, 1)
    ");
    
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'file_path' => 'uploads/' . $unique_name,
        'file_type' => $file_ext,
        'content_type' => $content_type,
        'cycle_number' => $cycle_number,
        'month_number' => $month_number
    ]);
    
    header('Location: content_upload.php?success=' . urlencode('Content uploaded successfully!'));
    exit();
    
} catch (PDOException $e) {
    // Delete uploaded file if database insert fails
    unlink($file_path);
    error_log("Content upload error: " . $e->getMessage());
    header('Location: content_upload.php?error=' . urlencode('Database error. Please try again.'));
    exit();
}
?>