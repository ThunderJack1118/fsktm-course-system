<?php
session_start();
require_once 'database.php';

// Base URL configuration
define('BASE_URL', 'http://localhost/fsktm-course-system');
define('ENVIRONMENT', 'development');

// User roles
define('ROLE_STUDENT', 'student');
define('ROLE_LECTURER', 'lecturer');
define('ROLE_ADMIN', 'admin');

// File upload paths
define('UPLOAD_PROFILE_PATH', 'assets/uploads/profile-pics/');
define('UPLOAD_COURSE_PATH', 'assets/uploads/courses/');
define('UPLOAD_ASSIGNMENT_PATH', 'assets/uploads/assignments/');
define('UPLOAD_SUBMISSION_PATH', 'assets/uploads/submissions/');

// Default images
define('DEFAULT_PROFILE_PIC', 'assets/images/default-profile.jpg');
define('DEFAULT_COURSE_IMAGE', 'default-course.jpg');

// Max file size (5MB)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Allowed file types
define('ALLOWED_TYPES', ['pdf', 'doc', 'docx', 'zip']);

// Autoload classes
spl_autoload_register(function ($class) {
    require_once __DIR__ . '/../includes/' . $class . '.php';
});

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>