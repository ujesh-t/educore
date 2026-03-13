# EduCore - School Management System

A comprehensive School Management System (SMS) built with Laravel (Backend API) and Vue.js (Frontend SPA).

## 🚀 Tech Stack

- **Backend**: Laravel 12 + SQLite + Laravel Sanctum (API Authentication)
- **Frontend**: Vue.js 3 + Pinia (State Management) + Vue Router + Tailwind CSS
- **Database**: SQLite (with WAL mode for better concurrency)

## 📋 Features

### User Roles & Permissions (RBAC)
- **Super Admin**: Full system access
- **Principal/Management**: Dashboard view, financial approvals, staff management
- **Teacher**: Manage subjects, grades, attendance, assignments
- **Staff**: Manage fees, admissions, transport, inventory
- **Student**: View results, fee status, announcements
- **Parent**: View child's progress, fee history, communicate with teachers

### Modules

1. **Authentication & Security**
   - Multi-role login
   - Password reset functionality
   - Session management with auto-logout
   - Role-based access control

2. **Student Information System (SIS)**
   - Admission management
   - Profile management with medical details
   - Class/roster management
   - Attendance tracking

3. **Academic Management**
   - Course & subject management
   - Assignments & exams
   - Gradebook with GPA calculation
   - Timetable generation

4. **Financial Management**
   - Fee structure configuration
   - Billing & invoicing
   - Payment gateway integration (ready for Stripe/PayPal)
   - Financial reporting

5. **Communication**
   - Announcements (role/class targeted)
   - Internal messaging system

6. **System Administration**
   - User management
   - Audit logs
   - System settings

## 🛠️ Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm

### Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations with seeders
php artisan migrate:fresh --seed

# Start development server
php artisan serve
```

The backend will run on `http://localhost:8000`

### Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

The frontend will run on `http://localhost:3000`

## 🔐 Default Login Credentials

After running the seeders, you can login with:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@edupro.com | password123 |
| Teacher | teacher@edupro.com | password123 |
| Staff | staff@edupro.com | password123 |
| Student | student@edupro.com | password123 |
| Parent | parent@edupro.com | password123 |

## 📁 Project Structure

```
EduPro/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/   # API Controllers
│   │   │   └── Middleware/    # Custom Middleware
│   │   ├── Models/            # Eloquent Models
│   │   └── Providers/         # Service Providers
│   ├── database/
│   │   ├── migrations/        # Database Migrations
│   │   └── seeders/           # Database Seeders
│   └── routes/
│       └── api.php            # API Routes
│
└── frontend/                   # Vue.js SPA
    ├── src/
    │   ├── assets/            # CSS, images
    │   ├── components/        # Reusable components
    │   ├── router/            # Vue Router config
    │   ├── services/          # API services
    │   ├── stores/            # Pinia stores
    │   └── views/             # Page components
    └── index.html
```

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/logout` - User logout (auth required)
- `GET /api/auth/me` - Get current user (auth required)
- `POST /api/auth/password/reset` - Request password reset
- `POST /api/auth/password/confirm` - Confirm password reset

### Dashboard
- `GET /api/dashboard/stats` - Get dashboard statistics (auth required)

### Admin Only
- `GET /api/admin/users` - List all users
- `GET /api/admin/settings` - Get system settings
- `POST /api/admin/settings` - Update settings
- `GET /api/admin/audit-logs` - View audit logs

### Academic (Teacher/Admin)
- `POST /api/academic/attendance` - Mark attendance
- `POST /api/academic/grades` - Submit grades

### Financial (Staff/Admin)
- `POST /api/financial/fees` - Create fee invoice
- `POST /api/financial/payments` - Process payment

### Student/Parent
- `GET /api/student/profile` - Get student profile
- `GET /api/student/fees` - Get fee history
- `GET /api/student/grades` - Get grades

### Announcements
- `GET /api/announcements` - List announcements (auth required)

### Messages
- `GET /api/messages` - Get inbox (auth required)
- `POST /api/messages` - Send message (auth required)
- `PUT /api/messages/{id}/read` - Mark as read (auth required)

## 🗄️ Database Schema

### Core Tables
- `users` - User accounts with role_id
- `roles` - User roles (admin, teacher, staff, student, parent)
- `students` - Student profiles
- `teachers` - Teacher profiles
- `classes` - Class/section information
- `subjects` - Subject catalog

### Academic Tables
- `enrollments` - Student-class-subject enrollment
- `attendances` - Daily attendance records
- `assignments` - Homework/assignments
- `grades` - Student grades
- `exams` - Exam schedule
- `timetables` - Class schedules

### Financial Tables
- `fee_structures` - Fee configuration
- `fees` - Fee invoices
- `transactions` - Payment transactions

### Communication Tables
- `announcements` - School announcements
- `messages` - Internal messaging

### System Tables
- `audit_logs` - Activity logging
- `settings` - System configuration

## 🔒 Security Features

- Password hashing with bcrypt
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS prevention (Vue auto-escaping)
- Role-based access control
- Audit logging for critical actions
- Session timeout (configurable)

## 📝 Development Workflow

1. **Backend Development**
   - Create migration: `php artisan make:migration migration_name`
   - Create model: `php artisan make:model ModelName`
   - Create controller: `php artisan make:controller ControllerName`
   - Run migrations: `php artisan migrate`

2. **Frontend Development**
   - Create component: Add to `src/components/`
   - Create view: Add to `src/views/`
   - Add route: Update `src/router/index.js`
   - Add store: Create in `src/stores/`

3. **Testing**
   - Backend tests: `php artisan test`
   - Frontend tests: `npm run test`

## 🚨 SQLite Limitations & Notes

This project uses SQLite with WAL (Write-Ahead Logging) mode for better concurrency. This is suitable for:
- Small to medium schools (< 10,000 students)
- Low to moderate concurrent users

For larger deployments, consider migrating to MySQL or PostgreSQL.

To migrate to MySQL:
1. Create MySQL database
2. Update `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=edupro
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```
3. Run: `php artisan migrate:fresh --seed`

## 📄 License

This project is open-source software.

## 👥 Support

For issues and questions, please contact the development team.
