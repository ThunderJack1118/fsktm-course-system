<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // Validate and sanitize inputs
        $course_code = sanitizeInput($_POST['course_code']);
        $course_name = sanitizeInput($_POST['course_name']);
        $description = sanitizeInput($_POST['description']);
        $credits = (int)$_POST['credits'];
        $semester = sanitizeInput($_POST['semester']);
        $academic_year = sanitizeInput($_POST['academic_year']);
        $max_students = (int)$_POST['max_students'];
        $instructor_id = !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;
        $schedule_day = sanitizeInput($_POST['schedule_day']);
        $schedule_time = sanitizeInput($_POST['schedule_time']);
        $classroom = sanitizeInput($_POST['classroom']);
        $prerequisites = sanitizeInput($_POST['prerequisites']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($course_code)) {
            echo json_encode(['success' => false, 'message' => 'Course code is required']);
            exit;
        }
        
        if (empty($course_name)) {
            echo json_encode(['success' => false, 'message' => 'Course name is required']);
            exit;
        }
        
        // Check if course code already exists
        $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ?");
        $stmt->bind_param("s", $course_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Course code already exists']);
            exit;
        }
        
        $stmt->close();
        
        // Insert new course
        $stmt = $conn->prepare("INSERT INTO courses (
            course_code, course_name, description, credits, semester, academic_year,
            max_students, instructor_id, schedule_day, schedule_time, classroom,
            prerequisites, category_id, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssisssisssssi", 
            $course_code, $course_name, $description, $credits, $semester, $academic_year,
            $max_students, $instructor_id, $schedule_day, $schedule_time, $classroom,
            $prerequisites, $category_id, $is_active
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Course added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add course']);
        }
        
        $stmt->close();
        break;
        
    case 'update':
        $course_id = (int)$_POST['course_id'];
        $course_code = sanitizeInput($_POST['course_code']);
        $course_name = sanitizeInput($_POST['course_name']);
        $description = sanitizeInput($_POST['description']);
        $credits = (int)$_POST['credits'];
        $semester = sanitizeInput($_POST['semester']);
        $academic_year = sanitizeInput($_POST['academic_year']);
        $max_students = (int)$_POST['max_students'];
        $instructor_id = !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;
        $schedule_day = sanitizeInput($_POST['schedule_day']);
        $schedule_time = sanitizeInput($_POST['schedule_time']);
        $classroom = sanitizeInput($_POST['classroom']);
        $prerequisites = sanitizeInput($_POST['prerequisites']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($course_code)) {
            echo json_encode(['success' => false, 'message' => 'Course code is required']);
            exit;
        }
        
        if (empty($course_name)) {
            echo json_encode(['success' => false, 'message' => 'Course name is required']);
            exit;
        }
        
        // Check if course code already exists for another course
        $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?");
        $stmt->bind_param("si", $course_code, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Course code already exists for another course']);
            exit;
        }
        
        $stmt->close();
        
        // Update course
        $stmt = $conn->prepare("UPDATE courses SET 
            course_code = ?, course_name = ?, description = ?, credits = ?, semester = ?, 
            academic_year = ?, max_students = ?, instructor_id = ?, schedule_day = ?, 
            schedule_time = ?, classroom = ?, prerequisites = ?, category_id = ?, is_active = ?
            WHERE course_id = ?");
        
        $stmt->bind_param("sssisssisssssii", 
            $course_code, $course_name, $description, $credits, $semester, $academic_year,
            $max_students, $instructor_id, $schedule_day, $schedule_time, $classroom,
            $prerequisites, $category_id, $is_active, $course_id
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Course updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update course']);
        }
        
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$db->closeConnection();
?>