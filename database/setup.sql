-- Database Setup Script
-- Run this to initialize a fresh database
-- Usage: sqlite3 database/app.db < database/setup.sql

-- Clear existing data (if any)
DROP TABLE IF EXISTS quiz_question_answers;
DROP TABLE IF EXISTS quiz_results;
DROP TABLE IF EXISTS quiz_questions;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS content;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS program_months;
DROP TABLE IF EXISTS program_cycles;
DROP TABLE IF EXISTS quiz_statuses;
DROP TABLE IF EXISTS content_types;
DROP TABLE IF EXISTS roles;

-- Create the normalized schema
.read database/schema.sql

-- Load test data
.read database/seed_data.sql