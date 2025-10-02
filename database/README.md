# Database Structure

SQLite database for the Cybersecurity Awareness Programme Management System with multi-tenant support.

## Recent Changes

### Database Simplification

- Removed `program_cycles` and `program_months` tables (12-month program is fixed)
- Updated tables to use `month_number` (1-12) instead of foreign keys
- Moved program structure data to PHP constants for better performance
- Maintained all enterprise features while reducing complexity

## Files

- `app.db` - SQLite database file
- `schema.sql` - Database schema with 20 tables
- `seed_data.sql` - Test data with 3 organizations and 11 users

## Features

### Multi-Tenancy

- Data isolation between organizations
- Organization-specific content and quizzes
- Subscription plans and user hierarchy

### User Roles

- System Admin - Platform management
- Organization Admin - Company management
- Employee - Content access and assessments

### Content & Assessments

- Global and organization-specific content
- Monthly content assignments (1-12)
- Quizzes with progress tracking
- Analytics and compliance reporting

## Setup

```bash
sqlite3 database/app.db < database/schema.sql
sqlite3 database/app.db < database/seed_data.sql
```

## Test Accounts

All passwords: `password123`

**System:** `system_admin`

**TechCorp:** `techcorp_admin`, `john_dev`, `mary_qa`, `bob_support`
**SafeGuard:** `safeguard_admin`, `lisa_consultant`, `peter_admin`  
**DataSecure:** `datasecure_admin`, `alex_analyst`, `emma_compliance`, `david_it`

## Database Tables

**Core:** organizations, users, departments, subscription_plans
**Content:** content, content_types, content_access_logs
**Assessments:** quizzes, quiz_attempts, quiz_questions, quiz_question_answers
**Analytics:** employee_progress, organization_analytics, notifications
**Lookup:** roles, various status tables

## Design Notes

Practical approach balancing functionality with simplicity:

- 20 tables total
- Proper normalization for core relationships
- Some JSON fields for flexibility
- Multi-tenant architecture that works
- Complex enough to be realistic, simple enough to manage

## Outstanding Work

### Immediate Priority

1. **PHP Constants File** - Create `src/config/program_structure.php` with 12-month program data
2. **Update PHP Files** - Change `month_id` references to `month_number` in all application files
3. **Test Core Functions** - Verify login, content management, quizzes work with new database

### Secondary Tasks

4. **Security Implementation** - Role-based access control and multi-tenant data isolation
5. **UI Updates** - Update forms and interfaces to work with simplified structure
6. **Full Integration Testing** - End-to-end testing of all features

Current status: Database complete, PHP application layer needs updates.
