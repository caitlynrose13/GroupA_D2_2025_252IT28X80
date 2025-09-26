-- Initial Database Schema for Cybersecurity Awareness Programme Management System
-- Created: September 26, 2025

-- Users table for authentication and role management
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role TEXT NOT NULL, 
    department VARCHAR(100),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Content table for flexible content management
CREATE TABLE content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    content_type TEXT NOT NULL, 
    cycle_number INTEGER,
    month_number INTEGER,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes table (one per cycle end - months 4, 8, 12)
CREATE TABLE quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    cycle_number INTEGER NOT NULL,
    passing_score INTEGER DEFAULT 70,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quiz questions table (handles both multiple choice and true/false)
CREATE TABLE quiz_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    question_type TEXT NOT NULL, -- was ENUM('multiple_choice', 'true_false')
    -- For multiple choice questions (4 options)
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    correct_answer VARCHAR(10) NOT NULL, -- 'A', 'B', 'C', 'D' for MC; 'TRUE', 'FALSE' for T/F
    question_order INTEGER NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

-- User quiz results table (simple score tracking for reporting)
CREATE TABLE quiz_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    quiz_id INTEGER NOT NULL,
    score INTEGER NOT NULL,
    total_questions INTEGER NOT NULL,
    percentage INTEGER NOT NULL,
    passed BOOLEAN NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);