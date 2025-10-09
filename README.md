# Cybersecurity Awareness Platform

**Group A - D2 Deliverable (252IT28X80)**

## What We Built

A web-based cybersecurity training platform for South African SMMEs with:

- **User Management**: 3-tier role system (System Admin → Org Admin → Employee)
- **Content System**: Upload/view PDFs, videos, images with progress tracking
- **Quiz Platform**: Create quizzes, take assessments, view results with timers
- **Organization Management**: Multi-tenant support with data isolation
- **Analytics**: Real progress tracking based on actual user interactions

## Quick Start

```bash
php -d upload_max_filesize=100M -d post_max_size=100M -S localhost:8000 -t src
```

Visit: `http://localhost:3000/login.php`

**Demo Accounts** (password: `password123`):

- System Admin: `admin@platform.com`
- Org Admin: `admin@techcorp.co.za`
- Employee: `mary.jones@techcorp.co.za`

## Tech Stack

- **Backend**: PHP 8.2 + SQLite (20 tables)
- **Frontend**: HTML/CSS
- **Security**: Password hashing, prepared statements, role-based access
- **Features**: Multi-tenant, file uploads, real-time progress tracking

## Key Achievements

✅ Complete user authentication & role management  
✅ Content upload/download with security validation  
✅ Interactive quiz system with timer & results  
✅ Organization management with data isolation  
✅ Real progress tracking (no fake data)  
✅ Professional UI with responsive design  
✅ Enhanced user experience with completion indicators  
✅ Secure file handling & database operations

## 12-Month Programme Structure

- **Cycle 1 (Months 1-4)**: Phishing, passwords, malware, email security
- **Cycle 2 (Months 5-8)**: POPIA compliance, data protection, physical security
- **Cycle 3 (Months 9-12)**: Advanced threats, cloud security, security culture

---

**Project Status: Ready for deployment and presentation! 🎉**
