<?php
// filepath: src/content_upload.php
require_once __DIR__ . '/config/upload_limits.php'; // Load upload config first
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';

// Check if user is logged in and has upload permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied. Upload permission required.'));
    exit();
}

// Get existing content groups for linking translations
$existing_groups = [];
$selected_group = $_GET['group_id'] ?? '';

try {
    // Build query with multi-tenant security for existing groups
    if ($_SESSION['role'] === 'system_admin') {
        $groups_where = '1=1';
        $groups_params = [];
    } else {
        $groups_where = '(cg.organization_id IS NULL OR cg.organization_id = :org_id)';
        $groups_params = ['org_id' => $_SESSION['organization_id']];
    }
    
    $stmt = $pdo->prepare("
        SELECT cg.*, ct.name as content_type_name, 
               GROUP_CONCAT(l.name || ' (' || l.code || ')') as available_languages,
               COUNT(c.id) as language_count
        FROM content_groups cg 
        LEFT JOIN content_types ct ON cg.content_type_id = ct.id
        LEFT JOIN content c ON c.content_group_id = cg.id
        LEFT JOIN languages l ON c.language_id = l.id
        WHERE cg.is_active = 1 AND $groups_where
        GROUP BY cg.id
        ORDER BY cg.created_at DESC
    ");
    $stmt->execute($groups_params);
    $existing_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $existing_groups = [];
}

// Get available languages
try {
    $lang_stmt = $pdo->prepare("SELECT * FROM languages WHERE is_active = 1 ORDER BY name");
    $lang_stmt->execute();
    $languages = $lang_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $languages = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content - Cybersecurity Platform</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
</head>
<body>
    <!-- Pattern Border -->
    <div class="african-border"></div>
    
    <div class="header">
        <div class="header-left">
            <h1>Cybersecurity Platform</h1>
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
        <div class="page-header">
            <h2>Upload Content</h2>
            <p>Share cybersecurity training materials with your <?php echo $_SESSION['role'] === 'system_admin' ? 'platform' : 'organization'; ?></p>
        </div>
        
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars(urldecode($error)); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" action="process_upload.php" enctype="multipart/form-data">
                <!-- Language and Content Group Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="upload_type">Upload Type <span class="required">*</span></label>
                        <select id="upload_type" name="upload_type" required onchange="toggleGroupSelection()">
                            <option value="">Select upload type...</option>
                            <option value="new">New Content (Create new content group)</option>
                            <option value="translation" <?php echo $selected_group ? 'selected' : ''; ?>>Translation (Add to existing content group)</option>
                        </select>
                    </div>
                </div>

                <!-- Existing Content Group Selection (for translations) -->
                <div class="form-row" id="group_selection" style="display: <?php echo $selected_group ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label for="content_group_id">Existing Content Group</label>
                        <select id="content_group_id" name="content_group_id">
                            <option value="">Select content group...</option>
                            <?php foreach ($existing_groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>" 
                                        <?php echo $selected_group == $group['id'] ? 'selected' : ''; ?>
                                        data-type="<?php echo htmlspecialchars($group['content_type_name']); ?>"
                                        data-month="<?php echo $group['month_number']; ?>">
                                    <?php echo htmlspecialchars($group['base_title']); ?>
                                    (<?php echo htmlspecialchars($group['content_type_name']); ?>)
                                    <?php if ($group['month_number']): ?>
                                        - Month <?php echo $group['month_number']; ?>
                                    <?php endif; ?>
                                    - <?php echo $group['language_count']; ?> language(s)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Available languages in selected group will be shown below</small>
                    </div>
                </div>

                <!-- Language Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="language_id">Language <span class="required">*</span></label>
                        <select id="language_id" name="language_id" required>
                            <option value="">Select language...</option>
                            <?php foreach ($languages as $language): ?>
                                <option value="<?php echo $language['id']; ?>">
                                    <?php echo htmlspecialchars($language['name']); ?> (<?php echo htmlspecialchars($language['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Content Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required maxlength="255" placeholder="Enter content title in selected language">
                        <small class="form-help">This title should be in the selected language</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Brief description of the content in the selected language..."></textarea>
                    </div>
                </div>

                <!-- Base Content Information (for new content groups only) -->
                <div id="new_content_fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="base_title">Base Content Title <span class="required">*</span></label>
                            <input type="text" id="base_title" name="base_title" maxlength="255" placeholder="Universal title for this content (language-neutral)">
                            <small class="form-help">This is the main title that will group all language versions together</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="content_type">Content Type <span class="required">*</span></label>
                            <select id="content_type" name="content_type">
                                <option value="">Select Type...</option>
                                <option value="document">Document</option>
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                                <option value="misc">Miscellaneous</option>
                            </select>
                        </div>
                        
                        <div class="form-group half-width">
                            <label for="month_number">Program Month</label>
                            <select id="month_number" name="month_number">
                                <option value="">Select Month...</option>
                                <?php 
                                foreach (PROGRAM_MONTHS as $month_num => $month_info): 
                                ?>
                                    <option value="<?php echo $month_num; ?>">
                                        Month <?php echo $month_num; ?>: <?php echo htmlspecialchars($month_info['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-help">Leave blank for general content</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="file">Upload File <span class="required">*</span></label>
                        <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi">
                        <small class="form-help">Supported: PDF, Word, PowerPoint, Images, Videos (Max: 100MB)</small>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'system_admin'): ?>
                <div class="form-row">
                    <div class="admin-info">
                        <div class="info-badge">
                            <strong>System Administrator Upload</strong>
                            <p>This content will be available to all organizations on the platform</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="form-row">
                    <div class="admin-info">
                        <div class="info-badge org-admin">
                            <strong>Organization Upload</strong>
                            <p>This content will be available only to your organization</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="uploadBtn">Upload Content</button>
                    <a href="content_list.php" class="btn-secondary">Cancel</a>
                    <div id="uploadProgress" style="display: none; margin-top: 10px;">
                        <div style="color: var(--accent-orange); font-weight: bold;">
                            <span id="uploadText">Uploading... Please wait</span>
                        </div>
                        <div style="background-color: #f0f0f0; border-radius: 10px; overflow: hidden; margin-top: 5px;">
                            <div id="progressBar" style="width: 0%; height: 20px; background-color: var(--accent-orange); transition: width 0.3s;"></div>
                        </div>
                        <small style="color: #666; margin-top: 5px; display: block;">Large files may take several minutes to upload</small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleGroupSelection() {
            const uploadType = document.getElementById('upload_type').value;
            const groupSelection = document.getElementById('group_selection');
            const newContentFields = document.getElementById('new_content_fields');
            const contentGroupSelect = document.getElementById('content_group_id');
            const contentTypeSelect = document.getElementById('content_type');
            const monthSelect = document.getElementById('month_number');
            
            if (uploadType === 'translation') {
                groupSelection.style.display = 'block';
                newContentFields.style.display = 'none';
                
                // Make group selection required for translations
                contentGroupSelect.required = true;
                contentTypeSelect.required = false;
                document.getElementById('base_title').required = false;
                
                // Auto-populate fields when group is selected
                contentGroupSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        contentTypeSelect.value = selectedOption.dataset.type || '';
                        monthSelect.value = selectedOption.dataset.month || '';
                    }
                });
                
            } else if (uploadType === 'new') {
                groupSelection.style.display = 'none';
                newContentFields.style.display = 'block';
                
                // Make content type required for new content
                contentGroupSelect.required = false;
                contentTypeSelect.required = true;
                document.getElementById('base_title').required = true;
                
            } else {
                groupSelection.style.display = 'none';
                newContentFields.style.display = 'none';
                contentGroupSelect.required = false;
                contentTypeSelect.required = false;
                document.getElementById('base_title').required = false;
            }
        }

        // File upload progress tracking
        function handleFormSubmit() {
            const form = document.querySelector('form');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadText = document.getElementById('uploadText');
            const progressBar = document.getElementById('progressBar');
            const fileInput = document.getElementById('file');
            
            form.addEventListener('submit', function(e) {
                const file = fileInput.files[0];
                
                if (file) {
                    // Show upload progress
                    uploadBtn.disabled = true;
                    uploadBtn.textContent = 'Uploading...';
                    uploadProgress.style.display = 'block';
                    
                    // Calculate estimated time based on file size
                    const fileSizeMB = file.size / (1024 * 1024);
                    let estimatedSeconds = Math.max(5, Math.floor(fileSizeMB / 2)); // Rough estimate: 2MB/second
                    
                    if (fileSizeMB > 100) {
                        uploadText.textContent = `Uploading large file (${fileSizeMB.toFixed(1)}MB)... This may take several minutes`;
                        estimatedSeconds = Math.floor(fileSizeMB / 1); // Slower for large files
                    } else {
                        uploadText.textContent = `Uploading ${fileSizeMB.toFixed(1)}MB...`;
                    }
                    
                    // Simulate progress bar (since we can't track actual upload progress easily)
                    let progress = 0;
                    const interval = setInterval(() => {
                        progress += Math.random() * 15;
                        if (progress > 95) progress = 95; // Don't complete until actual upload finishes
                        progressBar.style.width = progress + '%';
                    }, 1000);
                    
                    // Clear interval after estimated time
                    setTimeout(() => {
                        clearInterval(interval);
                        progressBar.style.width = '100%';
                        uploadText.textContent = 'Processing upload...';
                    }, estimatedSeconds * 1000);
                }
            });
        }

        // Initialize form state on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleGroupSelection();
            handleFormSubmit();
            
            // If there's a pre-selected group, trigger the change event
            const groupSelect = document.getElementById('content_group_id');
            if (groupSelect.value) {
                const uploadTypeSelect = document.getElementById('upload_type');
                uploadTypeSelect.value = 'translation';
                toggleGroupSelection();
                groupSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>