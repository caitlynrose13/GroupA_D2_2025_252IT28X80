-- Enterprise Multi-Tenant Cybersecurity Awareness Platform Database Schema
-- Compre-- Program structure is now handled by PHP constants (see config/program_structure.php)rprise-grade schema with multi-tenancy support
-- Created: October 2, 2025

-- =====================================================
-- MULTI-TENANT CORE TABLES
-- =====================================================

-- Organizations/Companies table (Multi-tenant support)
CREATE TABLE organizations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    registration_number VARCHAR(50),
    industry VARCHAR(50),
    size_category VARCHAR(20) CHECK (size_category IN ('micro', 'small', 'medium')),
    contact_email VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20),
    address TEXT,
    subscription_plan VARCHAR(20) DEFAULT 'basic',
    subscription_status VARCHAR(20) DEFAULT 'active' CHECK (subscription_status IN ('active', 'suspended', 'cancelled')),
    settings TEXT, -- JSON for organization-specific settings
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Organization subscription plans
CREATE TABLE subscription_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    max_employees INTEGER,
    features TEXT, -- JSON array of features
    price_monthly DECIMAL(10,2),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- LOOKUP TABLES (Reference Data)
-- =====================================================

-- User roles lookup table (Enhanced for enterprise)
CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    level INTEGER NOT NULL, -- 1=system_admin, 2=org_admin, 3=employee
    permissions TEXT, -- JSON array of permissions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Content types lookup table
CREATE TABLE content_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    file_extensions TEXT, -- JSON array of allowed extensions
    max_file_size INTEGER DEFAULT 10485760, -- 10MB default
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quiz status lookup table
CREATE TABLE quiz_statuses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employee status lookup table
CREATE TABLE employee_statuses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Assessment status lookup table
CREATE TABLE assessment_statuses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Program structure removed - using PHP constants for fixed 12-month program



-- =====================================================
-- USER MANAGEMENT TABLES
-- =====================================================

-- Users table (Multi-tenant with organization hierarchy)
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER, -- NULL for system admins
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role_id INTEGER NOT NULL,
    employee_id VARCHAR(50), -- Organization-specific employee ID
    department VARCHAR(100),
    job_title VARCHAR(100),
    manager_id INTEGER, -- Self-referencing for org hierarchy
    phone VARCHAR(20),
    hire_date DATE,
    status_id INTEGER DEFAULT 1,
    last_login TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (status_id) REFERENCES employee_statuses(id)
);

-- Departments table for better organization
CREATE TABLE departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INTEGER,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (manager_id) REFERENCES users(id),
    UNIQUE(organization_id, name)
);

-- =====================================================
-- CONTENT MANAGEMENT TABLES
-- =====================================================

-- Languages lookup table for multilingual support
CREATE TABLE languages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL, -- ISO language codes like 'en', 'af', 'zu'
    name VARCHAR(50) NOT NULL, -- Full language name like 'English', 'Afrikaans', 'Zulu'
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Content groups for linking multilingual content together
CREATE TABLE content_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER, -- NULL for global content
    base_title VARCHAR(255) NOT NULL, -- Base title for the content group
    description TEXT,
    content_type_id INTEGER NOT NULL,
    month_number INTEGER CHECK (month_number >= 1 AND month_number <= 12),
    target_audience TEXT, -- JSON array of roles/departments
    is_mandatory BOOLEAN DEFAULT 1,
    created_by INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (content_type_id) REFERENCES content_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Content table (Enhanced for multi-tenant and multilingual support)
CREATE TABLE content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content_group_id INTEGER NOT NULL, -- Links to content_groups
    language_id INTEGER NOT NULL, -- Links to languages
    title VARCHAR(255) NOT NULL, -- Language-specific title
    description TEXT, -- Language-specific description
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INTEGER,
    external_url VARCHAR(500), -- For YouTube, external links
    uploaded_by INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (content_group_id) REFERENCES content_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    UNIQUE(content_group_id, language_id) -- Only one content per language per group
);

-- Content access tracking (Updated for content groups)
CREATE TABLE content_access_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content_id INTEGER NOT NULL,
    content_group_id INTEGER NOT NULL,
    language_id INTEGER NOT NULL,
    access_type VARCHAR(20) NOT NULL, -- 'view', 'download', 'complete'
    duration_seconds INTEGER,
    ip_address VARCHAR(45),
    user_agent TEXT,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (content_id) REFERENCES content(id),
    FOREIGN KEY (content_group_id) REFERENCES content_groups(id),
    FOREIGN KEY (language_id) REFERENCES languages(id)
);

-- =====================================================
-- ASSESSMENT SYSTEM TABLES
-- =====================================================

