# Student Violation Monitoring System (SVMS)

A comprehensive PHP/MySQL web application for educational institutions to efficiently track, manage, and monitor student violations with email notifications and real-time alerts.

## 🚀 Quick Start

1. **Start XAMPP Services**
   - Start Apache
   - Start MySQL

2. **Create Database**
   ```bash
   # Via phpMyAdmin: Import svms.sql
   # Or via command line:
   mysql -u root < svms.sql
   ```

3. **Access the System**
   ```
   http://localhost/Student-Violation-Monitoring-System-SVMS--main/
   ```

## 👤 Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@example.com` | `admin123` |
| Admin | `mattjhevicadmin@example.com` | `admin123` |
| Staff | `staff@example.com` | `staff123` |
| Staff | `mattjhevicstaff@example.com` | `staff123` |

⚠️ **Change default passwords in production!**

## ✨ Key Features

- ✅ **Student Management** - Complete CRUD operations
- ✅ **Violation Tracking** - Record and categorize violations
- ✅ **Dashboard & Analytics** - Real-time statistics and trends
- ✅ **Reports & Export** - CSV, PDF, and print options
- ✅ **Email Notifications** - Automated emails to guardians/students
- ✅ **In-App Notifications** - Real-time notification system
- ✅ **Role-Based Access** - Admin, Staff, and Student roles
- ✅ **Modern UI** - Responsive design with Bootstrap 5
- ✅ **Search & Filter** - Advanced filtering capabilities

## 📋 Requirements

- XAMPP (Apache, PHP 7.4+ or 8.x, MySQL/MariaDB)
- Modern web browser
- For email: SMTP server (Gmail, Outlook, etc.)

## 📚 Documentation

- **[SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)** - Complete system documentation
- Code comments - Inline documentation

## 🎯 Main Modules

- **Dashboard** - Overview statistics and KPIs
- **Students** - Student database management
- **Violations** - Violation type definitions
- **Records** - Record violations with email notifications
- **Reports** - Comprehensive reporting with filters
- **Statistics** - Detailed analytics
- **Notifications** - In-app notification management
- **Email Settings** - SMTP configuration

## 🔧 Configuration

### Database
Edit `config.php` if using different MySQL credentials:
```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'newmatt');
```

### Email Settings
1. Navigate to **Email Settings** after login
2. Enable SMTP and configure your email provider
3. For Gmail: Use App Password (not regular password)

## 🔒 Security

- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection
- Session management
- Role-based access control

## 📦 Project Structure

```
├── Core Files (config, auth, index)
├── Main Modules (dashboard, students, violations, records, reports)
├── Notification System (notifications, api, service)
├── Email System (email service, settings)
├── UI Components (header, footer, nav, styles, scripts)
└── Database (svms.sql)
```

## 🆘 Troubleshooting

- **Database Error**: Check MySQL is running and credentials are correct
- **Email Not Sending**: Verify SMTP settings and use App Password for Gmail
- **Notifications Not Showing**: Check browser console for errors

## 📄 License

MIT License

---

**For complete documentation, see [SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)**

