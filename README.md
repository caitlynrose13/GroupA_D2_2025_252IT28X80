# Cybersecurity Awareness Programme Management System

**Group A - D2 Deliverable - 252IT28X80**

## Overview

A comprehensive web-based platform for South African SMMEs to manage a 12-month cybersecurity awareness programme. Built with PHP and SQLite featuring an African-inspired design theme.

## Key Features

- **Multi-Role User System**

  - **System Admin:** Manage all users/content; approve organisation admin accounts
  - **Organisation Admin:** Manage employees, access content, view assessment analytics
  - **Employee:** Access content and complete cybersecurity assessments

- **Complete Assessment System**

  - Create, take, and manage quizzes with timer functionality
  - Track progress, scores, and completion rates
  - Comprehensive analytics and reporting

- **Content Management**

  - Upload, categorize, and distribute resources (posters, videos, newsletters)
  - Month-by-month content organization for 12-month programme
  - Role-based access controls

- **Multi-Tenant Architecture**
  - Complete data isolation between organizations
  - Organization-specific analytics and reporting

## Quick Start

1. **Start Server:**

   ```bash
   php -S localhost:8000 -t src/ src/router.php
   ```

2. **Access System:**
   Navigate to `http://localhost:8000/login.php`

3. **Demo Accounts:**
   All passwords: `password123`

   **System Administrator:**

   - Email: `admin@platform.com`

   **Organization Admins:**

   - TechCorp: `admin@techcorp.co.za`
   - SafeGuard: `admin@safeguard.co.za`
   - DataSecure: `admin@datasecure.co.za`

   **Employees:**

   - TechCorp: `employee@techcorp.co.za`
   - SafeGuard: `lisa@safeguard.co.za`
   - DataSecure: `alex@datasecure.co.za`

## File Structure

```
GroupA_D2_2025_252IT28X80/
├── src/                    # Complete PHP application
│   ├── config/             # Database and program configuration
│   │   ├── db.php         # Database connection
│   │   └── program_structure.php # 12-month program constants
│   ├── assets/             # African-themed CSS and design
│   │   └── webdesign-style.css
│   ├── uploads/            # User-uploaded content
│   │   ├── documents/
│   │   └── images/
│   ├── quiz_create.php     # Quiz creation interface
│   ├── take_quiz.php       # Quiz taking with timer
│   ├── quiz_results.php    # Results and analytics
│   ├── content_upload.php  # Content management
│   ├── user_management.php # User approval system
│   ├── router.php          # Custom router for query parameters
│   └── *.php              # Other application files
├── database/               # SQLite schema and data
│   ├── app.db             # SQLite database (20 tables)
│   ├── schema.sql         # Database schema
│   ├── seed_data.sql      # Test data
│   └── README.md          # Database documentation
├── docs/                   # Additional documentation
├── README.md              # This file
```

## 12-Month Programme Implementation

The system supports a structured 12-month cybersecurity awareness programme:

- **Cycle 1 (Months 1-4):** Foundational Threats and Digital Hygiene
- **Cycle 2 (Months 5-8):** POPIA and Data Protection
- **Cycle 3 (Months 9-12):** Proactive Security and Advanced Threats

Each cycle includes content delivery, interactive learning, and assessment components.

## Technology Stack

- **Backend:** PHP 8.2 with PDO
- **Database:** SQLite 3 (20 tables, multi-tenant architecture)
- **Frontend:** HTML5/CSS3 with African-inspired theme
- **Security:** Password hashing, role-based access control, multi-tenant isolation
- **Features:** 4 Organizations, 13+ users across 3 roles

## Key Features

✅ User authentication with password hashing  
✅ Role-based dashboard and access control  
✅ Quiz creation and management system  
✅ Content upload and categorization  
✅ Organization admin approval workflow  
✅ Employee progress tracking  
✅ Analytics and reporting  
✅ African-themed responsive UI  
✅ Multi-tenant data isolation  
✅ 12-month structured programme cycles

---

**Demonstration:** Ready for presentation