-- Quizzes table (Enhanced for multi-tenant)
CREATE TABLE quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER, -- NULL for global quizzes
    title VARCHAR(255) NOT NULL,
    description TEXT,
    month_number INTEGER NOT NULL CHECK (month_number >= 1 AND month_number <= 12),
    passing_score INTEGER DEFAULT 70 CHECK (passing_score >= 0 AND passing_score <= 100),
    status_id INTEGER NOT NULL DEFAULT 1,
    release_date DATE,
    due_date DATE,
    requires_previous_completion BOOLEAN DEFAULT 0,
    max_attempts INTEGER DEFAULT 3,
    time_limit_minutes INTEGER,
    randomize_questions BOOLEAN DEFAULT 0,
    show_results_immediately BOOLEAN DEFAULT 1,
    created_by INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (status_id) REFERENCES quiz_statuses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Quiz questions table (Enhanced)
CREATE TABLE quiz_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    question_type TEXT NOT NULL CHECK (question_type IN ('multiple_choice', 'true_false', 'short_answer')),
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    correct_answer VARCHAR(10) NOT NULL,
    explanation TEXT, -- Explanation for correct answer
    question_order INTEGER NOT NULL,
    points INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz attempts table (Track multiple attempts)
CREATE TABLE quiz_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    quiz_id INTEGER NOT NULL,
    attempt_number INTEGER NOT NULL,
    status_id INTEGER NOT NULL, -- in_progress, completed, abandoned
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    time_taken_minutes INTEGER,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
    FOREIGN KEY (status_id) REFERENCES assessment_statuses(id),
    UNIQUE(user_id, quiz_id, attempt_number)
);

-- Quiz results table (Enhanced for analytics)
CREATE TABLE quiz_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    quiz_id INTEGER NOT NULL,
    score INTEGER NOT NULL,
    correct_answers INTEGER NOT NULL,
    total_questions INTEGER NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    passed BOOLEAN NOT NULL,
    feedback TEXT,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

-- Quiz question answers table (Enhanced tracking)
CREATE TABLE quiz_question_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    user_answer VARCHAR(255) NOT NULL,
    is_correct BOOLEAN NOT NULL,
    points_earned INTEGER DEFAULT 0,
    time_taken_seconds INTEGER,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id)
);

-- =====================================================
-- ANALYTICS AND PROGRESS TRACKING
-- =====================================================

-- Employee progress tracking
CREATE TABLE employee_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    month_number INTEGER NOT NULL CHECK (month_number >= 1 AND month_number <= 12),
    content_completed INTEGER DEFAULT 0,
    content_total INTEGER DEFAULT 0,
    quiz_completed BOOLEAN DEFAULT 0,
    quiz_passed BOOLEAN DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    last_activity TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, month_number)
);

-- Organization analytics aggregation table
CREATE TABLE organization_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL,
    month_number INTEGER NOT NULL CHECK (month_number >= 1 AND month_number <= 12),
    total_employees INTEGER DEFAULT 0,
    active_employees INTEGER DEFAULT 0,
    content_completion_rate DECIMAL(5,2) DEFAULT 0.00,
    quiz_completion_rate DECIMAL(5,2) DEFAULT 0.00,
    quiz_pass_rate DECIMAL(5,2) DEFAULT 0.00,
    average_quiz_score DECIMAL(5,2) DEFAULT 0.00,
    compliance_status VARCHAR(20) DEFAULT 'pending',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    UNIQUE(organization_id, month_number)
);

-- Notifications/Alerts system
CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER,
    user_id INTEGER,
    type VARCHAR(50) NOT NULL, -- 'reminder', 'deadline', 'completion', 'system'
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(500),
    is_read BOOLEAN DEFAULT 0,
    priority VARCHAR(20) DEFAULT 'normal', -- 'low', 'normal', 'high', 'urgent'
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Organization indexes
CREATE INDEX idx_organizations_status ON organizations(subscription_status);
CREATE INDEX idx_organizations_active ON organizations(is_active);

-- User indexes
CREATE INDEX idx_users_organization ON users(organization_id);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_manager ON users(manager_id);
CREATE INDEX idx_users_status ON users(status_id);

-- Content indexes (Updated for multilingual support)
CREATE INDEX idx_content_groups_organization ON content_groups(organization_id);
CREATE INDEX idx_content_groups_type ON content_groups(content_type_id);
CREATE INDEX idx_content_groups_month ON content_groups(month_number);
CREATE INDEX idx_content_groups_active ON content_groups(is_active);
CREATE INDEX idx_content_group ON content(content_group_id);
CREATE INDEX idx_content_language ON content(language_id);
CREATE INDEX idx_content_active ON content(is_active);

