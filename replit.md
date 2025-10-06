# Cybersecurity Awareness Programme Management System

## Project Overview

This is a web-based system designed to help South African SMMEs implement, manage, and track a 12-month cybersecurity awareness programme. The system provides content delivery, quiz assessments, and progress tracking for employees.

**Technology Stack:**
- Backend: PHP 8.2
- Database: SQLite
- Frontend: HTML/CSS (no frameworks)
- Server: PHP Built-in Development Server

## Project Structure

```
.
├── src/                    # PHP source code (document root)
│   ├── config/            # Configuration files
│   │   ├── db.php         # Database connection
│   │   └── program_structure.php  # 12-month program constants
│   ├── assets/            # CSS, JS, images
│   ├── uploads/           # User-uploaded content
│   ├── index.php          # Entry point (redirects to login/dashboard)
│   ├── login.php          # Login page
│   ├── authenticate.php   # Login handler
│   ├── dashboard.php      # Main dashboard
│   ├── content_*.php      # Content management pages
│   └── quiz_*.php         # Quiz system pages
├── database/              # SQLite database and schema
│   ├── app.db            # Main database file
│   ├── schema.sql        # Database schema
│   └── seed_data.sql     # Test data
└── README.md             # Project documentation
```

## Development Setup

The application is configured to run on Replit with the following setup:

- **Port:** 5000 (0.0.0.0 binding for Replit proxy)
- **Document Root:** src/
- **Workflow:** PHP built-in server running on port 5000

## Test Accounts

All test accounts use the password: `password123`

**System Administrator:**
- Username: `system_admin`
- Access: Full platform management

**Organization Admins:**
- Username: `techcorp_admin` (TechCorp Solutions)
- Username: `safeguard_admin` (SafeGuard Consulting)
- Username: `datasecure_admin` (DataSecure Inc)

**Employees:**
- Username: `john_dev` (TechCorp)
- Username: `mary_qa` (TechCorp)
- Username: `bob_support` (TechCorp)
- And more... (see database/README.md for full list)

## 12-Month Program Structure

The program is divided into 3 cycles of 4 months each:

### Cycle 1: Foundational Threats and Digital Hygiene (Months 1-4)
- Month 1: Phishing and Social Engineering
- Month 2: Password Management and Authentication
- Month 3: Malware and Ransomware
- Month 4: Cycle 1 Assessment Quiz

### Cycle 2: POPIA and Data Protection (Months 5-8)
- Month 5: POPIA Basics
- Month 6: Data Handling and Compliance
- Month 7: Physical Security and Data Privacy
- Month 8: Cycle 2 Assessment Quiz

### Cycle 3: Proactive Security and Advanced Threats (Months 9-12)
- Month 9: Business Email Compromise and Financial Fraud
- Month 10: Secure Device and Network Usage
- Month 11: Insider Threats and Threat Reporting
- Month 12: Cycle 3 Assessment Quiz

## Features

### User Roles
- **System Admin:** Platform-wide management and global content
- **Organization Admin:** Manage employees, upload content, create quizzes, view analytics
- **Employee:** Access content, take quizzes, track progress

### Content Management
- Upload documents (PDF, DOC, DOCX)
- Upload images and infographics
- Link external resources (YouTube videos, etc.)
- Filter content by type, cycle, and month
- Role-based permissions

### Assessment System
- Create quizzes with multiple question types
- Track quiz attempts and scores
- Progress tracking per employee
- Organization-wide analytics

### Multi-Tenant Support
- Data isolation between organizations
- Organization-specific content and quizzes
- Subscription plans (basic, professional, enterprise)

## Database

The SQLite database (`database/app.db`) contains:
- 20 tables for complete functionality
- Multi-tenant architecture with data isolation
- Sample data for 3 organizations and 12 users
- Proper indexes for performance

To reset the database:
```bash
sqlite3 database/app.db < database/schema.sql
sqlite3 database/app.db < database/seed_data.sql
```

## Current Development Status

### Completed Features
- User authentication system with role-based access
- Content management (upload, view, download, delete)
- Database setup with multi-tenant support
- Security features (file validation, permissions, session management)
- Quiz system (create, take, track results)
- Dashboard with program overview

### Recent Setup Changes (Replit Import)
- Installed SQLite for database access
- Configured PHP built-in server on port 5000 with 0.0.0.0 binding
- Created workflow for automatic server startup
- Added .gitignore for PHP project
- Verified database connectivity and test users

## Security Notes

- Passwords are hashed using PHP's `password_hash()`
- Session-based authentication
- Role-based access control throughout the application
- File upload validation (type, size, permissions)
- SQL injection prevention using prepared statements
- Multi-tenant data isolation in database queries

## Known Issues

- LSP shows false positives for `$pdo` variable (it's properly included via require_once)
- Minor 404 error for favicon (doesn't affect functionality)

## User Preferences

None configured yet.

## Recent Changes

**October 6, 2025:**
- Fixed true/false quiz question grading bug using case-insensitive comparison
- Enhanced quiz results page to show explanations for ALL questions (both correct and incorrect)
- Fixed prerequisite validation logic - now checks only previous cycle's assessment quiz instead of all previous months
- Updated prerequisite error message to be user-friendly: "You must complete and pass the [Cycle Title] assessment quiz (Month X) first"
- Added 36 real quiz questions across all 3 cycle assessments with proper explanations
- Cleaned up database by removing all dummy test data
- Removed organization-specific quizzes (TechCorp, SafeGuard, DataSecure) - only 3 cycle assessment quizzes remain
- Fixed progress page to show accurate quiz counts (1/3 instead of 1/12)
- Removed "Months Started" stat from progress page
- Redesigned progress page to show content access per month and quiz status for cycle assessments
- Added clear indicators for work remaining (content items to access, quizzes to complete)

**October 4, 2025:**
- Imported project from GitHub
- Set up Replit environment with PHP and SQLite
- Configured server to run on port 5000
- Created workflow for automatic server startup
- Added .gitignore file
- Verified database and test accounts working
- Created this documentation file
