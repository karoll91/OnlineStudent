-- Online Student Registration System Database
-- Created: 2024

CREATE DATABASE IF NOT EXISTS online_student_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_student_db;

-- Students table
CREATE TABLE students (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          student_id VARCHAR(20) UNIQUE NOT NULL,
                          first_name VARCHAR(50) NOT NULL,
                          last_name VARCHAR(50) NOT NULL,
                          email VARCHAR(100) UNIQUE NOT NULL,
                          phone VARCHAR(15),
                          password VARCHAR(255) NOT NULL,
                          date_of_birth DATE,
                          gender ENUM('Male', 'Female') NOT NULL,
                          address TEXT,
                          city VARCHAR(50),
                          profile_photo VARCHAR(255),
                          is_active BOOLEAN DEFAULT TRUE,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE courses (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         course_code VARCHAR(20) UNIQUE NOT NULL,
                         course_name VARCHAR(100) NOT NULL,
                         description TEXT,
                         credits INT DEFAULT 3,
                         instructor VARCHAR(100),
                         is_active BOOLEAN DEFAULT TRUE,
                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes table
CREATE TABLE quizzes (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         title VARCHAR(200) NOT NULL,
                         description TEXT,
                         course_id INT,
                         time_limit INT DEFAULT 30, -- in minutes
                         total_questions INT DEFAULT 0,
                         passing_score INT DEFAULT 60, -- percentage
                         is_active BOOLEAN DEFAULT TRUE,
                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Quiz questions table
CREATE TABLE quiz_questions (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                quiz_id INT NOT NULL,
                                question_text TEXT NOT NULL,
                                option_a VARCHAR(255) NOT NULL,
                                option_b VARCHAR(255) NOT NULL,
                                option_c VARCHAR(255) NOT NULL,
                                option_d VARCHAR(255) NOT NULL,
                                correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
                                points INT DEFAULT 1,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz attempts table
CREATE TABLE quiz_attempts (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               student_id INT NOT NULL,
                               quiz_id INT NOT NULL,
                               start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                               end_time TIMESTAMP NULL,
                               total_questions INT,
                               correct_answers INT DEFAULT 0,
                               score DECIMAL(5,2) DEFAULT 0,
                               status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
                               time_taken INT, -- in seconds
                               FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                               FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz answers table
CREATE TABLE quiz_answers (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              attempt_id INT NOT NULL,
                              question_id INT NOT NULL,
                              selected_answer ENUM('A', 'B', 'C', 'D'),
                              is_correct BOOLEAN DEFAULT FALSE,
                              answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
                              FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Course enrollments table
CREATE TABLE course_enrollments (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    student_id INT NOT NULL,
                                    course_id INT NOT NULL,
                                    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    status ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
                                    final_grade VARCHAR(5),
                                    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                                    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                                    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Admin users table
CREATE TABLE admin_users (
                             id INT AUTO_INCREMENT PRIMARY KEY,
                             username VARCHAR(50) UNIQUE NOT NULL,
                             email VARCHAR(100) UNIQUE NOT NULL,
                             password VARCHAR(255) NOT NULL,
                             full_name VARCHAR(100) NOT NULL,
                             role ENUM('admin', 'super_admin') DEFAULT 'admin',
                             is_active BOOLEAN DEFAULT TRUE,
                             last_login TIMESTAMP NULL,
                             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data

-- Insert sample courses
INSERT INTO courses (course_code, course_name, description, credits, instructor) VALUES
                                                                                     ('CS101', 'Introduction to Computer Science', 'Basic concepts of programming and computer science', 3, 'Dr. John Smith'),
                                                                                     ('MATH101', 'Mathematics I', 'Fundamentals of mathematics including algebra and geometry', 4, 'Prof. Sarah Johnson'),
                                                                                     ('ENG101', 'English Language', 'English grammar, vocabulary and communication skills', 2, 'Ms. Emily Brown'),
                                                                                     ('PHYS101', 'Physics I', 'Basic principles of physics including mechanics and energy', 3, 'Dr. Michael Davis');

-- Insert sample quiz
INSERT INTO quizzes (title, description, course_id, time_limit, total_questions, passing_score) VALUES
    ('CS101 - Midterm Quiz', 'Midterm examination for Introduction to Computer Science', 1, 45, 10, 70);

-- Insert sample questions for the quiz
INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
                                                                                                                (1, 'What does CPU stand for?', 'Central Processing Unit', 'Computer Personal Unit', 'Central Program Unit', 'Computer Processing Unit', 'A'),
                                                                                                                (1, 'Which is a programming language?', 'HTML', 'Python', 'CSS', 'XML', 'B'),
                                                                                                                (1, 'What is RAM?', 'Read Access Memory', 'Random Access Memory', 'Rapid Access Memory', 'Real Access Memory', 'B'),
                                                                                                                (1, 'Binary number 1010 equals?', '8', '10', '12', '14', 'B'),
                                                                                                                (1, 'What is an algorithm?', 'A programming language', 'A computer program', 'A step-by-step procedure', 'A type of software', 'C');

-- Update quiz total questions
UPDATE quizzes SET total_questions = 5 WHERE id = 1;

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, email, password, full_name, role) VALUES
    ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Create indexes for better performance
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_students_student_id ON students(student_id);
CREATE INDEX idx_quiz_attempts_student ON quiz_attempts(student_id);
CREATE INDEX idx_quiz_attempts_quiz ON quiz_attempts(quiz_id);
CREATE INDEX idx_course_enrollments_student ON course_enrollments(student_id);
CREATE INDEX idx_course_enrollments_course ON course_enrollments(course_id);