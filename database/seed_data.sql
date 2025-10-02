-- Enterprise Multi-Tenant Test Data
-- Created: October 2, 2025

-- =====================================================
-- ORGANIZATIONS (Multiple SMMEs for multi-tenant testing)
-- =====================================================

-- Insert test organizations
INSERT INTO organizations (name, registration_number, industry, size_category, contact_email, contact_phone, address, subscription_plan) VALUES 
('TechCorp Solutions', '2023/123456/07', 'Information Technology', 'small', 'admin@techcorp.co.za', '+27 11 555 0001', '123 Business Park, Sandton, Johannesburg, 2196', 'professional'),
('SafeGuard Consulting', '2022/789012/07', 'Professional Services', 'micro', 'info@safeguard.co.za', '+27 21 555 0002', '456 Corporate Ave, Cape Town, 8001', 'basic'),
('DataSecure Ltd', '2021/345678/07', 'Financial Services', 'medium', 'contact@datasecure.co.za', '+27 31 555 0003', '789 Finance Street, Durban, 4001', 'enterprise');

-- =====================================================
-- SYSTEM AND ORGANIZATION USERS
-- =====================================================

-- System Administrator (not tied to any organization)
INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role_id, job_title, status_id) VALUES 
(NULL, 'system_admin', 'admin@platform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 1, 'Platform Administrator', 1);

-- TechCorp Solutions Users
INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role_id, employee_id, department, job_title, status_id) VALUES 
(1, 'techcorp_admin', 'admin@techcorp.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 2, 'TC001', 'IT Management', 'IT Director', 1),
(1, 'john_dev', 'john.smith@techcorp.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 3, 'TC002', 'Development', 'Senior Developer', 1),
(1, 'mary_qa', 'mary.jones@techcorp.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary', 'Jones', 3, 'TC003', 'Quality Assurance', 'QA Analyst', 1),
(1, 'bob_support', 'bob.wilson@techcorp.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob', 'Wilson', 3, 'TC004', 'Support', 'Technical Support', 1);

-- SafeGuard Consulting Users
INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role_id, employee_id, department, job_title, status_id) VALUES 
(2, 'safeguard_admin', 'admin@safeguard.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Brown', 2, 'SG001', 'Management', 'Managing Director', 1),
(2, 'lisa_consultant', 'lisa.davis@safeguard.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Davis', 3, 'SG002', 'Consulting', 'Security Consultant', 1),
(2, 'peter_admin', 'peter.taylor@safeguard.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Peter', 'Taylor', 3, 'SG003', 'Administration', 'Office Administrator', 1);

-- DataSecure Ltd Users
INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role_id, employee_id, department, job_title, status_id) VALUES 
(3, 'datasecure_admin', 'admin@datasecure.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jennifer', 'Clark', 2, 'DS001', 'Security', 'Chief Security Officer', 1),
(3, 'alex_analyst', 'alex.white@datasecure.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex', 'White', 3, 'DS002', 'Risk Analysis', 'Risk Analyst', 1),
(3, 'emma_compliance', 'emma.green@datasecure.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma', 'Green', 3, 'DS003', 'Compliance', 'Compliance Officer', 1),
(3, 'david_it', 'david.martinez@datasecure.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Martinez', 3, 'DS004', 'IT', 'IT Specialist', 1);

-- =====================================================
-- DEPARTMENTS
-- =====================================================

-- TechCorp Departments
INSERT INTO departments (organization_id, name, description, manager_id) VALUES 
(1, 'IT Management', 'Information Technology Management and Strategy', 2),
(1, 'Development', 'Software Development and Engineering', 3),
(1, 'Quality Assurance', 'Testing and Quality Control', 4),
(1, 'Support', 'Technical Support and Customer Service', 5);

-- SafeGuard Departments
INSERT INTO departments (organization_id, name, description, manager_id) VALUES 
(2, 'Management', 'Executive Management and Strategic Planning', 6),
(2, 'Consulting', 'Security Consulting Services', 7),
(2, 'Administration', 'Administrative and Support Functions', 8);

-- DataSecure Departments
INSERT INTO departments (organization_id, name, description, manager_id) VALUES 
(3, 'Security', 'Information Security and Risk Management', 9),
(3, 'Risk Analysis', 'Risk Assessment and Analysis', 10),
(3, 'Compliance', 'Regulatory Compliance and Auditing', 11),
(3, 'IT', 'Information Technology Infrastructure', 12);

