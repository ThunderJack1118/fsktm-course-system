<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../api/user-api.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/pages/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle all CRUD operations
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
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

if (isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    toggleUserStatus($conn, $userId);
}

if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    deleteUser($conn, $userId);
}

// Get all users for display
$users = $conn->query("
    SELECT user_id, username, email, first_name, last_name, user_type, 
           student_id, staff_id, is_active, created_at, phone, address, department
    FROM users ORDER BY user_type, first_name, last_name
")->fetch_all(MYSQLI_ASSOC);

$db->closeConnection();

$pageTitle = "Manage Users";
include '../../includes/header.php';
?>

<div class="admin-container">
    <h1>Manage Users</h1>
    
    <?php showAlertMessages(); ?>
    
    <div class="mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" data-action="add">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <a href="profile.php?id=<?= $user['user_id'] ?>" class="text-primary">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= ucfirst($user['user_type']) ?></td>
                                <td><?= htmlspecialchars($user['student_id'] ?? $user['staff_id'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['department'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../profile.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-info" 
                                        title="View Profile" data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-warning edit-user" 
                                            data-bs-toggle="modal" data-bs-target="#userModal"
                                            data-user-id="<?= $user['user_id'] ?>"
                                            data-user-data='<?= htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') ?>'
                                            data-action="edit"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?toggle_status=<?= $user['user_id'] ?>" class="btn btn-sm btn-<?= $user['is_active'] ? 'secondary' : 'success' ?>"
                                            title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas <?= $user['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                        </a>
                                        <a href="?delete=<?= $user['user_id'] ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure? This cannot be undone.')"
                                            title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Unified User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modalTitle">Add</span> User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="user_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name*</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required
                                   pattern="[A-Za-z\s]{2,}" title="Only letters and spaces, minimum 2 characters">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name*</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required
                                   pattern="[A-Za-z\s]{2,}" title="Only letters and spaces, minimum 2 characters">
                        </div>
                        <div class="col-md-6">
                            <label for="user_type" class="form-label">User Type*</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="">Select Type</option>
                                <option value="student">Student</option>
                                <option value="lecturer">Lecturer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username*</label>
                            <input type="text" class="form-control" id="username" name="username" required
                                   pattern="[A-Za-z0-9_]{4,}" title="Letters, numbers, underscores only (4+ chars)">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email*</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   pattern="[\d\-]{10,15}" title="10-15 digit phone number">
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">Select Department</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Software Engineering">Software Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Information Security">Information Security</option>
                                <option value="Web Technology">Web Technology</option>
                                <option value="Multimedia Computing">Multimedia Computing</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="1"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active Account</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable with sorting
    $('#usersTable').DataTable({
        responsive: true,
        columnDefs: [
            { orderable: true, targets: [0, 1, 2, 3, 4, 5, 6, 7] },
            { orderable: false, targets: [8] } // Actions column
        ],
        order: [[0, 'asc']] // Default sort by Name
    });

    // Handle modal for both add and edit
    $(document).on('click', '.edit-user', function() {
        const button = $(this);
        const action = button.data('action');
        const userData = button.data('user-data');
        const modal = $('#userModal');
        
        modal.find('#modalTitle').text(action === 'add' ? 'Add' : 'Edit');
        modal.find('#formAction').val(action === 'add' ? 'add' : 'update');
        
        if (action === 'edit') {
            try {
                const user = typeof userData === 'string' ? JSON.parse(userData) : userData;
                modal.find('#user_id').val(user.user_id);
                modal.find('#first_name').val(user.first_name);
                modal.find('#last_name').val(user.last_name);
                modal.find('#username').val(user.username);
                modal.find('#email').val(user.email);
                modal.find('#user_type').val(user.user_type);
                modal.find('#phone').val(user.phone || '');
                modal.find('#department').val(user.department || '');
                modal.find('#address').val(user.address || '');
                modal.find('#is_active').prop('checked', user.is_active == 1);
            } catch (e) {
                console.error('Error parsing user data:', e);
                alert('Error loading user data');
                return;
            }
        } else {
            modal.find('form')[0].reset();
            modal.find('#user_id').val('');
        }
    });

    // Form submission
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function() {
                window.location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + xhr.responseText);
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<?php
include '../../includes/footer.php';
?>

<style>
/* Improved Table Styling */
#usersTable {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 0.9em;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

#usersTable thead tr {
    background-color: #343a40;
    color: #ffffff;
    text-align: left;
}

#usersTable th,
#usersTable td {
    padding: 12px 15px;
    border-bottom: 1px solid #dddddd;
    vertical-align: middle;
}

#usersTable tbody tr {
    border-bottom: 1px solid #dddddd;
}

#usersTable tbody tr:nth-of-type(even) {
    background-color: #f8f9fa;
}

#usersTable tbody tr:last-of-type {
    border-bottom: 2px solid #343a40;
}

#usersTable tbody tr:hover {
    background-color: #e9f7fe;
}

/* Button Group Styling */
.btn-group {
    display: flex;
    flex-wrap: nowrap;
    gap: 5px;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 4px;
}

/* Button Colors */
.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #117a8b;
    color: white;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #212529;
}

.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
    color: white;
}

/* Improved Modal Styling */
.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.modal-header {
    background-color: #343a40;
    color: white;
    border-radius: 10px 10px 0 0;
    padding: 15px 20px;
}

.modal-header .btn-close {
    filter: invert(1);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 15px 20px;
}

/* Form Styling */
.form-label {
    font-weight: 500;
    margin-bottom: 5px;
}

.form-control, .form-select {
    border-radius: 5px;
    padding: 10px;
    border: 1px solid #ced4da;
    margin-bottom: 15px;
}

.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Button Styling */
.btn {
    border-radius: 5px;
    padding: 8px 15px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}

.btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}

.btn-outline-danger:hover {
    color: white;
}

/* Alert Styling */
.alert {
    border-radius: 5px;
    padding: 10px 15px;
    margin-bottom: 20px;
}

/* Badge Styling */
.badge {
    padding: 5px 10px;
    font-size: 0.8em;
    font-weight: 600;
    border-radius: 4px;
}

.badge.bg-success {
    background-color: #28a745 !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    #usersTable {
        display: block;
        overflow-x: auto;
    }
    
    .modal-dialog {
        margin: 0.5rem auto;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>