# Cybersecurity Awareness Programme Management System

## Overview
A comprehensive web-based platform for South African SMMEs to manage a 12-month cybersecurity awareness programme. Built with PHP 8.2 and SQLite, featuring an African-inspired design theme.

**Project Type:** Academic Deliverable (252IT28X80 - Group A D2)  
**Status:** Production Ready  
**Last Updated:** October 7, 2025

## Technology Stack
- **Backend:** PHP 8.2 with PDO
- **Database:** SQLite 3
- **Frontend:** HTML5, CSS3 (African-inspired theme)
- **Server:** PHP Built-in Development Server
- **Deployment:** Autoscale (stateless web application)

## Project Architecture

### Core Components
1. **Multi-Tenant System:** Complete data isolation between organizations
2. **Role-Based Access Control:** System Admin, Organization Admin, Employee
3. **12-Month Programme Structure:** 3 cycles of 4 months each (defined in PHP constants)
4. **Assessment System:** Quiz creation, taking, and analytics
5. **Content Management:** Upload, categorize, and distribute cybersecurity resources

### File Structure
```
├── src/                          # Application root
│   ├── config/                   # Configuration files
│   │   ├── db.php               # Database connection
│   │   └── program_structure.php # 12-month program constants
│   ├── assets/                   # Styles and design files
│   │   └── webdesign-style.css  # African-inspired theme
│   ├── uploads/                  # User-uploaded content
│   │   ├── documents/
│   │   └── images/
│   ├── *.php                    # Application pages and logic
│   └── index.php                # Entry point (redirects to login/dashboard)
├── database/                     # Database files
│   ├── app.db                   # SQLite database (20 tables)
│   ├── schema.sql               # Database schema
│   └── seed_data.sql            # Test data
└── README.md                    # Original project documentation
```

### Database Structure
- **20 Tables** with proper normalization
- **4 Organizations** (including platform system)
- **13 Users** across 3 roles
- **Multi-tenant architecture** with organization_id isolation
- **12-Month Program** defined in `src/config/program_structure.php`

### Key Features Implemented
✅ User authentication with password hashing  
✅ Role-based dashboard and access control  
✅ Quiz creation and management system  
✅ Content upload and categorization  
✅ Organization admin approval workflow  
✅ Employee progress tracking  
✅ Analytics and reporting  
✅ African-themed responsive UI

## Quick Start

### Development
The application runs automatically via the configured workflow:
- **Server:** PHP built-in server on port 5000 with router.php
- **Command:** `php -S 0.0.0.0:5000 -t src/ src/router.php`
- **Access:** Click the webview to open the application
- **Auto-restart:** Server restarts automatically on file changes
- **Router:** Custom router handles query parameters correctly

### Demo Accounts
All passwords: `password123`

**System Administrator:**
- Email: `admin@platform.com`
- Access: Full platform management

**Organization Admins:**
- TechCorp: `admin@techcorp.co.za`
- SafeGuard: `admin@safeguard.co.za`
- DataSecure: `admin@datasecure.co.za`

**Employees:**
- TechCorp: `employee@techcorp.co.za`
- SafeGuard: `lisa@safeguard.co.za`
- DataSecure: `alex@datasecure.co.za`

## 12-Month Programme Structure

### Cycle 1 (Months 1-4): Foundational Threats and Digital Hygiene
- Month 1: Phishing and Social Engineering
- Month 2: Password Management and Authentication
- Month 3: Malware and Ransomware
- Month 4: Cycle 1 Assessment (Quiz)

### Cycle 2 (Months 5-8): POPIA and Data Protection
- Month 5: POPIA Basics
- Month 6: Data Handling and Compliance
- Month 7: Physical Security and Data Privacy
- Month 8: Cycle 2 Assessment (Quiz)

### Cycle 3 (Months 9-12): Proactive Security and Advanced Threats
- Month 9: Business Email Compromise and Financial Fraud
- Month 10: Secure Device and Network Usage
- Month 11: Insider Threats and Threat Reporting
- Month 12: Cycle 3 Assessment (Quiz)

## Deployment

### Production Deployment
The project is configured for autoscale deployment:
- **Type:** Autoscale (stateless web application)
- **Command:** `php -S 0.0.0.0:5000 -t src/`
- **Port:** 5000
- **Scaling:** Automatic based on traffic

### Database Considerations
- SQLite database is included in the deployment
- Database is read/write capable
- Consider PostgreSQL for production with high concurrency
- Database backups available in `database/` directory

## User Preferences
No specific user preferences documented yet.

## Recent Changes
- **Oct 7, 2025 (Latest):** Fixed file upload failures and increased limit to 500MB
  - **Fixed upload bug:** PHP was using default 2MB limit causing all uploads to fail
  - Added detailed error logging showing specific PHP upload error messages
  - Updated server workflow and deployment with PHP ini overrides (-d flags)
  - Now supports 500MB uploads in both development and production
  - Upload limits: upload_max_filesize=500M, post_max_size=500M, max_execution_time=300s
  - Supports large training videos and documents for cybersecurity content

- **Oct 7, 2025:** Enhanced content viewing with inline display
  - **PDFs now display in browser** using dedicated viewer page with proper headers
  - Created `src/pdf_viewer.php` to serve PDFs with Chrome-compatible settings
  - Fixed Chrome blocking issue by using CSP `frame-ancestors 'self'` instead of X-Frame-Options
  - Chrome's PDF viewer blocks X-Frame-Options headers even on same origin - replaced with CSP for security
  - **Videos display with HTML5 player** supporting MP4, MOV, AVI, WebM formats
  - **Images display inline** with enhanced styling (shadows, rounded corners)
  - Fixed download handler to extract file extensions from paths correctly
  - Download button remains available as backup for all file types

- **Oct 7, 2025:** Content upload and viewing system fixes
  - Created `src/router.php` to handle query parameters correctly
  - Fixed file upload path (now saves to `src/uploads/`)
  - Fixed content viewing bug (404 errors on query params)
  - Updated workflow to use router: `php -S 0.0.0.0:5000 -t src/ src/router.php`
  - Created all 3 cycle assessment quizzes (36 questions total)
  - Implemented user-scoped prerequisite system for sequential quiz access

- **Oct 7, 2025:** Initial Replit setup and configuration
  - Configured PHP 8.2 environment
  - Set up workflow for automatic server restart
  - Configured deployment settings
  - Created project documentation
  - Added .gitignore for PHP projects

## Development Notes

### Database Access
The database connection is configured in `src/config/db.php` and uses PDO with SQLite.

### Program Structure
The 12-month programme structure is defined as PHP constants in `src/config/program_structure.php` for better performance than database queries. This includes:
- 3 programme cycles
- 12 monthly topics with learning objectives
- Helper functions for cycle/month lookups

### Security Features
- Password hashing using `password_hash()` and `password_verify()`
- Role-based access control on all pages
- Multi-tenant data isolation
- Session-based authentication
- Organization-specific data filtering

### Styling
The application features an African-inspired design with:
- Earthy color palette (greens, oranges, golds)
- "Sawubona" (Zulu greeting) welcome message
- Responsive design for mobile and desktop

## Known Issues
None currently identified. All core features are functional.

## Future Enhancements
- Email notifications for quiz deadlines
- Advanced analytics dashboard
- Content scheduling automation
- Mobile application
- API for third-party integrations

## Support and Documentation
- Original project README: `README.md`
- Database documentation: `database/README.md`
- Design notes: `src/assets/DESIGN_NOTES.md`
