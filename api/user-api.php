<?php
require_once __DIR__ . '/../config/config.php';  // One level up to root, then into config
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/pages/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $result = handleAddUser($conn);
                $_SESSION['success'] = $result['message'];
                break;
            case 'update':
                $result = handleUpdateUser($conn);
                $_SESSION['success'] = $result['message'];
                break;
            default:
                throw new Exception('Invalid action');
        }
        
        // Redirect to avoid form resubmission
        redirect($_SERVER['PHP_SELF']);
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        redirect($_SERVER['PHP_SELF']);
    }
}

// Handle status toggle and delete
if (isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    toggleUserStatus($conn, $userId);
}

if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    deleteUser($conn, $userId);
}

// User management functions
function handleAddUser($conn) {
    $required = ['username', 'email', 'first_name', 'last_name', 'user_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $data = [
        'username' => sanitizeInput($_POST['username']),
        'email' => sanitizeInput($_POST['email']),
        'first_name' => sanitizeInput($_POST['first_name']),
        'last_name' => sanitizeInput($_POST['last_name']),
        'user_type' => sanitizeInput($_POST['user_type']),
        'phone' => !empty($_POST['phone']) ? sanitizeInput($_POST['phone']) : null,
        'date_of_birth' => !empty($_POST['date_of_birth']) ? sanitizeInput($_POST['date_of_birth']) : null,
        'department' => !empty($_POST['department']) ? sanitizeInput($_POST['department']) : null,
        'address' => !empty($_POST['address']) ? sanitizeInput($_POST['address']) : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check for existing user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $data['username'], $data['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        throw new Exception('Username or email already exists');
    }
    $stmt->close();

    // Generate IDs and password
    if ($data['user_type'] === 'student') {
        $data['student_id'] = generateStudentId($conn);
        $data['staff_id'] = null;
    } else {
        $data['student_id'] = null;
        $data['staff_id'] = generateStaffId($conn, $data['user_type']);
    }

    $password = generateDefaultPassword($data['user_type']);
    $data['password'] = password_hash($password, PASSWORD_BCRYPT);
    $data['profile_picture'] = 'default-avatar.png';

    // Build and execute insert query
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $types = str_repeat('s', count($data));
    
    $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...array_values($data));
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Failed to add user: ' . $error);
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'message' => 'User added successfully',
        'data' => [
            'user_id' => $conn->insert_id,
            'generated_password' => $password
        ]
    ];
}

function handleUpdateUser($conn) {
    if (empty($_POST['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = (int)$_POST['user_id'];
    $required = ['username', 'email', 'first_name', 'last_name', 'user_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $data = [
        'username' => sanitizeInput($_POST['username']),
        'email' => sanitizeInput($_POST['email']),
        'first_name' => sanitizeInput($_POST['first_name']),
        'last_name' => sanitizeInput($_POST['last_name']),
        'user_type' => sanitizeInput($_POST['user_type']),
        'phone' => !empty($_POST['phone']) ? sanitizeInput($_POST['phone']) : null,
        'date_of_birth' => !empty($_POST['date_of_birth']) ? sanitizeInput($_POST['date_of_birth']) : null,
        'department' => !empty($_POST['department']) ? sanitizeInput($_POST['department']) : null,
        'address' => !empty($_POST['address']) ? sanitizeInput($_POST['address']) : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check for duplicates
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->bind_param("ssi", $data['username'], $data['email'], $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        throw new Exception('Username or email already exists');
    }
    $stmt->close();

    // Build update query
    $setClause = implode(', ', array_map(function($k) {
        return "$k = ?";
    }, array_keys($data)));
    
    $sql = "UPDATE users SET $setClause WHERE user_id = ?";
    $values = array_values($data);
    $values[] = $userId;
    $types = str_repeat('s', count($data)) . 'i';
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$values);
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Failed to update user: ' . $error);
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'message' => 'User updated successfully'
    ];
}

function toggleUserStatus($conn, $userId) {
    $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success'] = 'User status updated';
}

function deleteUser($conn, $userId) {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success'] = 'User deleted';
}

// Helper functions
function generateStudentId($conn) {
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)) as max_id FROM users WHERE student_id LIKE 'STU%'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $max_id = $result['max_id'] ? $result['max_id'] : 0;
    return 'STU' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);
}

function generateStaffId($conn, $userType) {
    $prefix = ($userType === 'lecturer') ? 'LEC' : 'ADM';
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(staff_id, 4) AS UNSIGNED)) as max_id FROM users WHERE staff_id LIKE ?");
    $likePrefix = $prefix . '%';
    $stmt->bind_param("s", $likePrefix);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $max_id = $result['max_id'] ? $result['max_id'] : 0;
    return $prefix . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);
}

function generateDefaultPassword($userType) {
    $prefix = ucfirst($userType);
    return $prefix . '@' . rand(1000, 9999);
}

function showAlertMessages() {
    if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif;
}