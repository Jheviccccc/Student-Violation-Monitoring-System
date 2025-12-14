-- Database: newmatt
CREATE DATABASE IF NOT EXISTS  newmatt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE  newmatt;

-- Users
 CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(191) NOT NULL,
  role ENUM('admin','staff','student','viewer') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Students
 CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_no VARCHAR(64) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  class VARCHAR(100) NULL,
  section VARCHAR(100) NULL,
  guardian_contact VARCHAR(100) NULL,
  user_id INT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (last_name),
  INDEX (student_no)
) ENGINE=InnoDB;

ALTER TABLE students
  ADD INDEX idx_students_user_id (user_id),
  ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Violations
CREATE TABLE IF NOT EXISTS violations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(191) NOT NULL,
  category VARCHAR(100) NOT NULL,
  severity ENUM('low','medium','high') NOT NULL DEFAULT 'low',
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (category)
) ENGINE=InnoDB;

 -- Violation Records
 CREATE TABLE IF NOT EXISTS violation_records (
   id INT AUTO_INCREMENT PRIMARY KEY,
   student_id INT NOT NULL,
   violation_id INT NOT NULL,
   recorded_by INT NULL,
   occurred_at DATE NOT NULL,
   notes TEXT NULL,
   disposition VARCHAR(191) NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   INDEX (student_id),
   INDEX (violation_id),
   INDEX (recorded_by),
   CONSTRAINT fk_vr_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
   CONSTRAINT fk_vr_violation FOREIGN KEY (violation_id) REFERENCES violations(id) ON DELETE RESTRICT,
   CONSTRAINT fk_vr_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
 ) ENGINE=InnoDB;

-- Seed users
INSERT INTO users (email, password_hash, full_name, role) VALUES
('admin@example.com', CONCAT('$2y$10$', SUBSTRING(MD5(RAND()),1,22), 'not-used'), 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- Update admin to a known password hash for 'admin123'
UPDATE users SET password_hash = '$2y$10$8O0Q7oBAnuC0X1r3m6SNte/Xd7H8k4g8H1gC9wAc6n7O2o7z3c5pG' WHERE email='admin@example.com';

-- Removed staff seed

-- Additional admin and staff accounts
INSERT INTO users (email, password_hash, full_name, role) VALUES
('mattjhevicadmin@example.com', '$2y$10$8U5Yy9mo9RG2a3AwkBldpejGyVw0cPap8I1GhM90OhamDEJpyGDMe', 'Matt Jhevic Admin', 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- Removed additional staff seed

-- Viewer account
INSERT INTO users (email, password_hash, full_name, role) VALUES
('viewer@example.com', '$2y$10$kqG8l7RrH6Cw2aD1bE9nzeYk7iV9oQp3ZyS8m1Nf2H3J4K5L6M7Na', 'Viewer User', 'viewer')
ON DUPLICATE KEY UPDATE email=email;

-- Notes on hashes:
-- admin123 -> $2y$10$8O0Q7oBAnuC0X1r3m6SNte/Xd7H8k4g8H1gC9wAc6n7O2o7z3c5pG
-- mattjhevicadmin@example.com -> admin123 -> $2y$10$8U5Yy9mo9RG2a3AwkBldpejGyVw0cPap8I1GhM90OhamDEJpyGDMe

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info', 'warning', 'danger', 'success') NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  link VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id),
  INDEX (is_read),
  INDEX (created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Email settings table
CREATE TABLE IF NOT EXISTS email_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default email settings
INSERT INTO email_settings (setting_key, setting_value) VALUES
('smtp_enabled', '0'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_encryption', 'tls'),
('from_email', 'noreply@school.edu'),
('from_name', 'School Violation Monitoring System'),
('email_enabled', '1')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Seed some violations
INSERT INTO violations (title, category, severity, description) VALUES
('Tardiness', 'Attendance', 'low', 'Late to class'),
('Cutting classes', 'Attendance', 'medium', 'Skipping classes without excuse'),
('Disrespectful behavior', 'Conduct', 'medium', 'Disrespect to staff or students'),
('Fighting', 'Conduct', 'high', 'Physical altercation with another student')
ON DUPLICATE KEY UPDATE title=VALUES(title);

