<?php
// filepath: src/content_list.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';

// Handle delete action for content groups
if (isset($_GET['delete_group']) && in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    $delete_group_id = $_GET['delete_group'];
    
    try {
        // Security check: ensure user can only delete content they have access to
        if ($_SESSION['role'] === 'system_admin') {
            $security_where = '1=1';
            $security_params = ['id' => $delete_group_id];
        } else {
            $security_where = '(organization_id IS NULL OR organization_id = :org_id)';
            $security_params = ['id' => $delete_group_id, 'org_id' => $_SESSION['organization_id']];
        }
        
        // Get all file paths before deleting from database
        $stmt = $pdo->prepare("
            SELECT c.file_path 
            FROM content c 
            INNER JOIN content_groups cg ON c.content_group_id = cg.id 
            WHERE cg.id = :id AND $security_where
        ");
        $stmt->execute($security_params);
        $content_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if group exists and user has access
        $check_stmt = $pdo->prepare("SELECT id FROM content_groups WHERE id = :id AND $security_where LIMIT 1");
        $check_stmt->execute($security_params);
        
        if ($check_stmt->fetch()) {
            // Delete from database (content will be deleted by CASCADE)
            $delete_stmt = $pdo->prepare("DELETE FROM content_groups WHERE id = :id AND $security_where");
            $delete_stmt->execute($security_params);
            
            // Delete physical files
            foreach ($content_files as $file_path) {
                if ($file_path) {
                    $full_path = __DIR__ . '/' . $file_path;
                    if (file_exists($full_path)) {
                        unlink($full_path);
                    }
                }
            }
            
            header('Location: content_list.php?success=' . urlencode('Content group and all translations deleted successfully'));
            exit();
        } else {
            header('Location: content_list.php?error=' . urlencode('Content group not found or access denied'));
            exit();
        }
    } catch (PDOException $e) {
        header('Location: content_list.php?error=' . urlencode('Error deleting content group'));
        exit();
    }
}

// Handle delete action for individual content (specific language)
if (isset($_GET['delete']) && in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    $delete_id = $_GET['delete'];
    
    try {
        // Security check with content groups
        if ($_SESSION['role'] === 'system_admin') {
            $security_where = '1=1';
            $security_params = ['id' => $delete_id];
        } else {
            $security_where = '(cg.organization_id IS NULL OR cg.organization_id = :org_id)';
            $security_params = ['id' => $delete_id, 'org_id' => $_SESSION['organization_id']];
        }
        
        // Get file path before deleting from database
        $stmt = $pdo->prepare("
            SELECT c.file_path, c.content_group_id
            FROM content c 
            INNER JOIN content_groups cg ON c.content_group_id = cg.id 
            WHERE c.id = :id AND $security_where LIMIT 1
        ");
        $stmt->execute($security_params);
        $content_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($content_to_delete) {
            // Delete from database
            $stmt = $pdo->prepare("
                DELETE c FROM content c 
                INNER JOIN content_groups cg ON c.content_group_id = cg.id 
                WHERE c.id = :id AND $security_where
            ");
            $stmt->execute($security_params);
            
            // Delete physical file
            if ($content_to_delete['file_path']) {
                $file_path = __DIR__ . '/' . $content_to_delete['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            header('Location: content_list.php?success=' . urlencode('Content language version deleted successfully'));
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
$selected_language = $_GET['lang'] ?? 'en'; // Default to English

// Build query with filters and multi-tenant security for content groups
$where_conditions = ['cg.is_active = 1'];
$params = ['selected_lang' => $selected_language];

// Multi-tenant security
if ($_SESSION['role'] === 'system_admin') {
    // System admin can see all content
} else {
    // Regular users can only see global content + their organization's content
    $where_conditions[] = '(cg.organization_id IS NULL OR cg.organization_id = :org_id)';
    $params['org_id'] = $_SESSION['organization_id'];
}

// Add filters
if (!empty($filter_type)) {
    $where_conditions[] = 'ct.name = :type';
    $params['type'] = $filter_type;
}
if (!empty($filter_month)) {
    $where_conditions[] = 'cg.month_number = :month';
    $params['month'] = $filter_month;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get content groups with their available languages and selected language content
    $stmt = $pdo->prepare("
        SELECT cg.*, ct.name as content_type_name, o.name as organization_name,
               u.first_name || ' ' || u.last_name as created_by_name,
               GROUP_CONCAT(DISTINCT l.code || ':' || l.name) as available_languages,
               COUNT(DISTINCT c.language_id) as language_count,
               selected_content.id as selected_content_id,
               selected_content.title as selected_title,
               selected_content.description as selected_description,
               selected_content.file_path as selected_file_path,
               selected_content.file_name as selected_file_name,
               selected_content.file_size as selected_file_size,
               selected_content.external_url as selected_external_url,
               selected_lang.code as selected_lang_code,
               selected_lang.name as selected_lang_name
        FROM content_groups cg 
        LEFT JOIN content_types ct ON cg.content_type_id = ct.id
        LEFT JOIN organizations o ON cg.organization_id = o.id
        LEFT JOIN users u ON cg.created_by = u.id
        LEFT JOIN content c ON c.content_group_id = cg.id AND c.is_active = 1
        LEFT JOIN languages l ON c.language_id = l.id
        LEFT JOIN content selected_content ON selected_content.content_group_id = cg.id 
            AND selected_content.is_active = 1
            AND selected_content.language_id = (
                SELECT l2.id FROM languages l2 WHERE l2.code = :selected_lang LIMIT 1
            )
        LEFT JOIN languages selected_lang ON selected_content.language_id = selected_lang.id
        WHERE $where_clause 
        GROUP BY cg.id
        ORDER BY cg.month_number ASC, cg.created_at DESC
    ");
    $stmt->execute($params);
    $content_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $content_groups = [];
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

// Get available languages for language selector
try {
    $lang_stmt = $pdo->prepare("SELECT * FROM languages WHERE is_active = 1 ORDER BY name");
    $lang_stmt->execute();
    $available_languages = $lang_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $available_languages = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Library - SA SMME Cybersecurity Platform</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <!-- Pattern Border -->
    <div class="african-border"></div>
    
    <div class="header">
        <div class="header-left">
            <h1>SA SMME Cybersecurity Platform</h1>
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
        <div class="page-header-section">
            <div class="page-title-area">
                <h2 class="page-title">Content Library</h2>
                <p class="page-subtitle">Cybersecurity training materials for South African SMMEs</p>
            </div>
            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                <div class="page-actions">
                    <a href="content_upload.php" class="btn-primary create-quiz-btn">
                        <span class="btn-icon">📤</span>
                        <span class="btn-text">Upload Content</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="form-card" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px; color: var(--primary-dark);">Filter Content</h3>
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group third-width">
                        <label for="type">Content Type</label>
                        <select id="type" name="type">
                            <option value="">All Types</option>
                            <?php foreach ($available_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group third-width">
                        <label for="month">Program Month</label>
                        <select id="month" name="month">
                            <option value="">All Months</option>
                            <?php foreach (PROGRAM_MONTHS as $month_num => $month_info): ?>
                                <option value="<?php echo $month_num; ?>" <?php echo $filter_month === (string)$month_num ? 'selected' : ''; ?>>
                                    Month <?php echo $month_num; ?>: <?php echo htmlspecialchars($month_info['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group third-width">
                        <label for="lang">Display Language</label>
                        <select id="lang" name="lang" onchange="this.form.submit()">
                            <?php foreach ($available_languages as $language): ?>
                                <option value="<?php echo htmlspecialchars($language['code']); ?>" 
                                        <?php echo $selected_language === $language['code'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <a href="content_list.php" class="btn-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Content Grid -->
        <?php if (empty($content_groups)): ?>
            <div class="info-card" style="text-align: center; padding: 40px;">
                <h3>No content found</h3>
                <p>Try adjusting your filters or upload some content to get started.</p>
                <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                    <div style="margin-top: 20px;">
                        <a href="content_upload.php" class="btn-primary">Upload your first content</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="content-grid">
                <?php foreach ($content_groups as $group): ?>
                    <div class="content-card">
                        <div class="content-header">
                            <h3><?php echo htmlspecialchars($group['selected_title'] ?: $group['base_title']); ?></h3>
                            
                            <div class="content-badges">
                                <span class="badge badge-type">
                                    <?php echo htmlspecialchars($group['content_type_name']); ?>
                                </span>
                                <?php if ($group['month_number']): ?>
                                    <span class="badge badge-month">
                                        Month <?php echo $group['month_number']; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($group['organization_name']): ?>
                                    <span class="badge badge-org">
                                        <?php echo htmlspecialchars($group['organization_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-global">Global</span>
                                <?php endif; ?>
                                <span class="badge badge-lang">
                                    <?php echo $group['language_count']; ?> language<?php echo $group['language_count'] != 1 ? 's' : ''; ?>
                                </span>
                            </div>
                            
                            <!-- Language Selection -->
                            <?php if ($group['available_languages']): ?>
                                <div class="language-selector" style="margin: 10px 0;">
                                    <label style="font-size: 0.9em; color: #666;">Available in:</label>
                                    <div class="language-options" style="margin-top: 5px;">
                                        <?php 
                                        $languages = explode(',', $group['available_languages']);
                                        foreach ($languages as $lang_info): 
                                            if (trim($lang_info)) {
                                                list($code, $name) = explode(':', trim($lang_info), 2);
                                                $is_current = ($code === $selected_language);
                                                $link_params = $_GET;
                                                $link_params['lang'] = $code;
                                                $link_url = 'content_list.php?' . http_build_query($link_params);
                                        ?>
                                            <a href="<?php echo htmlspecialchars($link_url); ?>" 
                                               class="language-option <?php echo $is_current ? 'active' : ''; ?>"
                                               style="display: inline-block; margin-right: 8px; padding: 2px 8px; 
                                                      border: 1px solid #ddd; border-radius: 3px; font-size: 0.8em; 
                                                      text-decoration: none; color: #333;
                                                      <?php echo $is_current ? 'background-color: #007cba; color: white;' : ''; ?>">
                                                <?php echo htmlspecialchars($name); ?>
                                            </a>
                                        <?php 
                                            }
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($group['selected_description'] || $group['description']): ?>
                                <p class="content-description">
                                    <?php echo htmlspecialchars($group['selected_description'] ?: $group['description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="content-meta">
                                <?php if ($group['selected_file_size']): ?>
                                    <span><?php echo number_format($group['selected_file_size'] / 1024, 1); ?> KB</span>
                                <?php endif; ?>
                                <span><?php echo date('M j, Y', strtotime($group['created_at'])); ?></span>
                                <?php if ($group['created_by_name']): ?>
                                    <span>by <?php echo htmlspecialchars($group['created_by_name']); ?></span>
                                <?php endif; ?>
                                <?php if ($group['selected_lang_name']): ?>
                                    <span>in <?php echo htmlspecialchars($group['selected_lang_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="content-actions">
                            <?php if ($group['selected_content_id']): ?>
                                <!-- Content is available in selected language -->
                                <?php if ($group['selected_external_url']): ?>
                                    <a href="<?php echo htmlspecialchars($group['selected_external_url']); ?>" 
                                       target="_blank" class="btn-primary">
                                        Open Link
                                    </a>
                                <?php elseif ($group['selected_file_path']): ?>
                                    <a href="view_content.php?id=<?php echo $group['selected_content_id']; ?>" 
                                       class="btn-primary">
                                        View
                                    </a>
                                    <a href="download_content.php?id=<?php echo $group['selected_content_id']; ?>" 
                                       class="btn-secondary">
                                        Download
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Content not available in selected language -->
                                <div class="not-available">
                                    <span style="color: #999; font-style: italic;">
                                        Not available in <?php echo htmlspecialchars($selected_language); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (in_array($_SESSION['role'], ['system_admin', 'org_admin'])): ?>
                                <!-- Add translation link -->
                                <a href="content_upload.php?group_id=<?php echo $group['id']; ?>" 
                                   class="btn-secondary" title="Add translation">
                                    + Translation
                                </a>
                                
                                <?php if ($group['selected_content_id']): ?>
                                    <!-- Delete specific language version -->
                                    <a href="content_list.php?delete=<?php echo $group['selected_content_id']; ?>" 
                                       class="btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this language version?')">
                                        Delete <?php echo htmlspecialchars($group['selected_lang_name'] ?: $selected_language); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Delete entire content group -->
                                <a href="content_list.php?delete_group=<?php echo $group['id']; ?>" 
                                   class="btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this entire content group and ALL language versions?')">
                                    Delete All
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