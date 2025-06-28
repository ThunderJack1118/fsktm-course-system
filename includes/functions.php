<?php
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === ROLE_ADMIN;
}

function isLecturer() {
    return isLoggedIn() && $_SESSION['user_type'] === ROLE_LECTURER;
}

function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === ROLE_STUDENT;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function displayError($error) {
    echo '<div class="alert alert-error">' . $error . '</div>';
}

function displaySuccess($message) {
    echo '<div class="alert alert-success">' . $message . '</div>';
}

function getCourseCategories() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM course_categories");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    $stmt->close();
    $db->closeConnection();
    
    return $categories;
}

function getCourses($limit = null, $category_id = null, $search = null, $exclude_registered = false) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT c.*, u.first_name, u.last_name, cc.category_name, cc.color_code 
              FROM courses c
              LEFT JOIN users u ON c.instructor_id = u.user_id
              LEFT JOIN course_categories cc ON c.category_id = cc.category_id";
    
    // Add WHERE clauses based on parameters
    $where = [];
    $params = [];
    $types = "";
    
    if ($category_id) {
        $where[] = "c.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    if ($search) {
        $where[] = "(c.course_name LIKE ? OR c.course_code LIKE ? OR c.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "sss";
    }
    
    // Exclude registered courses for students
    if ($exclude_registered && isStudent()) {
        $where[] = "c.course_id NOT IN (
            SELECT course_id FROM registrations 
            WHERE user_id = ? AND status IN ('approved', 'pending', 'completed')
        )";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    $query .= " ORDER BY c.semester, c.course_name";
    
    if ($limit) {
        $query .= " LIMIT ?";
        $params[] = $limit;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->closeConnection();
        return $courses;
    }
    
    $db->closeConnection();
    return [];
}
function getAvatarUrl($email, $size = 150) {
    if (!empty($email)) {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }
    return "https://www.gravatar.com/avatar/?s={$size}&d=mp"; // Default avatar
}

function handleFileUpload($file, $target_dir, $allowed_types, $max_size) {
    $errors = [];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error: " . $file['error'];
        return [false, $errors];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = "File is too large. Maximum size is " . ($max_size / 1024 / 1024) . "MB.";
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
    }
    
    if (!empty($errors)) {
        return [false, $errors];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $target_path = $target_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return [true, $new_filename];
    } else {
        $errors[] = "Failed to move uploaded file";
        return [false, $errors];
    }
}

function deleteFile($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

function getAssignmentStatus($due_date, $submission_date, $is_graded) {
    $now = time();
    $due = strtotime($due_date);
    $submitted = $submission_date ? strtotime($submission_date) : null;
    
    if ($is_graded) {
        return 'graded';
    } elseif ($submitted) {
        return $submitted > $due ? 'late' : 'submitted';
    } else {
        return $now > $due ? 'missed' : 'pending';
    }
}
/**
 * Sanitizes a filename by removing special characters
 * @param string $filename The original filename
 * @return string The sanitized filename
 */
function sanitizeFilename($filename) {
    // Remove any path information
    $filename = basename($filename);
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    // Remove any non-alphanumeric, dash, underscore, or dot characters
    $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
    // Remove any runs of periods
    $filename = preg_replace('/\.+/', '.', $filename);
    return $filename;
}

/**
 * Returns a human-readable time difference (e.g., "2 days ago")
 * @param int $from Timestamp
 * @param int $to Timestamp (defaults to now)
 * @return string Human-readable time difference
 */
function human_time_diff($from, $to = null) {
    if ($to === null) {
        $to = time();
    }
    
    $diff = abs($to - $from);
    
    if ($diff < 60) {
        return $diff . ' second' . ($diff !== 1 ? 's' : '');
    }
    
    $mins = round($diff / 60);
    if ($mins < 60) {
        return $mins . ' minute' . ($mins !== 1 ? 's' : '');
    }
    
    $hours = round($diff / 3600);
    if ($hours < 24) {
        return $hours . ' hour' . ($hours !== 1 ? 's' : '');
    }
    
    $days = round($diff / 86400);
    if ($days < 7) {
        return $days . ' day' . ($days !== 1 ? 's' : '');
    }
    
    $weeks = round($diff / 604800);
    if ($weeks < 4) {
        return $weeks . ' week' . ($weeks !== 1 ? 's' : '');
    }
    
    $months = round($diff / 2592000);
    if ($months < 12) {
        return $months . ' month' . ($months !== 1 ? 's' : '');
    }
    
    $years = round($diff / 31536000);
    return $years . ' year' . ($years !== 1 ? 's' : '');
}
function get_user_name($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->closeConnection();
    
    return $result ? $result['full_name'] : 'Unknown';
}

function displaySessionMessages() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['warning'])) {
        echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['warning']) . '</div>';
        unset($_SESSION['warning']);
    }
    
    if (isset($_SESSION['info'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info']) . '</div>';
        unset($_SESSION['info']);
    }
}

?>