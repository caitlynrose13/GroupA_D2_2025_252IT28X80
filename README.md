# Cybersecurity Awareness Programme Management System

**Group A - D2 Deliverable - 252IT28X80**

## Project Overview

This project is a simple web-based system designed to help South African SMMEs implement, manage, and track a 12-month cybersecurity awareness programme. The system will use PHP and SQLite as the backend technologies.

## Implementation Plan

### 1. Project Structure

The project will follow a simple structure:

```
GroupA_D2_2025_252IT28X80/
├── src/                # PHP source code
├── database/           # SQLite database files
├── docs/               # Documentation
└── README.md           # Project overview and implementation plan
```

### 2. Technology Stack

- **Backend:** PHP (no other server-side technologies allowed)
- **Database:** SQLite
- **Frontend:** Basic HTML/CSS (no frameworks for simplicity)

### 3. Features

1. **User Roles:**
   - Admin: Manage all accounts and content
   - Organization Admin: Manage employees and view analytics
   - Employee: Access content and complete assessments
2. **Content Delivery:**
   - Host D1 content (e.g., posters, videos, newsletters)
   - Link to external resources (e.g., YouTube videos)
3. **Assessment System:**
   - Quizzes at the end of each cycle
   - Track progress and results
4. **Multi-Tenant Support:**
   - Ensure data isolation between SMMEs

### 4. Development Phases

1. **✅ Phase 1: Project Setup** _(COMPLETED)_
   - ✅ Create folder structure
   - ✅ Set up SQLite database
   - ✅ Write basic PHP configuration files
2. **✅ Phase 2: User Authentication** WILL NEED TO POLISH
   - ✅ Implement login/logout functionality
   - ✅ Create roles for Admin, Organization Admin, and Employee
   - ✅ Session management and role-based access control
3. **✅ Phase 3: Content Management** WILL NEED TO POLISH
   - ✅ File upload system with validation and security
   - ✅ Content library with filtering by type, cycle, and month
   - ✅ Role-based permissions (upload/delete for admins only)
   - ✅ Content viewing and download functionality
4. **🔄 Phase 4: Assessment System** done?
   - 🔄 Develop quiz creation functionality
   - 🔄 Implement quiz-taking interface
   - 🔄 Store and display quiz results not done
5. **⏳ Phase 5: Dashboard Enhancement & Final Polish**
   - ⏳ Enhanced role-specific dashboards
   - ⏳ Navigation improvements
   - ⏳ Final testing and documentation

### 5. Deliverables

1. **Codebase:**
   - All PHP and SQLite files in a ZIP archive
2. **Documentation:**
   - README file with setup instructions
   - Work allocation document
3. **Demonstration:**
   - 20-minute presentation of the system

## Current Status

### ✅ Completed Features:

- **User Authentication System** - Login/logout with role-based access (admin, org_admin, employee)
- **Content Management System** - Upload, view, download, and delete content with filtering
- **Database Setup** - SQLite database with proper schema and test user accounts
- **Security Features** - File validation, role permissions, session management

### 🔄 Next Steps:

- **Quiz System Development** - Create quiz creation and taking functionality
- **Enhanced Dashboard** - Role-specific content and navigation
- **Final Testing & Polish** - Complete system integration and styling

### 🧪 Testing:

- Test user accounts available: `admin/password123`, `manager/password123`, `employee/password123`
- Start development server: `php -S localhost:8000 -t src/`
- Access system at: `http://localhost:8000/login.php`

---

## 12-Month Awareness Program Implementation Roadmap

This section provides a detailed 12-month schedule of activities designed to guide South African SMMEs through the implementation of their awareness program. This roadmap is structured into four phases, each building upon the previous one to ensure a comprehensive and sustainable approach to enhancing visibility and fostering growth.

### Cycle 1: Foundational Threats and Digital Hygiene (Months 1-4)

This cycle uses a mix of static and interactive resources to build a strong security foundation.

- **Month 1: Phishing and Social Engineering**
  - Resource: A poster for common areas. This visually summarizes key red flags of phishing and social engineering attacks, serving as a constant reminder.
- **Month 2: Password Management and Authentication**
  - Resource: A video. A short, engaging video that visually demonstrates how to create a strong password and the step-by-step process of setting up multi-factor authentication (MFA).
- **Month 3: Malware and Ransomware**
  - Resource: A newsletter. This can provide a detailed breakdown of different types of malware and real-world examples of ransomware attacks, offering more depth than a poster or video.
- **Month 4: Quiz**
  - Resource: A Quiz containing formative questions of the cycle’s topics.

### Cycle 2: POPIA and Data Protection (Months 5-8)

This cycle uses practical, document-based resources to help employees understand and comply with legal requirements.

- **Month 5: POPIA Basics**
  - Resource: A pamphlet or simple text-based guide. This explains what personal information is and how POPIA affects their daily tasks in a clear, easy-to-read format.
- **Month 6: Data Handling and Compliance**
  - Resource: An infographic or flowchart. That shows the POPIA 8 laws for data handling and processing.
- **Month 7: Physical Security and Data Privacy**
  - Resource: A checklist. A simple, printed checklist to be placed on desks or near exits, reminding employees of key actions like "lock your screen" and "shred confidential documents" at the end of the day.
- **Month 8: Quiz**
  - Resource: A Quiz containing formative questions of the cycle’s topics.

### Cycle 3: Proactive Security and Advanced Threats (Months 9-12)

This final cycle focuses on proactive security habits and tackling sophisticated threats, using resources that demand active engagement.

- **Month 9: Business Email Compromise (BEC) and Financial Fraud**
  - Resource: A simulated email. A realistic, but harmless, simulated BEC email will be sent to employees to test their ability to spot and report this advanced type of scam.
- **Month 10: Secure Device and Network Usage**
  - Resource: A video demonstration. This video can show employees how to safely use public Wi-Fi, update software, and securely manage their work devices.
- **Month 11: Insider Threats and Threat Reporting**
  - Resource: A generic email. This email will outline the company's clear and non-punitive policy on reporting security incidents, encouraging employees to be vigilant and act without fear.
- **Month 12: Quiz**
  - Resource: A Quiz containing formative questions of the cycle’s topics.

---

**Project Timeline:** September - November 2025  
**Submission Deadline:** [To be confirmed]  
**Demonstration:** 20-minute Teams presentation
