# Cybersecurity Awareness Programme Management System

**Group A - D2 Deliverable - 252IT28X80**

## Overview

A comprehensive web-based platform for South African SMMEs to manage a 12-month cybersecurity awareness programme. Built with PHP and SQLite with an African-inspired design theme.

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

## Current Implementation Status

### ✅ **Fully Complete:**

- User authentication and role-based access control
- Quiz creation, taking, and results system
- Content upload and management system
- Organization admin approval workflow
- African-themed responsive UI/UX design
- Multi-tenant data isolation
- Comprehensive dashboard analytics

### 🔧 **Final Polish Needed:**

- Minor styling refinements
- Additional test data and documentation
- Final integration testing

## Quick Start

1. **Start Server:**

   ```bash
   php -S localhost:8000 -t src/
   ```

2. **Access System:**
   Navigate to `http://localhost:8000/login.php`

3. **Test Accounts:**
   - System Admin: `admin@platform.com` / `password123`
   - Org Admin: `admin@techcorp.co.za` / `password123`
   - Employee: `employee@techcorp.co.za` / `password123`

## File Structure

```
GroupA_D2_2025_252IT28X80/
├── src/                    # Complete PHP application
│   ├── quiz_create.php     # Quiz creation interface
│   ├── quiz_take.php       # Quiz taking with timer
│   ├── quiz_results.php    # Results and analytics
│   ├── content_upload.php  # Content management
│   ├── user_management.php # User approval system
│   └── assets/             # African-themed CSS
├── database/               # SQLite schema and data
└── README.md              # This file
```

## 12-Month Programme Implementation

The system supports a structured 12-month cybersecurity awareness programme:

- **Cycle 1 (Months 1-4):** Foundational Threats and Digital Hygiene
- **Cycle 2 (Months 5-8):** POPIA and Data Protection
- **Cycle 3 (Months 9-12):** Proactive Security and Advanced Threats

Each cycle includes content delivery, interactive learning, and assessment components.

## Deliverables

- ✅ Complete functional codebase
- ✅ SQLite database with test data
- ✅ African-inspired responsive UI
- ✅ Multi-tenant architecture
- 📋 Final documentation and demonstration

---

**Project Status:** Implementation Complete - Ready for Demonstration  
**Technology Stack:** PHP 8.0+, SQLite, HTML5/CSS3  
**Demonstration:** 20-minute presentation ready