-- =====================================================
-- GLOBAL CONTENT (Available to all organizations)
-- =====================================================

-- Global content (existing files mapped to new structure)
INSERT INTO content (organization_id, title, description, file_path, file_name, file_size, content_type_id, month_number, uploaded_by) VALUES 
(NULL, 'POPIA Basics Guide', 'Essential guide to POPIA compliance requirements for all South African businesses', 'uploads/Month5-POPIABASICS.pdf', 'Month5-POPIABASICS.pdf', 524288, 1, 5, 1),
(NULL, 'Physical Security Checklist', 'Daily physical security checklist for all employees', 'uploads/Month7-PhyiscalSecurity_Checklist.pdf', 'Month7-PhyiscalSecurity_Checklist.pdf', 256000, 1, 7, 1),
(NULL, 'Phishing Awareness Poster', 'Visual guide to identifying phishing attempts', 'uploads/documents/Month1-Phishing_Poster.pdf', 'Month1-Phishing_Poster.pdf', 1048576, 2, 1, 1),
(NULL, 'POPIA Compliance Poster', 'Visual summary of POPIA requirements', 'uploads/documents/Month6-POPIA_Poster.pdf', 'Month6-POPIA_Poster.pdf', 2097152, 2, 6, 1),
(NULL, 'Malware Newsletter (English)', 'Monthly newsletter about malware threats', 'uploads/images/Month3-MalwareNewsletter_English.pdf', 'Month3-MalwareNewsletter_English.pdf', 3145728, 1, 3, 1),
(NULL, 'Malware Newsletter (isiZulu)', 'Monthly newsletter about malware threats in isiZulu', 'uploads/images/Month3-MalwareNewsletter_isiZulu.pdf', 'Month3-MalwareNewsletter_isiZulu.pdf', 3145728, 1, 3, 1);

-- External learning resources
INSERT INTO content (organization_id, title, description, external_url, content_type_id, month_number, uploaded_by) VALUES 
(NULL, 'Password Security Best Practices', 'Comprehensive video guide on creating secure passwords', 'https://www.youtube.com/watch?v=cybersecurity_passwords', 4, 2, 1),
(NULL, 'Social Engineering Awareness', 'Interactive training module on social engineering tactics', 'https://training.cybersecurity.gov.za/social-engineering', 4, 1, 1),
(NULL, 'POPIA Compliance Training', 'Official POPIA training from the Information Regulator', 'https://www.inforegulator.org.za/popia-training', 4, 5, 1);

-- Organization-specific content
INSERT INTO content (organization_id, title, description, file_path, file_name, content_type_id, month_number, uploaded_by) VALUES 
(1, 'TechCorp Security Policy', 'Company-specific security policies and procedures', 'uploads/documents/Letterhead-FNB-Details.pdf', 'TechCorp-Security-Policy.pdf', 1, NULL, 2),
(2, 'SafeGuard Client Data Handling', 'Guidelines for handling client confidential information', 'uploads/documents/safeguard_data_policy.pdf', 'SafeGuard-Data-Policy.pdf', 1, 6, 6),
(3, 'DataSecure Incident Response Plan', 'Step-by-step incident response procedures', 'uploads/documents/datasecure_incident_plan.pdf', 'DataSecure-Incident-Plan.pdf', 1, 11, 9);

-- =====================================================
-- SAMPLE QUIZZES
-- =====================================================

-- Global quizzes (available to all organizations)
INSERT INTO quizzes (organization_id, title, description, month_number, passing_score, status_id, requires_previous_completion, created_by) VALUES 
(NULL, 'Foundational Security Knowledge Assessment', 'Comprehensive assessment covering phishing, passwords, and malware basics', 4, 70, 3, 0, 1),
(NULL, 'POPIA and Data Protection Assessment', 'Assessment covering POPIA compliance and data handling requirements', 8, 75, 3, 1, 1),
(NULL, 'Advanced Security Practices Assessment', 'Assessment covering advanced threats and security procedures', 12, 80, 3, 1, 1);