-- Language indexes
CREATE INDEX idx_languages_code ON languages(code);
CREATE INDEX idx_languages_active ON languages(is_active);

-- Quiz indexes
CREATE INDEX idx_quizzes_organization ON quizzes(organization_id);
CREATE INDEX idx_quizzes_month ON quizzes(month_number);
CREATE INDEX idx_quizzes_status ON quizzes(status_id);
CREATE INDEX idx_quiz_questions_quiz ON quiz_questions(quiz_id);
CREATE INDEX idx_quiz_questions_order ON quiz_questions(quiz_id, question_order);

-- Progress tracking indexes
CREATE INDEX idx_employee_progress_user ON employee_progress(user_id);
CREATE INDEX idx_employee_progress_month ON employee_progress(month_number);
-- Content access indexes (Updated for multilingual support)
CREATE INDEX idx_content_access_user ON content_access_logs(user_id);
CREATE INDEX idx_content_access_content ON content_access_logs(content_id);
CREATE INDEX idx_content_access_group ON content_access_logs(content_group_id);
CREATE INDEX idx_content_access_language ON content_access_logs(language_id);

-- Quiz results indexes
CREATE INDEX idx_quiz_attempts_user ON quiz_attempts(user_id);
CREATE INDEX idx_quiz_attempts_quiz ON quiz_attempts(quiz_id);
CREATE INDEX idx_quiz_results_user ON quiz_results(user_id);
CREATE INDEX idx_quiz_results_quiz ON quiz_results(quiz_id);

-- Analytics indexes
CREATE INDEX idx_org_analytics_org ON organization_analytics(organization_id);
CREATE INDEX idx_org_analytics_month ON organization_analytics(month_number);

-- =====================================================
-- INITIAL REFERENCE DATA
-- =====================================================

-- Insert subscription plans
INSERT INTO subscription_plans (name, description, max_employees, features, price_monthly) VALUES 
('basic', 'Basic plan for small businesses', 50, '["content_access", "basic_analytics"]', 99.00),
('professional', 'Professional plan with advanced features', 200, '["content_access", "advanced_analytics", "custom_content", "priority_support"]', 299.00),
('enterprise', 'Enterprise plan for large organizations', 1000, '["content_access", "advanced_analytics", "custom_content", "priority_support", "white_labeling", "api_access"]', 599.00);

-- Insert default roles
INSERT INTO roles (name, description, level, permissions) VALUES 
('system_admin', 'System administrator with full platform access', 1, '["platform_manage", "organizations_manage", "global_content_manage", "system_analytics"]'),
('org_admin', 'Organization administrator managing employees and content', 2, '["employees_manage", "org_content_manage", "org_analytics", "quiz_create"]'),
('employee', 'Regular employee accessing program content and taking assessments', 3, '["content_access", "quiz_take", "progress_view"]');

-- Insert content types
INSERT INTO content_types (name, description, file_extensions, max_file_size) VALUES 
('document', 'PDF and Word documents', '["pdf","doc","docx"]', 1073741824),
('image', 'Images and infographics', '["jpg","jpeg","png","gif"]', 104857600),
('video', 'Video content', '["mp4","mov","avi"]', 1073741824),
('external_link', 'External URLs and links', '[]', 0);

-- Insert quiz statuses
INSERT INTO quiz_statuses (name, description) VALUES 
('draft', 'Quiz is being created or edited'),
('scheduled', 'Quiz is scheduled for future release'),
('published', 'Quiz is live and available to users'),
('archived', 'Quiz is no longer active');

-- Insert employee statuses
INSERT INTO employee_statuses (name, description) VALUES 
('active', 'Active employee with full access'),
('pending_approval', 'Organization admin account pending system admin approval'),
('inactive', 'Temporarily inactive employee'),
('terminated', 'Former employee with revoked access'),
('on_leave', 'Employee on leave with limited access');

-- Insert assessment statuses
INSERT INTO assessment_statuses (name, description) VALUES 
('not_started', 'Assessment not yet begun'),
('in_progress', 'Assessment currently being taken'),
('completed', 'Assessment finished successfully'),
('abandoned', 'Assessment started but not completed'),
('expired', 'Assessment time limit exceeded');

-- Insert default languages (South African context)
INSERT INTO languages (code, name) VALUES 
('en', 'English'),
('af', 'Afrikaans'),
('zu', 'isiZulu'),
('xh', 'isiXhosa'),
('st', 'Sesotho'),
('tn', 'Setswana'),
('ss', 'siSwati'),
('ve', 'Tshivenda'),
('ts', 'Xitsonga'),
('nr', 'isiNdebele'),
('nso', 'Sepedi');