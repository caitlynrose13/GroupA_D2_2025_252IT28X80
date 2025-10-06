<?php
// filepath: src/content_list.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';

// Handle delete action
if (isset($_GET['delete']) && in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    $delete_id = $_GET['delete'];
    
    try {
        // Security check: ensure user can only delete content they have access to
        if ($_SESSION['role'] === 'system_admin') {
            $security_where = '1=1';
            $security_params = ['id' => $delete_id];
        } else {
            $security_where = '(organization_id IS NULL OR organization_id = :org_id)';
            $security_params = ['id' => $delete_id, 'org_id' => $_SESSION['organization_id']];
        }
        
        // Get file path before deleting from database
        $stmt = $pdo->prepare("SELECT file_path FROM content WHERE id = :id AND $security_where LIMIT 1");
        $stmt->execute($security_params);
        $content_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($content_to_delete) {
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM content WHERE id = :id AND $security_where");
            $stmt->execute($security_params);
            
            // Delete physical file
            if ($content_to_delete['file_path']) {
                $file_path = __DIR__ . '/' . $content_to_delete['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            header('Location: content_list.php?success=' . urlencode('Content deleted successfully'));
            exit();
        } else {
            header('Location: content_list.php?error=' . urlencode('Content not found or access denied'));
            exit();
        }
    } catch (PDOException $e) {
        header('Location: content_list.php?error=' . urlencode('Error deleting content'));
        exit();
    }
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$filter_type = $_GET['type'] ?? '';
$filter_month = $_GET['month'] ?? '';

// Build query with filters and multi-tenant security
$where_conditions = ['c.is_active = 1'];
$params = [];

// Multi-tenant security
if ($_SESSION['role'] === 'system_admin') {
    // System admin can see all content
} else {
    // Regular users can only see global content + their organization's content
    $where_conditions[] = '(c.organization_id IS NULL OR c.organization_id = :org_id)';
    $params['org_id'] = $_SESSION['organization_id'];
}

// Add filters
if (!empty($filter_type)) {
    $where_conditions[] = 'ct.name = :type';
    $params['type'] = $filter_type;
}
if (!empty($filter_month)) {
    $where_conditions[] = 'c.month_number = :month';
    $params['month'] = $filter_month;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $stmt = $pdo->prepare("
        SELECT c.*, ct.name as content_type_name, o.name as organization_name,
               u.first_name || ' ' || u.last_name as uploaded_by_name
        FROM content c 
        LEFT JOIN content_types ct ON c.content_type_id = ct.id
        LEFT JOIN organizations o ON c.organization_id = o.id
        LEFT JOIN users u ON c.uploaded_by = u.id
        WHERE $where_clause 
        ORDER BY c.month_number ASC, c.created_at DESC
    ");
    $stmt->execute($params);
    $content_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $content_list = [];
    $error = "Error loading content: " . $e->getMessage();
}

// Get available content types for filter
try {
    $type_stmt = $pdo->prepare("SELECT DISTINCT name FROM content_types ORDER BY name");
    $type_stmt->execute();
    $available_types = $type_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $available_types = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Library - South African SMME Cybersecurity Portal</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <div class="african-header">
        <div class="african-border"></div>
        <div class="african-header-content">
            <h1>📚 Content Library</h1>
            <p>Cybersecurity training materials for South African SMMEs</p>
        </div>
        <div class="african-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                <a href="content_upload.php">Upload Content</a>
            <?php endif; ?>
            <a href="quiz_list.php">Quiz Library</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="african-container">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="african-success-message">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="african-error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="african-card african-filters">
            <h3 style="margin-bottom: 20px; color: var(--primary-dark);">🔍 Filter Content</h3>
            <form method="GET" action="">
                <div class="african-filter-row">
                    <div class="african-filter-group">
                        <label for="type">Content Type</label>
                        <select id="type" name="type" class="african-select">
                            <option value="">All Types</option>
                            <?php foreach ($available_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="african-filter-group">
                        <label for="month">Program Month</label>
                        <select id="month" name="month" class="african-select">
                            <option value="">All Months</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filter_month === (string)$i ? 'selected' : ''; ?>>
                                    Month <?php echo $i; ?> - <?php echo PROGRAM_STRUCTURE[$i]['theme']; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="african-filter-actions">
                        <button type="submit" class="african-btn african-btn-primary">🔍 Filter</button>
                        <a href="content_list.php" class="african-btn african-btn-secondary">🗑️ Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Content Grid -->
        <?php if (empty($content_list)): ?>
            <div class="african-empty-state">
                <h3>📂 No content found</h3>
                <p>Try adjusting your filters or upload some content to get started.</p>
                <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                    <p><a href="content_upload.php" class="african-link">📤 Upload your first content</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="african-content-grid">
                <?php foreach ($content_list as $content): ?>
                    <div class="african-card african-content-card">
                        <div class="african-content-header">
                            <h3 class="african-content-title"><?php echo htmlspecialchars($content['title']); ?></h3>
                            <div class="african-content-meta">
                                <span class="african-badge african-badge-type">
                                    <?php echo htmlspecialchars($content['content_type_name']); ?>
                                </span>
                                <?php if ($content['month_number']): ?>
                                    <span class="african-badge african-badge-month">
                                        Month <?php echo $content['month_number']; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($content['organization_name']): ?>
                                    <span class="african-badge african-badge-org">
                                        <?php echo htmlspecialchars($content['organization_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="african-badge african-badge-global">Global</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($content['description']): ?>
                                <p class="african-content-description">
                                    <?php echo htmlspecialchars($content['description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="african-content-stats">
                                <?php if ($content['file_size']): ?>
                                    <span class="african-stat">📊 <?php echo number_format($content['file_size'] / 1024, 1); ?> KB</span>
                                <?php endif; ?>
                                <span class="african-stat">📅 <?php echo date('M j, Y', strtotime($content['created_at'])); ?></span>
                                <?php if ($content['uploaded_by_name']): ?>
                                    <span class="african-stat">👤 <?php echo htmlspecialchars($content['uploaded_by_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="african-content-actions">
                            <?php if ($content['external_url']): ?>
                                <a href="<?php echo htmlspecialchars($content['external_url']); ?>" 
                                   target="_blank" class="african-btn african-btn-primary">
                                    🌐 Open Link
                                </a>
                            <?php elseif ($content['file_path']): ?>
                                <a href="view_content.php?id=<?php echo $content['id']; ?>" 
                                   class="african-btn african-btn-primary">
                                    👁️ View
                                </a>
                                <a href="download_content.php?id=<?php echo $content['id']; ?>" 
                                   class="african-btn african-btn-secondary">
                                    📥 Download
                                </a>
                            <?php endif; ?>
                            
                            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                                <a href="content_list.php?delete=<?php echo $content['id']; ?>" 
                                   class="african-btn african-btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this content?')">
                                    🗑️ Delete
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>