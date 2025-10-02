<?php
// filepath: src/content_list.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';

// Handle delete action
if (isset($_GET['delete']) && in_array($_SESSION['role'], ['admin', 'org_admin'])) {
    $delete_id = $_GET['delete'];
    
    try {
        // Get file path before deleting from database
        $stmt = $pdo->prepare("SELECT file_path FROM content WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $delete_id]);
        $content_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($content_to_delete) {
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM content WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            
            // Delete physical file
            $file_path = __DIR__ . '/../' . $content_to_delete['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            header('Location: content_list.php?success=' . urlencode('Content deleted successfully'));
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

// Build query with filters
$where_conditions = ['is_active = 1'];
$params = [];

if (!empty($filter_type)) {
    $where_conditions[] = 'content_type = :type';
    $params['type'] = $filter_type;
}
if (!empty($filter_month)) {
    $where_conditions[] = 'month_number = :month';
    $params['month'] = $filter_month;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $stmt = $pdo->prepare("
        SELECT * FROM content 
        WHERE $where_clause 
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $content_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $content_list = [];
    $error = "Error loading content: " . $e->getMessage();
}
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Library - Cybersecurity Awareness</title>
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }
        .filter-group {
            flex: 1;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
        }
        .filter-btn, .clear-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .filter-btn {
            background: #3498db;
            color: white;
        }
        .clear-btn {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .content-card:hover {
            transform: translateY(-5px);
        }
        .content-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .content-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .content-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .meta-badge {
            background: #ecf0f1;
            color: #555;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .content-description {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }
        .content-actions {
            padding: 15px 20px;
            background: #f8f9fa;
        }
        .view-btn {
            background: #27ae60;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }
        .view-btn:hover {
            background: #219a52;
        }
        .delete-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Content Library</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <?php if (in_array($_SESSION['role'], ['admin', 'org_admin'])): ?>
                <a href="content_upload.php">Upload Content</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="success-message" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="type">Content Type</label>
                        <select id="type" name="type">
                            <option value="">All Types</option>
                            <option value="document" <?php echo $filter_type === 'document' ? 'selected' : ''; ?>>Documents</option>
                            <option value="image" <?php echo $filter_type === 'image' ? 'selected' : ''; ?>>Images</option>
                            <option value="video" <?php echo $filter_type === 'video' ? 'selected' : ''; ?>>Videos</option>
                            <option value="misc" <?php echo $filter_type === 'misc' ? 'selected' : ''; ?>>Miscellaneous</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="cycle">Cycle</label>
                        <select id="cycle" name="cycle">
                            <option value="">All Cycles</option>
                            <option value="1" <?php echo $filter_cycle === '1' ? 'selected' : ''; ?>>Cycle 1</option>
                            <option value="2" <?php echo $filter_cycle === '2' ? 'selected' : ''; ?>>Cycle 2</option>
                            <option value="3" <?php echo $filter_cycle === '3' ? 'selected' : ''; ?>>Cycle 3</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="month">Month</label>
                        <select id="month" name="month">
                            <option value="">All Months</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filter_month === (string)$i ? 'selected' : ''; ?>>Month <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="filter-btn">Filter</button>
                        <a href="content_list.php" class="clear-btn">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Content Grid -->
        <?php if (empty($content_list)): ?>
            <div class="empty-state">
                <h3>No content found</h3>
                <p>Try adjusting your filters or upload some content to get started.</p>
            </div>
        <?php else: ?>
            <div class="content-grid">
                <?php foreach ($content_list as $content): ?>
                    <div class="content-card">
                        <div class="content-header">
                            <div class="content-title"><?php echo htmlspecialchars($content['title']); ?></div>
                            <div class="content-meta">
                                <span class="meta-badge"><?php echo ucfirst($content['content_type']); ?></span>
                                <?php if ($content['cycle_number']): ?>
                                    <span class="meta-badge">Cycle <?php echo $content['cycle_number']; ?></span>
                                <?php endif; ?>
                                <?php if ($content['month_number']): ?>
                                    <span class="meta-badge">Month <?php echo $content['month_number']; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($content['description']): ?>
                                <div class="content-description"><?php echo htmlspecialchars($content['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="content-actions">
                            <a href="view_content.php?id=<?php echo $content['id']; ?>" class="view-btn">View Content</a>
                            <?php if (in_array($_SESSION['role'], ['admin', 'org_admin'])): ?>
                                <a href="content_list.php?delete=<?php echo $content['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this content?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>