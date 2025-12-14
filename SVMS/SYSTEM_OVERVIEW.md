# Student Violation Monitoring System (SVMS) - Complete Overview

## 📋 Table of Contents
1. [System Introduction](#system-introduction)
2. [Key Features](#key-features)
3. [User Roles & Access](#user-roles--access)
4. [System Modules](#system-modules)
5. [Technical Architecture](#technical-architecture)
6. [Setup & Installation](#setup--installation)
7. [Usage Guide](#usage-guide)
8. [Email & Notification System](#email--notification-system)
9. [Database Structure](#database-structure)
10. [Security Features](#security-features)

---

## 🎯 System Introduction

The **Student Violation Monitoring System (SVMS)** is a comprehensive web-based application designed for educational institutions to efficiently track, manage, and monitor student violations. The system provides a centralized platform for recording violations, generating reports, and maintaining communication with students and guardians.

### Purpose
- Track student violations systematically
- Maintain detailed records with notes and dispositions
- Generate comprehensive reports for administrative purposes
- Send automated email notifications to guardians
- Provide real-time notifications to administrators
- Enable students to view their own violation records

---

## ✨ Key Features

### 1. **User Management & Authentication**
- Secure login system with role-based access control
- Password hashing using bcrypt
- Session management
- Multiple user roles (Admin, Staff, Student)

### 2. **Student Management**
- Complete student database with:
  - Student number (unique identifier)
  - Personal information (name, class, section)
  - Guardian contact information
  - Optional student login accounts
- Search and filter functionality
- Bulk operations support

### 3. **Violation Management**
- Predefined violation types with:
  - Categories (e.g., Attendance, Conduct)
  - Severity levels (Low, Medium, High)
  - Descriptions
- Easy violation creation and editing
- Categorization for better organization

### 4. **Violation Recording**
- Record violations with:
  - Student selection
  - Violation type
  - Date of occurrence
  - Detailed notes
  - Disposition status
- Automatic tracking of who recorded the violation
- Email notification option

### 5. **Dashboard & Analytics**
- Real-time statistics:
  - Total students
  - Total violations
  - Total records
  - Pending actions
- Monthly trend analysis
- Top violations by frequency
- Students with most violations
- Severity distribution charts
- Recent violation records

### 6. **Reports & Export**
- Comprehensive filtering:
  - Date range
  - Student search
  - Violation type
  - Category
  - Severity level
  - Class
- Multiple export formats:
  - CSV export
  - PDF download
  - Print-friendly view
- Summary statistics

### 7. **Email Notification System**
- Automated email notifications to:
  - Student guardians (if email provided)
  - Students (if account linked)
- Professional HTML email templates
- SMTP support (Gmail, Outlook, etc.)
- Configurable email settings
- Email sending status feedback

### 8. **In-App Notification System**
- Real-time notification bell in navigation
- Unread notification count badge
- Notification dropdown with recent items
- Full notification management page
- Notification types: Info, Warning, Danger, Success
- Mark as read/unread functionality
- Auto-refresh every 30 seconds

### 9. **Modern UI/UX**
- Responsive design (mobile-friendly)
- Modern gradient design
- Smooth animations and transitions
- Bootstrap 5 framework
- Bootstrap Icons
- Professional color scheme
- Intuitive navigation

---

## 👥 User Roles & Access

### **Administrator (Admin)**
**Full System Access**
- ✅ Dashboard with all statistics
- ✅ Student management (CRUD)
- ✅ Violation type management
- ✅ Record violations
- ✅ View all reports
- ✅ Statistics and analytics
- ✅ Email settings configuration
- ✅ View all notifications
- ✅ Access to all features

### **Staff**
**Limited Access** (Currently same as Admin - can be customized)
- ✅ View dashboard
- ✅ Record violations
- ✅ View reports
- ✅ View notifications

### **Student**
**Self-Service Portal**
- ✅ View own violation records
- ✅ View violation details
- ✅ View disposition status
- ❌ Cannot record violations
- ❌ Cannot view other students

---

## 📦 System Modules

### 1. **Authentication Module** (`index.php`, `auth.php`, `logout.php`)
- User login
- Session management
- Role-based access control
- Password verification

### 2. **Dashboard Module** (`dashboard.php`)
- Overview statistics
- KPI cards
- Recent activity
- Quick access to main features

### 3. **Student Management** (`students.php`)
- List all students
- Search functionality
- Add new students
- Edit student information
- Delete students
- Link student accounts

### 4. **Violation Management** (`violations.php`)
- List all violation types
- Add new violation types
- Edit violation details
- Delete violations
- Categorize violations

### 5. **Violation Records** (`records.php`)
- Create new violation records
- Select student and violation
- Add notes and disposition
- Email notification option
- View recent records

### 6. **Reports Module** (`reports.php`)
- Advanced filtering
- Date range selection
- Multiple filter options
- Export to CSV
- PDF download
- Print view

### 7. **Statistics Module** (`stats.php`)
- Detailed analytics
- Charts and graphs
- Trend analysis

### 8. **Student Portal** (`student.php`)
- Personal violation history
- Student information display
- Read-only access

### 9. **Notifications Module** (`notifications.php`, `api_notifications.php`)
- View all notifications
- Mark as read/unread
- Delete notifications
- Filter by type
- Real-time updates

### 10. **Email Settings** (`email_settings.php`)
- Configure SMTP settings
- Enable/disable email
- Test email configuration
- Sender information

---

## 🏗️ Technical Architecture

### **Technology Stack**
- **Backend**: PHP 7.4+ / 8.x
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3.2
- **Icons**: Bootstrap Icons 1.11.1
- **PDF Generation**: html2pdf.js
- **Server**: Apache (XAMPP)

### **File Structure**
```
Student-Violation-Monitoring-System-SVMS--main/
│
├── Core Files
│   ├── config.php              # Database configuration
│   ├── auth.php                 # Authentication helpers
│   ├── index.php                # Login page
│   └── logout.php               # Logout handler
│
├── Main Modules
│   ├── dashboard.php            # Admin dashboard
│   ├── students.php             # Student management
│   ├── violations.php           # Violation management
│   ├── records.php              # Violation recording
│   ├── reports.php              # Reports & exports
│   ├── stats.php                # Statistics
│   └── student.php              # Student portal
│
├── Notification System
│   ├── notifications.php        # Notification management page
│   ├── api_notifications.php    # Notification API
│   └── notification_service.php # Notification service class
│
├── Email System
│   ├── email_service.php        # Email service class
│   └── email_settings.php       # Email configuration
│
├── UI Components
│   ├── header.php               # Page header
│   ├── footer.php               # Page footer
│   ├── nav.php                  # Navigation bar
│   ├── styles.css               # Custom styles
│   └── main.js                  # JavaScript functions
│
└── Database
    └── svms.sql                 # Database schema
```

### **Design Patterns**
- **MVC-like Structure**: Separation of concerns
- **Service Classes**: EmailService, NotificationService
- **Helper Functions**: Authentication, database, HTML escaping
- **Session Management**: Secure session handling

---

## 🚀 Setup & Installation

### **Prerequisites**
- XAMPP (Apache, PHP 7.4+, MySQL)
- Web browser (Chrome, Firefox, Edge)
- Text editor (optional)

### **Installation Steps**

1. **Extract/Copy Files**
   ```
   Copy the project folder to: C:\xampp\htdocs\
   ```

2. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. **Create Database**
   ```sql
   -- Option 1: Via phpMyAdmin
   - Open http://localhost/phpmyadmin
   - Import svms.sql file
   
   -- Option 2: Via Command Line
   mysql -u root < svms.sql
   ```

4. **Configure Database** (if needed)
   - Edit `config.php` if using different credentials:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'newmatt');
   ```

5. **Access the System**
   ```
   http://localhost/Student-Violation-Monitoring-System-SVMS--main/
   ```

### **Default Login Credentials**

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@example.com` | `admin123` |
| Admin | `mattjhevicadmin@example.com` | `admin123` |
| Staff | `staff@example.com` | `staff123` |
| Staff | `mattjhevicstaff@example.com` | `staff123` |

⚠️ **Important**: Change default passwords in production!

---

## 📖 Usage Guide

### **For Administrators**

#### **1. Managing Students**
1. Navigate to **Students** from the menu
2. Click **Add Student** or use the form on the right
3. Fill in student details:
   - Student Number (required, unique)
   - First Name, Last Name (required)
   - Class, Section (optional)
   - Guardian Contact (email for notifications)
   - Optional: Student login email/password
4. Click **Add** to save

#### **2. Managing Violations**
1. Go to **Violations**
2. Add violation types:
   - Title (e.g., "Tardiness")
   - Category (e.g., "Attendance")
   - Severity (Low, Medium, High)
   - Description
3. Edit or delete existing violations as needed

#### **3. Recording Violations**
1. Navigate to **Records**
2. Fill in the form:
   - Select Student
   - Select Violation Type
   - Choose Date
   - Add Notes (optional)
   - Add Disposition (optional)
   - ✅ Check "Send email notification" if needed
3. Click **Save Record**

#### **4. Generating Reports**
1. Go to **Reports**
2. Apply filters:
   - Date Range
   - Student Name/Number
   - Violation Type
   - Category
   - Severity
   - Class
3. Click **Apply Filters**
4. Export options:
   - **Export CSV**: Download as spreadsheet
   - **Download PDF**: Generate PDF report
   - **Print**: Print-friendly view

#### **5. Configuring Email**
1. Navigate to **Email Settings**
2. Enable **Email Notifications**
3. Enable **Use SMTP** (recommended)
4. Configure SMTP:
   - **Host**: `smtp.gmail.com` (for Gmail)
   - **Port**: `587` (TLS) or `465` (SSL)
   - **Username**: Your email address
   - **Password**: App Password (for Gmail)
   - **Encryption**: TLS or SSL
5. Set **From Email** and **From Name**
6. Click **Save Settings**

### **For Students**

1. Login with student credentials
2. View **My Violations** page
3. See all violation records
4. View details: Date, Violation, Category, Severity, Disposition

---

## 📧 Email & Notification System

### **Email Notifications**

**Features:**
- Professional HTML email templates
- Automatic sending when violations are recorded
- Sent to guardian email (if provided)
- Sent to student email (if account linked)
- SMTP support for reliable delivery

**Email Template Includes:**
- School branding
- Student information
- Violation details
- Severity indicator
- Date and notes
- Professional formatting

**SMTP Configuration:**
- **Gmail**: Use App Password (not regular password)
- **Outlook**: Use SMTP settings from Microsoft
- **Other Providers**: Check their SMTP documentation

### **In-App Notifications**

**Features:**
- Real-time notification bell
- Unread count badge
- Dropdown with recent notifications
- Full notification management page
- Auto-refresh every 30 seconds
- Mark as read/unread
- Delete notifications

**Notification Types:**
- 🔵 **Info**: General information
- 🟡 **Warning**: Important notices
- 🔴 **Danger**: Critical alerts
- 🟢 **Success**: Confirmation messages

**When Notifications Are Created:**
- New violation recorded (admins notified)
- System events
- Important updates

---

## 🗄️ Database Structure

### **Tables**

1. **users**
   - User accounts (admin, staff, student)
   - Email, password hash, role

2. **students**
   - Student information
   - Links to user accounts

3. **violations**
   - Violation type definitions
   - Categories and severity levels

4. **violation_records**
   - Actual violation records
   - Links students to violations
   - Notes and dispositions

5. **notifications**
   - In-app notifications
   - User-specific messages

6. **email_settings**
   - Email configuration
   - SMTP settings

### **Relationships**
- `students.user_id` → `users.id`
- `violation_records.student_id` → `students.id`
- `violation_records.violation_id` → `violations.id`
- `violation_records.recorded_by` → `users.id`
- `notifications.user_id` → `users.id`

---

## 🔒 Security Features

### **Authentication & Authorization**
- Password hashing (bcrypt)
- Session-based authentication
- Role-based access control
- SQL injection prevention (prepared statements)
- XSS protection (HTML escaping)

### **Data Protection**
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection (can be added)
- Secure password storage

### **Best Practices**
- Change default passwords
- Use strong passwords
- Regular database backups
- Keep PHP and MySQL updated
- Use HTTPS in production
- Restrict database user privileges

---

## 🎨 UI/UX Features

### **Design Elements**
- Modern gradient backgrounds
- Professional color scheme
- Smooth animations
- Responsive layout
- Intuitive navigation
- Clear visual hierarchy

### **User Experience**
- Fast page loads
- Real-time updates
- Clear error messages
- Success confirmations
- Helpful tooltips
- Mobile-friendly design

---

## 📊 System Capabilities

### **Scalability**
- Handles hundreds of students
- Thousands of violation records
- Efficient database queries
- Optimized for performance

### **Extensibility**
- Modular code structure
- Easy to add new features
- Service-based architecture
- Well-documented code

### **Maintainability**
- Clean code structure
- Consistent naming conventions
- Separation of concerns
- Reusable components

---

## 🔧 Troubleshooting

### **Common Issues**

1. **Database Connection Error**
   - Check MySQL is running
   - Verify credentials in `config.php`
   - Ensure database exists

2. **Email Not Sending**
   - Check SMTP settings
   - Verify email credentials
   - Check firewall settings
   - Use App Password for Gmail

3. **Notifications Not Showing**
   - Check browser console for errors
   - Verify API endpoint is accessible
   - Check database for notifications

4. **Login Issues**
   - Verify credentials
   - Check session is enabled
   - Clear browser cache

---

## 📝 Future Enhancements (Potential)

- Password reset functionality
- Email templates customization
- SMS notifications
- Mobile app
- Advanced analytics dashboard
- Bulk operations
- Data import/export
- Audit logs
- Multi-language support
- Calendar integration

---

## 📞 Support & Documentation

### **Files to Review**
- `README.md` - Quick start guide
- `SYSTEM_OVERVIEW.md` - This document
- Code comments - Inline documentation

### **Getting Help**
- Review this overview
- Check code comments
- Review database schema
- Test in development environment first

---

## ✅ System Status

**Current Version**: 2.0  
**Last Updated**: December 2024  
**Status**: Production Ready  
**License**: MIT

---

**Developed for educational institutions to efficiently manage student violations with modern technology and user-friendly interface.**

