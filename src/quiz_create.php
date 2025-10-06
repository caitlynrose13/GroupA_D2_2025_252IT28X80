<?php
// filepath: src/quiz_create.php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/program_structure.php';

// Check if user is logged in and has quiz creation permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

if (!in_array($_SESSION['role'], ['system_admin', 'org_admin'])) {
    header('Location: dashboard.php?error=' . urlencode('Access denied. Quiz creation permission required.'));
    exit();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - SA SMME Cybersecurity Platform</title>
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
                <h2 class="page-title">Create New Quiz</h2>
                <p class="page-subtitle">Design cybersecurity assessments for your program</p>
            </div>
            <div class="page-actions">
                <a href="quiz_list.php" class="btn-secondary">
                    <span>↩️</span> Back to Quizzes
                </a>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
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
        
        <form method="POST" action="process_quiz.php" id="quizForm">
            <!-- Quiz Details -->
            <div class="form-card">
                <h3 style="margin-bottom: 25px; color: var(--primary-dark); border-bottom: 2px solid var(--light-cream); padding-bottom: 10px;">
                    Quiz Details
                </h3>
                
                <div class="form-group">
                    <label for="title">Quiz Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required maxlength="255" placeholder="e.g., Cycle 1 Assessment - Phishing & Password Security">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Brief description of the quiz content and objectives..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="month_number">Program Month <span class="required">*</span></label>
                        <select id="month_number" name="month_number" required>
                            <option value="">Select Month...</option>
                            <?php foreach (PROGRAM_MONTHS as $month_num => $month_info): ?>
                                <option value="<?php echo $month_num; ?>">
                                    Month <?php echo $month_num; ?>: <?php echo htmlspecialchars($month_info['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group half-width">
                        <label for="passing_score">Passing Score (%) <span class="required">*</span></label>
                        <input type="number" id="passing_score" name="passing_score" min="50" max="100" value="70" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="status_id">Quiz Status <span class="required">*</span></label>
                        <select id="status_id" name="status_id" required>
                            <option value="3">Published (Immediately Available)</option>
                            <option value="1">Draft (Not Visible to Users)</option>
                            <option value="2">Scheduled (Will Auto-Publish)</option>
                        </select>
                    </div>
                    
                    <div class="form-group half-width">
                        <label for="time_limit_minutes">Time Limit (minutes)</label>
                        <input type="number" id="time_limit_minutes" name="time_limit_minutes" min="5" max="120" placeholder="Optional - leave blank for no limit">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="african-checkbox-wrapper">
                        <input type="checkbox" id="requires_previous_completion" name="requires_previous_completion" value="1" checked class="african-checkbox">
                        <label for="requires_previous_completion" class="african-checkbox-label">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Require completion of previous quiz before accessing this one</span>
                        </label>
                    </div>
                    <small class="form-help">
                        Uncheck only for the first quiz in a series. This ensures progressive learning.
                    </small>
                </div>
            </div>
            
            <!-- Questions Section -->
            <div class="form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid var(--light-cream); padding-bottom: 10px;">
                    <h3 style="margin: 0; color: var(--primary-dark);">❓ Quiz Questions</h3>
                    <button type="button" class="btn-primary add-question-btn" onclick="addQuestion()">
                        <span>➕</span> Add Question
                    </button>
                </div>
                
                <div id="questionsContainer">
                    <!-- Questions will be added by JavaScript -->
                </div>
                
                <div id="noQuestionsMessage" style="text-align: center; padding: 40px; color: var(--text-medium); font-style: italic;">
                    Click "Add Question" to start building your quiz
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary create-quiz-btn">
                    <span>💾</span> Create Quiz

            </div>
        </form>
    </div>
    
    <script>
        let questionCount = 0;
        
        function addQuestion() {
            questionCount++;
            const container = document.getElementById('questionsContainer');
            const noMessage = document.getElementById('noQuestionsMessage');
            
            if (noMessage) {
                noMessage.style.display = 'none';
            }
            
            const questionHtml = `
                <div class="quiz-question-card" id="question-${questionCount}">
                    <div class="question-header-bar">
                        <span class="question-number-badge">Question ${questionCount}</span>
                        <button type="button" class="btn-danger remove-question-btn" onclick="removeQuestion(${questionCount})">
                            <span>🗑️</span> Remove
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_text_${questionCount}">Question Text <span class="required">*</span></label>
                        <textarea name="questions[${questionCount}][text]" id="question_text_${questionCount}" rows="3" required placeholder="Enter your question here..." style="resize: vertical;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_type_${questionCount}">Question Type <span class="required">*</span></label>
                        <select name="questions[${questionCount}][type]" id="question_type_${questionCount}" required onchange="toggleOptions(${questionCount})">
                            <option value="">Select Type...</option>
                            <option value="multiple_choice">Multiple Choice (4 Options)</option>
                            <option value="true_false">True/False</option>
                        </select>
                    </div>
                    
                    <div id="options_${questionCount}" class="options-section" style="display: none;">
                        <!-- Options will be populated by JavaScript -->
                    </div>
                    
                    <input type="hidden" name="questions[${questionCount}][order]" value="${questionCount}">
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', questionHtml);
        }
        
        function removeQuestion(id) {
            if (questionCount <= 1) {
                alert('A quiz must have at least one question.');
                return;
            }
            
            const questionElement = document.getElementById(`question-${id}`);
            if (questionElement) {
                questionElement.remove();
                questionCount--;
                renumberQuestions();
                
                // Show no questions message if all removed
                if (questionCount === 0) {
                    document.getElementById('noQuestionsMessage').style.display = 'block';
                }
            }
        }
        
        function toggleOptions(questionId) {
            const typeSelect = document.getElementById(`question_type_${questionId}`);
            const optionsContainer = document.getElementById(`options_${questionId}`);
            const type = typeSelect.value;
            
            if (type === 'multiple_choice') {
                optionsContainer.innerHTML = `
                    <div class="options-grid">
                        <div class="option-item">
                            <div class="option-header">
                                <input type="radio" name="questions[${questionId}][correct]" value="A" required>
                                <label class="option-label">Option A (Correct Answer?)</label>
                            </div>
                            <input type="text" name="questions[${questionId}][option_a]" placeholder="Enter option A text..." required>
                        </div>
                        <div class="option-item">
                            <div class="option-header">
                                <input type="radio" name="questions[${questionId}][correct]" value="B" required>
                                <label class="option-label">Option B (Correct Answer?)</label>
                            </div>
                            <input type="text" name="questions[${questionId}][option_b]" placeholder="Enter option B text..." required>
                        </div>
                        <div class="option-item">
                            <div class="option-header">
                                <input type="radio" name="questions[${questionId}][correct]" value="C" required>
                                <label class="option-label">Option C (Correct Answer?)</label>
                            </div>
                            <input type="text" name="questions[${questionId}][option_c]" placeholder="Enter option C text..." required>
                        </div>
                        <div class="option-item">
                            <div class="option-header">
                                <input type="radio" name="questions[${questionId}][correct]" value="D" required>
                                <label class="option-label">Option D (Correct Answer?)</label>
                            </div>
                            <input type="text" name="questions[${questionId}][option_d]" placeholder="Enter option D text..." required>
                        </div>
                    </div>
                    <small class="form-help" style="margin-top: 15px; display: block;">
                        ⚠️ Select the radio button next to the correct answer option.
                    </small>
                `;
                optionsContainer.style.display = 'block';
            } else if (type === 'true_false') {
                optionsContainer.innerHTML = `
                    <div class="true-false-options">
                        <div class="tf-option">
                            <input type="radio" name="questions[${questionId}][correct]" value="TRUE" required id="true_${questionId}">
                            <label for="true_${questionId}" class="tf-label tf-true">✅ True (Correct Answer)</label>
                        </div>
                        <div class="tf-option">
                            <input type="radio" name="questions[${questionId}][correct]" value="FALSE" required id="false_${questionId}">
                            <label for="false_${questionId}" class="tf-label tf-false">❌ False (Correct Answer)</label>
                        </div>
                    </div>
                    <small class="form-help" style="margin-top: 15px; display: block;">
                        Select which answer (True or False) is correct for this question.
                    </small>
                `;
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        }
        
        function renumberQuestions() {
            const questions = document.querySelectorAll('.quiz-question-card');
            let currentCount = 0;
            questions.forEach((question) => {
                currentCount++;
                const numberSpan = question.querySelector('.question-number-badge');
                if (numberSpan) {
                    numberSpan.textContent = `Question ${currentCount}`;
                }
            });
            questionCount = currentCount;
        }
        
        // Add first question on page load
        document.addEventListener('DOMContentLoaded', function() {
            addQuestion();
        });
        
        // Enhanced form validation
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            if (questionCount === 0) {
                e.preventDefault();
                alert('Please add at least one question to the quiz.');
                return false;
            }
            
            // Validate that all questions have correct answers selected
            const questions = document.querySelectorAll('.quiz-question-card');
            for (let i = 0; i < questions.length; i++) {
                const questionDiv = questions[i];
                const correctAnswer = questionDiv.querySelector('input[name*="[correct]"]:checked');
                if (!correctAnswer) {
                    e.preventDefault();
                    alert(`Please select the correct answer for Question ${i + 1}.`);
                    return false;
                }
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.create-quiz-btn');
            submitBtn.innerHTML = '<span>⏳</span> Creating Quiz...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>