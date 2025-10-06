<?php
// filepath: src/quiz_create.php
session_start();
require_once __DIR__ . '/config/db.php';

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
    <title>Create Quiz - South African SMME Cybersecurity Portal</title>
    <link rel="stylesheet" href="assets/webdesign-style.css">
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
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .question-card {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .question-number {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        .remove-question {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .options-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        .option-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .option-input input[type="radio"] {
            width: auto;
        }
        .create-btn, .add-question-btn {
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            margin-right: 10px;
        }
        .create-btn {
            background: #27ae60;
        }
        .create-btn:hover { background: #219a52; }
        .add-question-btn {
            background: #3498db;
        }
        .add-question-btn:hover { background: #2980b9; }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .required { color: #e74c3c; }
        .true-false-options {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="african-header">
        <div class="african-border"></div>
        <div class="african-header-content">
            <h1>🎯 Create Quiz</h1>
            <p>Design cybersecurity assessments</p>
        </div>
        <div class="african-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="quiz_list.php">View Quizzes</a>
            <a href="content_list.php">Content Library</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="african-container">
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars(urldecode($success)); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars(urldecode($error)); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="process_quiz.php" id="quizForm">
            <!-- Quiz Details -->
            <div class="form-card">
                <h2>Quiz Details</h2>
                
                <div class="form-group">
                    <label for="title">Quiz Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required maxlength="255" placeholder="e.g., Cycle 1 Assessment - Phishing & Password Security">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Brief description of the quiz content and objectives..."></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="cycle_number">Cycle Number <span class="required">*</span></label>
                        <select id="cycle_number" name="cycle_number" required onchange="updateMonthNumber()">
                            <option value="">Select Cycle...</option>
                            <option value="1">Cycle 1 (Foundational Threats)</option>
                            <option value="2">Cycle 2 (POPIA & Data Protection)</option>
                            <option value="3">Cycle 3 (Advanced Threats)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="month_number">Quiz Month <span class="required">*</span></label>
                        <select id="month_number" name="month_number" required>
                            <option value="">Select Cycle First...</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="status">Quiz Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <option value="published">Published (Immediately Available)</option>
                            <option value="draft">Draft (Not Visible to Employees)</option>
                            <option value="scheduled">Scheduled (Will Auto-Publish)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="release_date">Release Date (for Scheduled Status)</label>
                        <input type="date" id="release_date" name="release_date">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="requires_previous_completion" name="requires_previous_completion" value="1" checked>
                        Require completion of previous quiz before accessing this one
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Uncheck only for the first quiz in a series. This ensures progressive learning.
                    </small>
                </div>
            </div>
            
            <!-- Questions Section -->
            <div class="form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Quiz Questions</h2>
                    <button type="button" class="add-question-btn" onclick="addQuestion()">Add Question</button>
                </div>
                
                <div id="questionsContainer">
                    <!-- Initial question will be added by JavaScript -->
                </div>
            </div>
            
            <button type="submit" class="create-btn">Create Quiz</button>
        </form>
    </div>
    
    <script>
        let questionCount = 0;
        
        function addQuestion() {
            questionCount++;
            const container = document.getElementById('questionsContainer');
            
            const questionHtml = `
                <div class="question-card" id="question-${questionCount}">
                    <div class="question-header">
                        <span class="question-number">Question ${questionCount}</span>
                        <button type="button" class="remove-question" onclick="removeQuestion(${questionCount})">Remove</button>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_text_${questionCount}">Question Text <span class="required">*</span></label>
                        <textarea name="questions[${questionCount}][text]" id="question_text_${questionCount}" rows="2" required placeholder="Enter your question here..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_type_${questionCount}">Question Type <span class="required">*</span></label>
                        <select name="questions[${questionCount}][type]" id="question_type_${questionCount}" required onchange="toggleOptions(${questionCount})">
                            <option value="">Select Type...</option>
                            <option value="multiple_choice">Multiple Choice</option>
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
                renumberQuestions();
            }
        }
        
        function toggleOptions(questionId) {
            const typeSelect = document.getElementById(`question_type_${questionId}`);
            const optionsContainer = document.getElementById(`options_${questionId}`);
            const type = typeSelect.value;
            
            if (type === 'multiple_choice') {
                optionsContainer.innerHTML = `
                    <div class="options-group">
                        <div class="option-input">
                            <input type="radio" name="questions[${questionId}][correct]" value="A" required>
                            <input type="text" name="questions[${questionId}][option_a]" placeholder="Option A" required>
                        </div>
                        <div class="option-input">
                            <input type="radio" name="questions[${questionId}][correct]" value="B" required>
                            <input type="text" name="questions[${questionId}][option_b]" placeholder="Option B" required>
                        </div>
                        <div class="option-input">
                            <input type="radio" name="questions[${questionId}][correct]" value="C" required>
                            <input type="text" name="questions[${questionId}][option_c]" placeholder="Option C" required>
                        </div>
                        <div class="option-input">
                            <input type="radio" name="questions[${questionId}][correct]" value="D" required>
                            <input type="text" name="questions[${questionId}][option_d]" placeholder="Option D" required>
                        </div>
                    </div>
                    <small style="color: #666; margin-top: 10px; display: block;">Select the correct answer by clicking the radio button next to it.</small>
                `;
                optionsContainer.style.display = 'block';
            } else if (type === 'true_false') {
                optionsContainer.innerHTML = `
                    <div class="true-false-options">
                        <div class="option-input">
                            <input type="radio" name="questions[${questionId}][correct]" value="TRUE" required>
                            <label>True</label>
                        </div>
                        <div class="option-input">
                            <input type="radio" name="questions[${questionId}][correct]" value="FALSE" required>
                            <label>False</label>
                        </div>
                    </div>
                    <small style="color: #666; margin-top: 10px; display: block;">Select the correct answer.</small>
                `;
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        }
        
        function renumberQuestions() {
            const questions = document.querySelectorAll('.question-card');
            questions.forEach((question, index) => {
                const newNumber = index + 1;
                const numberSpan = question.querySelector('.question-number');
                if (numberSpan) {
                    numberSpan.textContent = `Question ${newNumber}`;
                }
            });
        }
        
        // Professional quiz scheduling functions
        function updateMonthNumber() {
            const cycleNumber = document.getElementById('cycle_number').value;
            const monthSelect = document.getElementById('month_number');
            
            // Clear existing options
            monthSelect.innerHTML = '';
            
            if (cycleNumber === '1') {
                monthSelect.innerHTML = '<option value="4">Month 4 (End of Cycle 1)</option>';
                monthSelect.value = '4';
            } else if (cycleNumber === '2') {
                monthSelect.innerHTML = '<option value="8">Month 8 (End of Cycle 2)</option>';
                monthSelect.value = '8';
            } else if (cycleNumber === '3') {
                monthSelect.innerHTML = '<option value="12">Month 12 (End of Cycle 3)</option>';
                monthSelect.value = '12';
            } else {
                monthSelect.innerHTML = '<option value="">Select Cycle First...</option>';
            }
            
            // Update prerequisite checkbox based on cycle
            const prerequisiteCheckbox = document.getElementById('requires_previous_completion');
            if (cycleNumber === '1') {
                prerequisiteCheckbox.checked = false;
                prerequisiteCheckbox.disabled = true;
            } else {
                prerequisiteCheckbox.checked = true;
                prerequisiteCheckbox.disabled = false;
            }
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
            
            // Validate release date for scheduled quizzes
            const status = document.getElementById('status').value;
            const releaseDate = document.getElementById('release_date').value;
            
            if (status === 'scheduled' && !releaseDate) {
                e.preventDefault();
                alert('Please set a release date for scheduled quizzes.');
                return false;
            }
            
            if (status === 'scheduled' && new Date(releaseDate) < new Date()) {
                e.preventDefault();
                alert('Release date cannot be in the past.');
                return false;
            }
        });
    </script>
</body>
</html>