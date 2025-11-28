# Student Attendance Management System

A web-based attendance management system for Algiers University built with HTML, CSS, JavaScript/jQuery, PHP, and MySQL.

## Features

### Authentication
- User registration and login
- Role-based access (Student, Professor, Admin)
- Secure password hashing

### Professor Module
- View courses and sessions
- Create attendance sessions
- Mark student attendance (present, absent, late, excused)
- View attendance summary per group/course

### Student Module
- View enrolled courses
- Check attendance records
- Submit justifications for absences

### Administrator Module
- Dashboard with system statistics
- Manage student accounts (add/remove)
- View analytics and charts
- System overview

## Database Tables
- users (id, email, password, full_name, role)
- courses (id, code, name, professor_id)
- groups (id, course_id, group_name)
- enrollments (id, student_id, group_id)
- attendance_sessions (id, course_id, session_date, session_time, status)
- attendance_records (id, session_id, student_id, status)
- justifications (id, attendance_id, reason, file_path, status)

## Setup Instructions

1. Create a MySQL database named 'attendance_system'
2. Run the SQL script in `scripts/01_create_tables.sql`
3. Update database credentials in `config/db.php`
4. Start your PHP server and navigate to login.html

## File Structure
- config/ - Database and authentication configuration
- api/ - Backend PHP APIs
  - professor/ - Professor module APIs
  - student/ - Student module APIs
  - admin/ - Admin module APIs
- professor/ - Professor interface pages
- student/ - Student interface pages
- admin/ - Admin interface pages
- login.html, register.html - Authentication pages
