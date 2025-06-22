<?php
require_once '../config/config.php';

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
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = sanitizeInput($_POST['password']);
        $confirm_password = sanitizeInput($_POST['confirm_password']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $user_type = sanitizeInput($_POST['user_type']);
        $student_id = ($user_type === 'student') ? sanitizeInput($_POST['student_id'] ?? '') : null;
        $staff_id = ($user_type !== 'student') ? sanitizeInput($_POST['staff_id'] ?? '') : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username is required']);
            exit;
        }
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Password is required']);
            exit;
        }
        
        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }
        
        if (empty($first_name)) {
            echo json_encode(['success' => false, 'message' => 'First name is required']);
            exit;
        }
        
        if (empty($last_name)) {
            echo json_encode(['success' => false, 'message' => 'Last name is required']);
            exit;
        }
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        $stmt->close();
        
        // Check if student_id or staff_id already exists if provided
        if ($user_type === 'student' && !empty($student_id)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE student_id = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                echo json_encode(['success' => false, 'message' => 'Student ID already exists']);
                exit;
            }
            
            $stmt->close();
        } elseif (($user_type === 'lecturer' || $user_type === 'admin') && !empty($staff_id)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE staff_id = ?");
            $stmt->bind_param("s", $staff_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                echo json_encode(['success' => false, 'message' => 'Staff ID already exists']);
                exit;
            }
            
            $stmt->close();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (
            username, email, password, first_name, last_name, user_type,
            student_id, staff_id, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssssssi", 
            $username, $email, $hashed_password, $first_name, $last_name, $user_type,
            $student_id, $staff_id, $is_active
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add user']);
        }
        
        $stmt->close();
        break;
        
    case 'update':
        $user_id = (int)$_POST['user_id'];
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $user_type = sanitizeInput($_POST['user_type']);
        $student_id = ($user_type === 'student') ? sanitizeInput($_POST['student_id'] ?? '') : null;
        $staff_id = ($user_type !== 'student') ? sanitizeInput($_POST['staff_id'] ?? '') : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username is required']);
            exit;
        }
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        if (empty($first_name)) {
            echo json_encode(['success' => false, 'message' => 'First name is required']);
            exit;
        }
        
        if (empty($last_name)) {
            echo json_encode(['success' => false, 'message' => 'Last name is required']);
            exit;
        }
        
        // Check if username or email already exists for another user
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Username or email already exists for another user']);
            exit;
        }
        
        $stmt->close();
        
        // Check if student_id or staff_id already exists for another user if provided
        if ($user_type === 'student' && !empty($student_id)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE student_id = ? AND user_id != ?");
            $stmt->bind_param("si", $student_id, $user_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                echo json_encode(['success' => false, 'message' => 'Student ID already exists for another user']);
                exit;
            }
            
            $stmt->close();
        } elseif (($user_type === 'lecturer' || $user_type === 'admin') && !empty($staff_id)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE staff_id = ? AND user_id != ?");
            $stmt->bind_param("si", $staff_id, $user_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                echo json_encode(['success' => false, 'message' => 'Staff ID already exists for another user']);
                exit;
            }
            
            $stmt->close();
        }
        
        // Update user
        $stmt = $conn->prepare("UPDATE users SET 
            username = ?, email = ?, first_name = ?, last_name = ?, user_type = ?,
            student_id = ?, staff_id = ?, is_active = ?
            WHERE user_id = ?");
        
        $stmt->bind_param("sssssssii", 
            $username, $email, $first_name, $last_name, $user_type,
            $student_id, $staff_id, $is_active, $user_id
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
        
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$db->closeConnection();
?>