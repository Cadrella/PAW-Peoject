-- Users table
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('student', 'professor', 'admin') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE courses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  professor_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (professor_id) REFERENCES users(id)
);

-- Groups table
CREATE TABLE groups (
  id INT PRIMARY KEY AUTO_INCREMENT,
  course_id INT NOT NULL,
  group_name VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Enrollments table
CREATE TABLE enrollments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  group_id INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (group_id) REFERENCES groups(id)
);

-- Attendance sessions table
CREATE TABLE attendance_sessions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  course_id INT NOT NULL,
  group_id INT DEFAULT NULL,
  session_date DATE NOT NULL,
  session_time TIME NOT NULL,
  status ENUM('draft', 'open', 'closed') DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id),
  FOREIGN KEY (group_id) REFERENCES groups(id)
);

-- New simplified sessions table (for professor-facing session entries)
CREATE TABLE sessions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  course_id INT NOT NULL,
  group_id INT DEFAULT NULL,
  name VARCHAR(150) NOT NULL,
  session_date DATE NOT NULL,
  session_time TIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id),
  FOREIGN KEY (group_id) REFERENCES groups(id)
);

-- Attendance records table
CREATE TABLE attendance_records (
  id INT PRIMARY KEY AUTO_INCREMENT,
  session_id INT NOT NULL,
  student_id INT NOT NULL,
  status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES attendance_sessions(id),
  FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Justifications table
CREATE TABLE justifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  attendance_id INT NOT NULL,
  reason TEXT NOT NULL,
  file_path VARCHAR(255),
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  FOREIGN KEY (attendance_id) REFERENCES attendance_records(id)
);
