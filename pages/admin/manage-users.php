<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';


if (!isLoggedIn() || !isAdmin()) {
    redirect('/pages/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Check if user is trying to delete themselves
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account";
    } else {
        // Check if user has any courses as instructor
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE instructor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Cannot delete user who is assigned as instructor to courses";
        } else {
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "User deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete user";
            }
            
            $stmt->close();
        }
    }
    
    redirect('/pages/admin/manage-users.php');
}

// Handle user status change
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    
    // Check if user is trying to deactivate themselves
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot deactivate your own account";
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User status updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update user status";
        }
        
        $stmt->close();
    }
    
    redirect('/pages/admin/manage-users.php');
}

// Get all users
$query = "SELECT user_id, username, email, first_name, last_name, user_type, is_active, 
                 student_id, staff_id, created_at 
          FROM users 
          ORDER BY user_type, first_name, last_name";

$users = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

$db->closeConnection();

$pageTitle = "Manage Users";
include '../../includes/header.php';
?>

<div class="admin-container">
    <h1>Manage Users</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="admin-actions">
        <a href="#add-user-modal" class="btn btn-primary" data-modal-open>Add New User</a>
    </div>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Student/Staff ID</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo ucfirst($user['user_type']); ?></td>
                        <td>
                            <?php if (!empty($user['student_id'])): ?>
                                <?php echo $user['student_id']; ?>
                            <?php elseif (!empty($user['staff_id'])): ?>
                                <?php echo $user['staff_id']; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="<?php echo BASE_URL; ?>/pages/profile.php?id=<?php echo $user['user_id']; ?>" class="btn btn-small btn-secondary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#edit-user-modal" class="btn btn-small btn-primary" title="Edit" 
                               data-modal-open 
                               data-user-id="<?php echo $user['user_id']; ?>"
                               data-username="<?php echo htmlspecialchars($user['username']); ?>"
                               data-email="<?php echo htmlspecialchars($user['email']); ?>"
                               data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                               data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                               data-user-type="<?php echo $user['user_type']; ?>"
                               data-student-id="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>"
                               data-staff-id="<?php echo htmlspecialchars($user['staff_id'] ?? ''); ?>"
                               data-is-active="<?php echo $user['is_active']; ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/admin/manage-users.php?toggle_status=<?php echo $user['user_id']; ?>" class="btn btn-small btn-warning" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas <?php echo $user['is_active'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/admin/manage-users.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-small btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New User</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="add-user-form" action="<?php echo BASE_URL; ?>/api/user-api.php" method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select id="user_type" name="user_type" required>
                            <option value="student">Student</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="student-id-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id">
                    </div>
                    
                    <div class="form-group" id="staff-id-group" style="display: none;">
                        <label for="staff_id">Staff ID</label>
                        <input type="text" id="staff_id" name="staff_id">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" checked>
                        Active Account
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-user-form" action="<?php echo BASE_URL; ?>/api/user-api.php" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_first_name">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_user_type">User Type</label>
                        <select id="edit_user_type" name="user_type" required>
                            <option value="student">Student</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="edit-student-id-group">
                        <label for="edit_student_id">Student ID</label>
                        <input type="text" id="edit_student_id" name="student_id">
                    </div>
                    
                    <div class="form-group" id="edit-staff-id-group" style="display: none;">
                        <label for="edit_staff_id">Staff ID</label>
                        <input type="text" id="edit_staff_id" name="staff_id">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_active" name="is_active">
                        Active Account
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const modalOpenButtons = document.querySelectorAll('[data-modal-open]');
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    
    modalOpenButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('href') || this.dataset.modalOpen;
            const modal = document.querySelector(modalId);
            
            if (modal) {
                modal.style.display = 'block';
                
                // If this is the edit modal, populate with data
                if (modalId === '#edit-user-modal') {
                    document.getElementById('edit_user_id').value = this.dataset.userId;
                    document.getElementById('edit_username').value = this.dataset.username;
                    document.getElementById('edit_email').value = this.dataset.email;
                    document.getElementById('edit_first_name').value = this.dataset.firstName;
                    document.getElementById('edit_last_name').value = this.dataset.lastName;
                    document.getElementById('edit_user_type').value = this.dataset.userType;
                    document.getElementById('edit_is_active').checked = this.dataset.isActive === '1';
                    
                    // Handle student/staff ID fields based on user type
                    const userType = this.dataset.userType;
                    const studentIdGroup = document.getElementById('edit-student-id-group');
                    const staffIdGroup = document.getElementById('edit-staff-id-group');
                    
                    if (userType === 'student') {
                        studentIdGroup.style.display = 'block';
                        staffIdGroup.style.display = 'none';
                        document.getElementById('edit_student_id').value = this.dataset.studentId;
                    } else if (userType === 'lecturer' || userType === 'admin') {
                        studentIdGroup.style.display = 'none';
                        staffIdGroup.style.display = 'block';
                        document.getElementById('edit_staff_id').value = this.dataset.staffId;
                    }
                }
            }
        });
    });
    
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
    
    // Show/hide student/staff ID fields based on user type in add form
    const userTypeSelect = document.getElementById('user_type');
    const studentIdGroup = document.getElementById('student-id-group');
    const staffIdGroup = document.getElementById('staff-id-group');
    
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            if (this.value === 'student') {
                studentIdGroup.style.display = 'block';
                staffIdGroup.style.display = 'none';
            } else {
                studentIdGroup.style.display = 'none';
                staffIdGroup.style.display = 'block';
            }
        });
    }
    
    // Show/hide student/staff ID fields based on user type in edit form
    const editUserTypeSelect = document.getElementById('edit_user_type');
    const editStudentIdGroup = document.getElementById('edit-student-id-group');
    const editStaffIdGroup = document.getElementById('edit-staff-id-group');
    
    if (editUserTypeSelect) {
        editUserTypeSelect.addEventListener('change', function() {
            if (this.value === 'student') {
                editStudentIdGroup.style.display = 'block';
                editStaffIdGroup.style.display = 'none';
            } else {
                editStudentIdGroup.style.display = 'none';
                editStaffIdGroup.style.display = 'block';
            }
        });
    }
    
    // Form submission handling
    const addUserForm = document.getElementById('add-user-form');
    const editUserForm = document.getElementById('edit-user-form');
    
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitUserForm(this, 'add');
        });
    }
    
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitUserForm(this, 'update');
        });
    }
    
    function submitUserForm(form, action) {
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>