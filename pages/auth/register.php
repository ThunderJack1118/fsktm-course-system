<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (isLoggedIn()) {
    redirect('/pages/profile.php');
}

// Function to validate date format (YYYY-MM-DD)
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Required fields
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    
    // Optional fields
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : null;
    $date_of_birth = isset($_POST['date_of_birth']) ? sanitizeInput($_POST['date_of_birth']) : null;
    $student_id = isset($_POST['student_id']) ? sanitizeInput($_POST['student_id']) : null;
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : null;
    $department = isset($_POST['department']) ? sanitizeInput($_POST['department']) : null;
    
    $user_type = ROLE_STUDENT; // Default to student
    
    // Validation
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";

    
    // Additional validations
    if (!empty($student_id)) {
        if (!preg_match('/^[A-Za-z0-9]+$/', $student_id)) {
            $errors[] = "Student ID must be alphanumeric";
        }
        if (!preg_match('/^STU\d{3}$/i', $student_id)) {
            $errors[] = "Student ID must be in format STU followed by 3 digits (e.g., STU001)";
        }
    }
    
    if (!empty($phone) && !preg_match('/^\+?[0-9\s\-]{10,}$/', $phone)) {
        $errors[] = "Phone number format is invalid";
    }
    
    if (empty($date_of_birth)) {
        $errors[] = "Date of birth is required";
    } else {
        if (!validateDate($date_of_birth)) {
            $errors[] = "Date of birth format is invalid (YYYY-MM-DD)";
        } else {
            $dob = new DateTime($date_of_birth);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
            if ($age < 18) {
                $errors[] = "You must be at least 18 years old to register";
            }
        }
    }
    
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Generate student ID if not provided
        if (empty($student_id)) {
            // Get the highest existing student ID number
            $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)) as max_id FROM users WHERE student_id LIKE 'STU%'");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $max_id = $row['max_id'] ? $row['max_id'] : 0;
            $next_id = $max_id + 1;
            $student_id = 'STU' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
            $stmt->close();
        }
        
        // Check if username, email, or student_id exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? OR student_id = ?");
        $stmt->bind_param("sss", $username, $email, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username, email, or student ID already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $profile_picture = 'default-avatar.png'; // Default profile picture
            
            $insert_stmt = $conn->prepare("INSERT INTO users 
                (username, email, password, first_name, last_name, user_type, 
                profile_picture, phone, date_of_birth, student_id, address, department) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $insert_stmt->bind_param("ssssssssssss", 
                $username, $email, $hashed_password, $first_name, $last_name, $user_type,
                $profile_picture, $phone, $date_of_birth, $student_id, $address, $department);
            
            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                // Clear form values
                $_POST = array();
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            
            $insert_stmt->close();
        }
        
        $stmt->close();
        $db->closeConnection();
    }
}

$pageTitle = "Register";
include '../../includes/header.php';
?>

<div class="auth-container">
    <h1>Create an Account</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name*</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name*</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username*</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password* (min 8 characters)</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password*</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id" name="student_id" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" placeholder="Leave blank to auto-generate">
                <small class="form-text">If left blank, will be auto-generated (e.g., STU001)</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth* (Must be 18+)</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="department">Department*</label>
                <select id="department" name="department" class="form-control" required>
                    <option value="">Select Department</option>
                    <option value="Information Technology" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Software Engineering" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Software Engineering') ? 'selected' : ''; ?>>Software Engineering</option>
                    <option value="Information Security" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Information Security') ? 'selected' : ''; ?>>Information Security</option>
                    <option value="Multimedia Computing" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Multimedia Computing') ? 'selected' : ''; ?>>Multimedia Computing</option>
                    <option value="Web Technology" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Web Technology') ? 'selected' : ''; ?>>Web Technology</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3" class="form-control"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        
        <div class="auth-links">
            <p>Already have an account? <a href="<?php echo BASE_URL; ?>/pages/auth/login.php">Login here</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>