-- Organization-specific quizzes
INSERT INTO quizzes (organization_id, title, description, month_number, passing_score, status_id, requires_previous_completion, created_by) VALUES 
(1, 'TechCorp Security Policy Quiz', 'Assessment on company-specific security policies', 4, 80, 3, 0, 2),
(2, 'SafeGuard Client Confidentiality Quiz', 'Assessment on client data handling procedures', 8, 85, 3, 0, 6),
(3, 'DataSecure Incident Response Quiz', 'Assessment on incident response procedures', 12, 90, 3, 0, 9);

-- =====================================================
-- SAMPLE EMPLOYEE PROGRESS
-- =====================================================

-- Initialize progress tracking for some employees
INSERT INTO employee_progress (user_id, month_number, content_completed, content_total, completion_percentage) VALUES 
-- TechCorp employees
(3, 1, 2, 3, 66.67), -- John - Month 1 (Phishing)
(3, 2, 1, 2, 50.00), -- John - Month 2 (Passwords)
(4, 1, 3, 3, 100.00), -- Mary - Month 1 (Completed)
(4, 2, 2, 2, 100.00), -- Mary - Month 2 (Completed)
(4, 3, 1, 2, 50.00), -- Mary - Month 3 (In progress)

-- SafeGuard employees
(7, 1, 1, 3, 33.33), -- Lisa - Month 1
(8, 1, 2, 3, 66.67), -- Peter - Month 1

-- DataSecure employees
(10, 1, 3, 3, 100.00), -- Alex - Month 1 (Completed)
(10, 2, 2, 2, 100.00), -- Alex - Month 2 (Completed)
(11, 1, 3, 3, 100.00), -- Emma - Month 1 (Completed)
(11, 2, 2, 2, 100.00), -- Emma - Month 2 (Completed)
(11, 3, 2, 2, 100.00); -- Emma - Month 3 (Completed)

-- =====================================================
-- SAMPLE ANALYTICS DATA
-- =====================================================

-- Organization analytics for current periods
INSERT INTO organization_analytics (organization_id, month_number, total_employees, active_employees, content_completion_rate, quiz_completion_rate, quiz_pass_rate, average_quiz_score, compliance_status) VALUES 
-- TechCorp analytics
(1, 1, 4, 4, 75.00, 50.00, 80.00, 78.50, 'in_progress'),
(1, 2, 4, 4, 62.50, 25.00, 100.00, 85.00, 'in_progress'),
(1, 3, 4, 4, 25.00, 0.00, 0.00, 0.00, 'behind'),

-- SafeGuard analytics
(2, 1, 3, 3, 66.67, 33.33, 100.00, 72.00, 'in_progress'),
(2, 2, 3, 3, 0.00, 0.00, 0.00, 0.00, 'behind'),

-- DataSecure analytics
(3, 1, 4, 4, 87.50, 75.00, 100.00, 88.25, 'compliant'),
(3, 2, 4, 4, 75.00, 50.00, 100.00, 91.50, 'compliant'),
(3, 3, 4, 4, 37.50, 25.00, 100.00, 89.00, 'in_progress');

-- =====================================================
-- SAMPLE NOTIFICATIONS
-- =====================================================

-- System-wide notifications
INSERT INTO notifications (organization_id, user_id, type, title, message, priority) VALUES 
(NULL, NULL, 'system', 'Platform Maintenance Scheduled', 'System maintenance is scheduled for this weekend. Please save your work.', 'normal'),
(NULL, NULL, 'system', 'New Content Available', 'New cybersecurity training content has been added to the platform.', 'normal');

-- Organization-specific notifications
INSERT INTO notifications (organization_id, user_id, type, title, message, action_url, priority) VALUES 
(1, 2, 'reminder', 'Monthly Compliance Review Due', 'Please review and approve this month''s compliance status.', '/analytics/compliance', 'high'),
(1, 3, 'reminder', 'Complete Month 3 Training', 'You have pending training materials for Month 3 - Malware and Ransomware.', '/content/month/3', 'normal'),
(2, 6, 'deadline', 'Quarterly Assessment Due', 'Your quarterly security assessment is due in 3 days.', '/quiz/2', 'high'),
(3, 9, 'completion', 'Excellent Progress!', 'Your organization has achieved 100% compliance for Month 2.', '/analytics/progress', 'normal');