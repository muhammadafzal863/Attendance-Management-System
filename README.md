# Attendance Management System

A PHP & MySQL based Attendance Management System for schools, colleges, and institutes.  
It helps admins and teachers manage students, teachers, classes, attendance, reports, bulk CSV import, calendar view, and system settings.

---

## 📌 Features

- 🔐 Secure Login System (Admin / Teacher roles)
- 📊 Dashboard with attendance statistics
- 👨‍🎓 Student Management
- 👨‍🏫 Teacher Management
- 📚 Class Management
- ✅ Student Attendance Marking
- ✅ Teacher Attendance Marking
- 📥 Bulk Attendance Import via CSV
- 📅 Monthly Attendance Calendar View
- 📈 Attendance Reports
- 👤 Individual Student Report + CSV Export
- ⚙️ System Settings
- 👤 User Profile & Password Change
- 🖨️ Print-Friendly Attendance Pages
- 📱 Responsive Navigation

---

## 🛠️ Tech Stack

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- XAMPP / WAMP / LAMP

---

## ✅ Requirements

- PHP 7.4 or higher (PHP 8+ recommended)
- MySQL 5.7+ or MariaDB
- Apache Server
- Web Browser (Chrome, Firefox, Edge, etc.)

---

## 🚀 Installation

1. Clone or download this project.

```bash
git clone https://github.com/your-username/attendance-management-system.git


CREATE DATABASE attendance_db;




attendance-management-system/
│
├── bulk_attendance.php        # Bulk CSV attendance import
├── calendar.php               # Monthly attendance calendar
├── classes.php                # Class management
├── config.php                 # Database configuration
├── dashboard.php              # Main dashboard
├── login.php                  # Login page
├── logout.php                 # Logout
├── management.php             # Combined student/teacher attendance management
├── mark_attendance.php        # Mark attendance by class
├── navbar.php                 # Navigation bar
├── profile.php                # User profile & password change
├── reports.php                # Attendance reports
├── security.php               # Security helper functions
├── settings.php               # System settings
├── student_attendance.php     # Student attendance page
├── student_reports.php        # Individual student report
├── students.php               # Student management
├── style.css                  # Main stylesheet
├── teacher_attendance.php     # Teacher attendance page
├── teachers.php               # Teacher management
└── update_db.php              # Database update/helper page