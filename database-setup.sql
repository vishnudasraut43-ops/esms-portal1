-- ESMS Portal Database Setup
CREATE DATABASE IF NOT EXISTS esms_portal;
USE esms_portal;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(20) UNIQUE NOT NULL,
    enrollment_no VARCHAR(20) UNIQUE NOT NULL,
    branch VARCHAR(10) NOT NULL,
    year INT NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    branch VARCHAR(10) NOT NULL
);

INSERT INTO hods (username, name, branch) VALUES
('hodcm', 'Computer HOD', 'CM'),
('hodej', 'Electronics HOD', 'EJ'),
('hodme', 'Mechanical HOD', 'ME'),
('hodce', 'Civil HOD', 'CE'),
('hodai', 'AI HOD', 'AI'),
('hodee', 'Electrical HOD', 'EE'),
('hodit', 'IT HOD', 'IT');

CREATE TABLE class_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    branch VARCHAR(10) NOT NULL,
    year INT NOT NULL
);

INSERT INTO class_teachers (username, name, branch, year) VALUES
('CM1', 'Computer Year 1 Teacher', 'CM', 1),
('CM2', 'Computer Year 2 Teacher', 'CM', 2),
('CM3', 'Computer Year 3 Teacher', 'CM', 3);

CREATE TABLE student_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(20) NOT NULL,
    branch VARCHAR(10) NOT NULL,
    year INT NOT NULL,
    request_type ENUM('leave', 'getpass', 'bonafide') NOT NULL,
    request_data JSON NOT NULL,
    class_teacher_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    hod_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    class_teacher_remarks TEXT,
    hod_remarks TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
