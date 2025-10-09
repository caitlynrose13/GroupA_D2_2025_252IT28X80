<?php
// filepath: src/process_upload.php
require_once __DIR__ . '/config/upload_limits.php'; // Load upload config first
session_start();
require_once __DIR__ . '/config/db.php';

// Check permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: content_upload.php');
    exit();
}

// Get form data
$upload_type = $_POST['upload_type'] ?? '';
$content_group_id = !empty($_POST['content_group_id']) ? (int)$_POST['content_group_id'] : null;
$language_id = !empty($_POST['language_id']) ? (int)$_POST['language_id'] : null;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$base_title = trim($_POST['base_title'] ?? '');
$content_type = $_POST['content_type'] ?? '';
$month_number = !empty($_POST['month_number']) ? (int)$_POST['month_number'] : null;

// Validate required fields based on upload type
if (empty($upload_type) || empty($language_id) || empty($title) || !isset($_FILES['file'])) {
    header('Location: content_upload.php?error=' . urlencode('Please fill in all required fields'));
    exit();
}

if ($upload_type === 'new' && (empty($base_title) || empty($content_type))) {
    header('Location: content_upload.php?error=' . urlencode('Base title and content type are required for new content'));
    exit();
}

if ($upload_type === 'translation' && empty($content_group_id)) {
    header('Location: content_upload.php?error=' . urlencode('Please select an existing content group for translations'));
    exit();
}

// File upload handling
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$file = $_FILES['file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_error = $file['error'];

// Check for upload errors with detailed messages
if ($file_error !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'PHP extension stopped the upload'
    ];
    $error_msg = $error_messages[$file_error] ?? 'Unknown upload error (Code: ' . $file_error . ')';
    header('Location: content_upload.php?error=' . urlencode($error_msg));
    exit();
}

// Check file size (100MB limit should work for your 7MB file)
if ($file_size > 100 * 1024 * 1024) {
    header('Location: content_upload.php?error=' . urlencode('File too large. Maximum 100MB allowed'));
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
    // Start transaction for data consistency
    $pdo->beginTransaction();
    
    // Determine organization_id (NULL for global content, org ID for org-specific)
    $organization_id = ($_SESSION['role'] === 'system_admin') ? null : $_SESSION['organization_id'];
    
    if ($upload_type === 'new') {
        // Create new content group
        
        // Get or create content type ID
        $type_stmt = $pdo->prepare("SELECT id FROM content_types WHERE name = :type LIMIT 1");
        $type_stmt->execute(['type' => $content_type]);
        $content_type_row = $type_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$content_type_row) {
            // If content type doesn't exist, create it
            $insert_type = $pdo->prepare("INSERT INTO content_types (name, description) VALUES (:name, :description)");
            $insert_type->execute([
                'name' => $content_type,
                'description' => ucfirst($content_type) . ' content'
            ]);
            $content_type_id = $pdo->lastInsertId();
        } else {
            $content_type_id = $content_type_row['id'];
        }
        
        // Create content group
        $group_stmt = $pdo->prepare("
            INSERT INTO content_groups (organization_id, base_title, description, content_type_id, month_number, created_by, is_active) 
            VALUES (:organization_id, :base_title, :description, :content_type_id, :month_number, :created_by, 1)
        ");
        
        $group_stmt->execute([
            'organization_id' => $organization_id,
            'base_title' => $base_title,
            'description' => $description,
            'content_type_id' => $content_type_id,
            'month_number' => $month_number,
            'created_by' => $_SESSION['user_id']
        ]);
        
        $content_group_id = $pdo->lastInsertId();
        
    } else {
        // Translation mode - verify access to existing group
        $group_check_where = ($_SESSION['role'] === 'system_admin') ? '1=1' : '(organization_id IS NULL OR organization_id = :org_id)';
        $group_check_params = ['group_id' => $content_group_id];
        if ($_SESSION['role'] !== 'system_admin') {
            $group_check_params['org_id'] = $_SESSION['organization_id'];
        }
        
        $group_check = $pdo->prepare("SELECT id FROM content_groups WHERE id = :group_id AND $group_check_where AND is_active = 1 LIMIT 1");
        $group_check->execute($group_check_params);
        
        if (!$group_check->fetch()) {
            throw new Exception('Content group not found or access denied');
        }
    }
    
    // Check if this language already exists for this content group
    $lang_check = $pdo->prepare("SELECT id FROM content WHERE content_group_id = :group_id AND language_id = :lang_id LIMIT 1");
    $lang_check->execute(['group_id' => $content_group_id, 'lang_id' => $language_id]);
    
    if ($lang_check->fetch()) {
        throw new Exception('Content in this language already exists for this content group. Please delete the existing version first.');
    }
    
    // Insert content record
    $content_stmt = $pdo->prepare("
        INSERT INTO content (content_group_id, language_id, title, description, file_path, file_name, file_size, uploaded_by, is_active) 
        VALUES (:content_group_id, :language_id, :title, :description, :file_path, :file_name, :file_size, :uploaded_by, 1)
    ");
    
    $content_stmt->execute([
        'content_group_id' => $content_group_id,
        'language_id' => $language_id,
        'title' => $title,
        'description' => $description,
        'file_path' => 'uploads/' . $unique_name,
        'file_name' => $file_name,
        'file_size' => $file_size,
        'uploaded_by' => $_SESSION['user_id']
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    $success_message = ($upload_type === 'new') ? 'New content uploaded successfully!' : 'Translation added successfully!';
    header('Location: content_upload.php?success=' . urlencode($success_message));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollBack();
    
    // Delete uploaded file if database operations fail
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    error_log("Content upload error: " . $e->getMessage());
    header('Location: content_upload.php?error=' . urlencode('Upload failed: ' . $e->getMessage()));
    exit();
}
?>