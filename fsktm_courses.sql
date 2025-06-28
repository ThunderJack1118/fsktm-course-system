-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2025 at 10:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fsktm_courses`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `course_id`, `created_by`, `priority`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to New Academic Year', 'Dear students, welcome to the 2024/2025 academic year! Important dates will be shared soon.', NULL, 1, 'high', 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(2, 'Database Systems Lab Change', 'The lab for CSC102 Database Systems has been changed to Lab B3 starting next week.', 2, 3, 'medium', 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(3, 'Programming Assignment Deadline', 'Reminder: Assignment 1 for CSC101 is due this Friday at 5pm. Late submissions will be penalized.', 1, 2, 'high', 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(4, 'Career Fair Announcement', 'The annual FSKTM Career Fair will be held on March 15th. Register at the student portal.', NULL, 1, 'medium', 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(5, 'Mobile Development Prerequisites', 'Students registering for CSC301 must complete CSC201 first. Contact department if you have questions.', 5, 4, 'medium', 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `max_marks` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `course_id`, `title`, `description`, `due_date`, `max_marks`, `created_at`, `updated_at`) VALUES
(1, 1, 'Python Basics', 'Complete the Python exercises on variables, loops, and functions', '2024-02-15 23:59:00', 100, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(2, 4, 'OOP Project', 'Create a Python program demonstrating OOP concepts', '2024-03-20 23:59:00', 100, '2025-06-16 06:26:40', '2025-06-18 15:15:29'),
(3, 2, 'Database Design', 'Design an ER diagram for a library management system', '2024-02-20 23:59:00', 100, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(4, 3, 'Website Prototype', 'Create a responsive website with HTML/CSS', '2024-02-25 23:59:00', 100, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(5, 6, 'AI Research Paper', 'Write a 5-page paper on an AI application', '2024-03-10 23:59:00', 100, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(11, 1, 'C Programming Lab 1', 'print triangle', '2025-06-20 00:00:00', 100, '2025-06-19 04:18:22', '2025-06-19 04:18:22'),
(13, 7, '2.9.2 Lab', 'Basic Switch and End Device Configuration', '2025-06-26 00:00:00', 100, '2025-06-19 06:33:11', '2025-06-19 06:33:11');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_files`
--

