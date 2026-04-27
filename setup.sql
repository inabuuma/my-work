create database school_assignments_db;
USE school_assignments_db;
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(5) NOT NULL,
    subject VARCHAR(30) NOT NULL,
    assignment_title VARCHAR(200) NOT NULL,
    due_date DATE NOT NULL,
    marks INT NOT NULL,
    remarks TEXT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO assignments 
(student_name, student_id, subject, assignment_title, due_date, marks, remarks)
VALUES 
('Mukisa Simon', '12345678', 'Mathematics', 'Algebra Assignment', '2026-04-15', 85, 'Good work'),

('Natukunda Ritah', '23456789', 'English', 'Essay Writing', '2026-04-16', 78, 'Needs improvement in grammar'),

('Nabuuma Immaculate', '34567891', 'Computer Science', 'Database Design', '2026-04-18', 92, 'Excellent understanding'),

('James Stepten', '45678912', 'Biology', 'Human Anatomy', '2026-04-20', 88, NULL),

('Lubale Johnson', '56789123', 'Physics', 'Newton Laws', '2026-04-22', 81, 'Well explained');