CREATE TABLE `assignment_files` (
  `file_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_files`
--

INSERT INTO `assignment_files` (`file_id`, `assignment_id`, `file_name`, `file_path`, `file_size`, `file_type`, `uploaded_at`) VALUES
(1, 11, 'lab_activity1.docx', 'assignments/68538f8ed0cad.docx', 88491, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2025-06-19 04:18:22'),
(5, 13, '2.9.2_Lab_-_Basic_Switch_and_End_Device_Configuration_0.pdf', 'assignments/6853af27ad711.pdf', 423161, 'application/pdf', '2025-06-19 06:33:11');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(10) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) NOT NULL DEFAULT 3,
  `semester` enum('1','2','3') NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `max_students` int(11) DEFAULT 40,
  `current_enrolled` int(11) DEFAULT 0,
  `instructor_id` int(11) DEFAULT NULL,
  `schedule_day` varchar(20) DEFAULT NULL,
  `schedule_time` varchar(50) DEFAULT NULL,
  `classroom` varchar(20) DEFAULT NULL,
  `course_image` varchar(255) DEFAULT 'default-course.jpg',
  `prerequisites` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `description`, `credits`, `semester`, `academic_year`, `max_students`, `current_enrolled`, `instructor_id`, `schedule_day`, `schedule_time`, `classroom`, `course_image`, `prerequisites`, `category_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CSC101', 'Introduction to Programming', 'Fundamentals of programming using Python', 3, '1', '2024/2025', 40, 3, 2, 'Monday', '08:00-10:00', 'Lab A1', 'default-course.jpg', NULL, 1, 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(2, 'CSC102', 'Database Systems', 'Introduction to relational databases and SQL', 3, '1', '2024/2025', 35, 2, 3, 'Tuesday', '10:00-12:00', 'Lab B2', 'default-course.jpg', NULL, 2, 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(3, 'CSC201', 'Web Development', 'Full-stack web development with HTML, CSS, JavaScript, and PHP', 3, '1', '2024/2025', 30, 3, 3, 'Wednesday', '14:00-16:00', 'Lab C3', 'default-course.jpg', 'CSC101', 5, 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(4, 'CSC202', 'Object-Oriented Programming', 'Advanced programming concepts using Java', 3, '2', '2024/2025', 35, 2, 2, 'Thursday', '09:00-11:00', 'Lab A2', 'default-course.jpg', 'CSC101', 1, 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(5, 'CSC301', 'Mobile App Development', 'Cross-platform mobile development with Flutter', 3, '2', '2024/2025', 25, 2, 4, 'Friday', '11:00-13:00', 'Lab D1', 'default-course.jpg', 'CSC201', 6, 1, '2025-06-16 06:26:40', '2025-06-18 07:27:26'),
(6, 'CSC302', 'Artificial Intelligence', 'Introduction to AI and machine learning', 3, '1', '2024/2025', 30, 2, 5, 'Monday', '14:00-16:00', 'Lecture Hall 1', 'default-course.jpg', 'CSC101', 4, 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(7, 'CSC401', 'Computer Networks', 'Network protocols and security', 3, '2', '2024/2025', 30, 2, 6, 'Tuesday', '15:00-17:00', 'Lab E1', 'default-course.jpg', NULL, 3, 1, '2025-06-16 06:26:40', '2025-06-16 17:35:56'),
(8, 'CSC402', 'Data Science Fundamentals', 'Data analysis and visualization with Python', 3, '1', '2024/2025', 25, 1, 5, 'Wednesday', '10:00-12:00', 'Lab F1', 'default-course.jpg', 'CSC101', 7, 1, '2025-06-16 06:26:40', '2025-06-18 07:19:42'),
(9, 'CSC501', 'Cybersecurity Essentials', 'Information security principles and practices', 3, '2', '2024/2025', 25, 1, 6, 'Thursday', '13:00-15:00', 'Lecture Hall 2', 'default-course.jpg', 'CSC401', 8, 1, '2025-06-16 06:26:40', '2025-06-16 06:26:40'),
(10, 'CSC502', 'Advanced Web Development', 'Modern web frameworks and APIs', 3, '1', '2024/2025', 20, 2, 3, 'Friday', '09:00-11:00', 'Lab C2', 'default-course.jpg', 'CSC201', 5, 1, '2025-06-16 06:26:40', '2025-06-28 20:18:24');

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color_code` varchar(7) DEFAULT '#007bff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_categories`
--

INSERT INTO `course_categories` (`category_id`, `category_name`, `description`, `color_code`, `created_at`) VALUES
(1, 'Programming', 'Programming and Software Development courses', '#28a745', '2025-06-16 06:26:40'),
(2, 'Database', 'Database Management and Design courses', '#dc3545', '2025-06-16 06:26:40'),
(3, 'Networking', 'Computer Networks and Security courses', '#ffc107', '2025-06-16 06:26:40'),
(4, 'AI & Machine Learning', 'Artificial Intelligence and ML courses', '#6f42c1', '2025-06-16 06:26:40'),
(5, 'Web Development', 'Frontend and Backend Web Development', '#fd7e14', '2025-06-16 06:26:40'),
(6, 'Mobile Development', 'Mobile App Development courses', '#20c997', '2025-06-16 06:26:40'),
(7, 'Data Science', 'Data Analysis and Visualization', '#17a2b8', '2025-06-16 06:26:40'),
(8, 'Cybersecurity', 'Information Security courses', '#343a40', '2025-06-16 06:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `course_resources`
--

CREATE TABLE `course_resources` (
  `resource_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_resources`
--

INSERT INTO `course_resources` (`resource_id`, `course_id`, `title`, `description`, `file_path`, `file_size`, `file_type`, `uploaded_by`, `uploaded_at`) VALUES
(1, 1, 'Chapter 2', 'Problem Solving and Algorithm', '1750337964_c2_problemsolvingandalgorithm.pptx', NULL, NULL, 2, '2025-06-19 20:59:24');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected','dropped','completed') DEFAULT 'pending',
  `grade` varchar(3) DEFAULT NULL,
  `attendance_percentage` decimal(5,2) DEFAULT 0.00,
  `final_marks` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`registration_id`, `user_id`, `course_id`, `registration_date`, `status`, `grade`, `attendance_percentage`, `final_marks`, `notes`) VALUES
(1, 7, 1, '2024-01-10 01:00:00', 'approved', 'A', 95.50, 92.00, NULL),
(2, 7, 2, '2024-01-10 01:05:00', 'approved', 'A-', 90.25, 87.50, NULL),
(3, 7, 3, '2024-01-11 02:00:00', 'approved', 'B+', 88.75, 82.00, NULL),
(4, 8, 1, '2024-01-10 01:30:00', 'approved', 'B+', 85.00, 80.50, NULL),
(5, 8, 4, '2024-01-12 03:00:00', 'approved', 'A', 93.25, 91.00, NULL),
(6, 8, 6, '2024-01-12 03:05:00', 'approved', 'A-', 91.50, 88.75, NULL),
(7, 9, 2, '2024-01-11 01:00:00', 'approved', 'A', 96.00, 93.50, NULL),
(8, 9, 3, '2024-01-11 01:30:00', 'approved', 'B', 82.50, 77.00, NULL),
(9, 9, 5, '2024-01-15 06:00:00', 'pending', NULL, 0.00, 0.00, NULL),
(10, 10, 1, '2024-01-13 02:00:00', 'approved', 'A+', 98.00, 95.00, NULL),
(11, 10, 6, '2024-01-13 02:05:00', 'approved', 'A', 94.50, 92.25, NULL),
(12, 10, 8, '2024-01-14 03:00:00', 'approved', 'B+', 87.75, 83.50, NULL),
(13, 11, 4, '2024-01-14 01:00:00', 'approved', 'A-', 90.00, 86.50, NULL),
(14, 11, 7, '2024-01-14 01:30:00', 'approved', 'B', 83.25, 78.00, NULL),
(15, 11, 9, '2024-01-15 02:00:00', 'approved', 'B+', 86.50, 81.75, NULL),
(16, 12, 3, '2024-01-15 03:00:00', 'approved', 'A', 94.00, 90.50, NULL),
(17, 12, 5, '2024-01-15 03:30:00', 'approved', 'A-', 89.75, 85.25, NULL),
(18, 12, 10, '2024-01-16 01:00:00', 'pending', NULL, 0.00, 0.00, NULL),
(19, 7, 7, '2025-06-16 17:35:56', 'approved', 'A', 0.00, 0.00, NULL),
(22, 7, 5, '2025-06-18 07:27:26', 'pending', NULL, 0.00, 0.00, NULL),
(23, 7, 10, '2025-06-26 07:45:59', 'pending', NULL, 0.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) NOT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','late','graded') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submission_id`, `assignment_id`, `user_id`, `submission_date`, `file_path`, `marks`, `feedback`, `status`) VALUES
(1, 1, 7, '2025-06-16 06:26:40', 'submissions/python_basics_john.zip', 85.50, 'Good work, but need more comments in code', 'graded'),
(2, 1, 8, '2025-06-16 06:26:40', 'submissions/python_basics_jane.zip', 92.00, 'Excellent implementation!', 'graded'),
(3, 1, 10, '2025-06-16 06:26:40', 'submissions/python_basics_mei.zip', 95.00, 'Perfect solution with great documentation', 'graded'),
(4, 3, 7, '2025-06-16 06:26:40', 'submissions/db_design_john.pdf', 78.00, 'Good start but missing some relationships', 'graded'),
(5, 3, 9, '2025-06-16 06:26:40', 'submissions/db_design_ahmad.pdf', 88.50, 'Well-designed with clear documentation', 'graded'),
(6, 4, 12, '2025-06-16 06:26:40', 'submissions/web_prototype_sara.zip', 91.00, 'Beautiful design and fully responsive', 'graded'),
(8, 13, 7, '2025-06-19 06:51:10', 'submission_7_13_1750315870.pdf', 80.00, 'Good Work! Keep it Up!', 'graded');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `user_type` enum('student','lecturer','admin') DEFAULT 'student',
  `profile_picture` varchar(255) DEFAULT 'default-avatar.png',
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `staff_id` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `user_type`, `profile_picture`, `phone`, `date_of_birth`, `student_id`, `staff_id`, `address`, `department`, `created_at`, `updated_at`, `is_active`, `last_login`) VALUES
(1, 'admin', 'admin@fsktm.edu.my', '$2a$12$79SdsP1nbxQ5gbS2eptNW.Mb708OtSdiII3K9Vi2cLWdZHbD0tD0u', 'System', 'Administrator', 'admin', 'profile_1_1750089204.png', '0389212345', '0000-00-00', NULL, 'ADM001', '', 'Administration', '2025-06-16 06:26:40', '2025-06-28 08:46:48', 1, '2025-06-26 16:44:47'),
(2, 'dr_ahmad', 'ahmad@fsktm.edu.my', '$2a$12$OH8SHQ6HktwoEyrLrv51iuHETcQg2zEytgdtBN7EgeLMcDSZr4HDy', 'Ahmad', 'Hassan', 'lecturer', 'profile_2_1750092346.png', '0168600321', NULL, NULL, 'LEC001', NULL, 'Information Technology', '2025-06-16 06:26:40', '2025-06-28 18:05:23', 1, '2025-06-26 16:41:38'),
(3, 'dr_siti', 'siti@fsktm.edu.my', '$2a$12$OH8SHQ6HktwoEyrLrv51iuHETcQg2zEytgdtBN7EgeLMcDSZr4HDy', 'Siti', 'Nurhaliza', 'lecturer', 'default-avatar.png', '0389210002', NULL, NULL, 'LEC002', NULL, 'Information Technology', '2025-06-16 06:26:40', '2025-06-28 08:46:48', 1, '2025-06-26 08:12:59'),
(4, 'prof_lee', 'lee@fsktm.edu.my', '$2a$12$OH8SHQ6HktwoEyrLrv51iuHETcQg2zEytgdtBN7EgeLMcDSZr4HDy', 'Lee', 'Wei Ming', 'lecturer', 'default-avatar.png', '0389210003', NULL, NULL, 'LEC003', NULL, 'Software Engineering', '2025-06-16 06:26:40', '2025-06-28 08:46:48', 1, NULL),
(5, 'dr_rajesh', 'rajesh@fsktm.edu.my', '$2a$12$OH8SHQ6HktwoEyrLrv51iuHETcQg2zEytgdtBN7EgeLMcDSZr4HDy', 'Rajesh', 'Kumar', 'lecturer', 'default-avatar.png', '0389210004', NULL, NULL, 'LEC004', NULL, 'Software Engineering', '2025-06-16 06:26:40', '2025-06-28 08:56:59', 1, NULL),
(6, 'dr_fatimah', 'fatimah@fsktm.edu.my', '$2a$12$OH8SHQ6HktwoEyrLrv51iuHETcQg2zEytgdtBN7EgeLMcDSZr4HDy', 'Fatimah', 'Ali', 'lecturer', 'default-avatar.png', '0389210005', NULL, NULL, 'LEC005', NULL, 'Network Security', '2025-06-16 06:26:40', '2025-06-28 18:41:35', 1, '2025-06-19 06:54:37'),
(7, 'john_doe', 'john@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'John', 'Doe', 'student', 'profile_7_1750923892.png', '0191234567', '2001-05-15', 'STU001', NULL, '', 'Information Security', '2025-06-16 06:26:40', '2025-06-28 08:58:02', 1, '2025-06-26 07:44:22'),
(8, 'jane_smith', 'jane@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'Jane', 'Smith', 'student', 'default-avatar.png', '0192345678', '2000-12-03', 'STU002', NULL, NULL, 'Information Technology', '2025-06-16 06:26:40', '2025-06-28 08:46:48', 0, NULL),
(9, 'ahmad_ibrahim', 'ahmad@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'Ahmad', 'Ibrahim', 'student', 'default-avatar.png', '0325223211', NULL, 'STU003', NULL, '18, TAMAN RONA', 'Software Engineering', '2025-06-16 06:26:40', '2025-06-28 19:18:21', 1, NULL),
(10, 'mei_ling', 'mei@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'Mei', 'Ling', 'student', 'default-avatar.png', '0194567890', '2002-03-10', 'STU004', NULL, NULL, 'Software Engineering', '2025-06-16 06:26:40', '2025-06-28 08:57:31', 1, NULL),
(11, 'ali_omar', 'ali@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'Ali', 'Omar', 'student', 'default-avatar.png', '0195678901', '2001-11-25', 'STU005', NULL, NULL, 'Network Security', '2025-06-16 06:26:40', '2025-06-28 08:47:40', 1, NULL),
(12, 'sara_wong', 'sara@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'Sara', 'Wong', 'student', 'default-avatar.png', '0196789012', '2000-07-18', 'STU006', NULL, NULL, 'Software Engineering', '2025-06-16 06:26:40', '2025-06-28 08:58:30', 1, NULL),
(13, 'david_lim', 'david@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'David', 'Lim', 'student', 'default-avatar.png', '0197890123', '2001-09-30', 'STU007', NULL, NULL, 'Information Technology', '2025-06-16 06:26:40', '2025-06-28 08:47:56', 1, NULL),
(14, 'nurul_huda', 'nurul@student.fsktm.edu.my', '$2a$12$lB9OHQh8th5S/d.nvM.0GuIZwuKgXDywBZ8KH4PPA1f/zwfTJG7ES', 'Nurul', 'Huda', 'student', 'default-avatar.png', '0198901234', '2002-01-05', 'STU008', NULL, NULL, 'Software Engineering', '2025-06-16 06:26:40', '2025-06-28 08:48:05', 1, '0000-00-00 00:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_announcement_course` (`course_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_assignment_course` (`course_id`);

--
-- Indexes for table `assignment_files`
--
ALTER TABLE `assignment_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_course_code` (`course_code`),
  ADD KEY `idx_course_category` (`category_id`),
  ADD KEY `idx_course_instructor` (`instructor_id`);

--
-- Indexes for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `course_resources`
--
ALTER TABLE `course_resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
  ADD KEY `idx_registration_user` (`user_id`),
  ADD KEY `idx_registration_course` (`course_id`),
  ADD KEY `idx_registration_status` (`status`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_submission_assignment` (`assignment_id`),
  ADD KEY `idx_submission_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_student_id` (`student_id`),
  ADD KEY `idx_user_staff_id` (`staff_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `assignment_files`
--
ALTER TABLE `assignment_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `course_categories`
--
ALTER TABLE `course_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `course_resources`
--
ALTER TABLE `course_resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_files`
--
ALTER TABLE `assignment_files`
  ADD CONSTRAINT `assignment_files_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `course_resources`
--
ALTER TABLE `course_resources`
  ADD CONSTRAINT `course_resources_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_resources_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
