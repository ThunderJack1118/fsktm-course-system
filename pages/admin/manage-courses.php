<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';


if (!isLoggedIn() || !isAdmin()) {
    redirect('/pages/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle course deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = (int)$_GET['delete'];
    
    // Check if there are any registrations for this course
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['count'] > 0) {
        $_SESSION['error'] = "Cannot delete course with active registrations. Please deactivate it instead.";
    } else {
        $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete course";
        }
        
        $stmt->close();
    }
    
    redirect('/pages/admin/manage-courses.php');
}

// Get all courses with instructor and category info
$query = "SELECT c.*, u.first_name, u.last_name, cc.category_name 
          FROM courses c 
          LEFT JOIN users u ON c.instructor_id = u.user_id
          LEFT JOIN course_categories cc ON c.category_id = cc.category_id
          ORDER BY c.course_name";

$courses = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get all instructors (lecturers)
$instructors = [];
$stmt = $conn->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_type = ?");
$user_type = ROLE_LECTURER;
$stmt->bind_param("s", $user_type);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $instructors[] = $row;
}
$stmt->close();

// Get all categories
$categories = getCourseCategories();

$db->closeConnection();

$pageTitle = "Manage Courses";
include '../../includes/header.php';
?>

<div class="admin-container">
    <h1>Manage Courses</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="admin-actions">
        <a href="#add-course-modal" class="btn btn-primary" data-modal-open>Add New Course</a>
    </div>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Instructor</th>
                    <th>Category</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo $course['course_code']; ?></td>
                        <td><?php echo $course['course_name']; ?></td>
                        <td><?php echo $course['first_name'] . ' ' . $course['last_name']; ?></td>
                        <td><?php echo $course['category_name']; ?></td>
                        <td><?php echo $course['semester']; ?></td>
                        <td>
                            <span class="status-badge <?php echo $course['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="<?php echo BASE_URL; ?>/pages/course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-small btn-secondary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#edit-course-modal" class="btn btn-small btn-primary" title="Edit" 
                               data-modal-open 
                               data-course-id="<?php echo $course['course_id']; ?>"
                               data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                               data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                               data-description="<?php echo htmlspecialchars($course['description']); ?>"
                               data-credits="<?php echo $course['credits']; ?>"
                               data-semester="<?php echo $course['semester']; ?>"
                               data-academic-year="<?php echo $course['academic_year']; ?>"
                               data-max-students="<?php echo $course['max_students']; ?>"
                               data-instructor-id="<?php echo $course['instructor_id']; ?>"
                               data-schedule-day="<?php echo htmlspecialchars($course['schedule_day']); ?>"
                               data-schedule-time="<?php echo htmlspecialchars($course['schedule_time']); ?>"
                               data-classroom="<?php echo htmlspecialchars($course['classroom']); ?>"
                               data-prerequisites="<?php echo htmlspecialchars($course['prerequisites']); ?>"
                               data-category-id="<?php echo $course['category_id']; ?>"
                               data-is-active="<?php echo $course['is_active']; ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/admin/manage-courses.php?delete=<?php echo $course['course_id']; ?>" class="btn btn-small btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this course?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Course Modal -->
<div id="add-course-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Course</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="add-course-form" action="<?php echo BASE_URL; ?>/api/course-api.php" method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_code">Course Code</label>
                        <input type="text" id="course_code" name="course_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_name">Course Name</label>
                        <input type="text" id="course_name" name="course_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="credits">Credits</label>
                        <input type="number" id="credits" name="credits" min="1" max="10" value="3" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <input type="text" id="academic_year" name="academic_year" placeholder="YYYY/YYYY" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" min="1" value="40" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="instructor_id">Instructor</label>
                        <select id="instructor_id" name="instructor_id">
                            <option value="">-- Select Instructor --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor['user_id']; ?>">
                                    <?php echo $instructor['first_name'] . ' ' . $instructor['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo $category['category_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="schedule_day">Schedule Day</label>
                        <input type="text" id="schedule_day" name="schedule_day" placeholder="e.g., Monday">
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule_time">Schedule Time</label>
                        <input type="text" id="schedule_time" name="schedule_time" placeholder="e.g., 9:00 AM - 11:00 AM">
                    </div>
                    
                    <div class="form-group">
                        <label for="classroom">Classroom</label>
                        <input type="text" id="classroom" name="classroom" placeholder="e.g., FSKTM Lab 1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="prerequisites">Prerequisites</label>
                    <textarea id="prerequisites" name="prerequisites" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" checked>
                        Active Course
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="edit-course-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Course</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-course-form" action="<?php echo BASE_URL; ?>/api/course-api.php" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_course_id" name="course_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_course_code">Course Code</label>
                        <input type="text" id="edit_course_code" name="course_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_course_name">Course Name</label>
                        <input type="text" id="edit_course_name" name="course_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_credits">Credits</label>
                        <input type="number" id="edit_credits" name="credits" min="1" max="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_semester">Semester</label>
                        <select id="edit_semester" name="semester" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_academic_year">Academic Year</label>
                        <input type="text" id="edit_academic_year" name="academic_year" placeholder="YYYY/YYYY" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_max_students">Max Students</label>
                        <input type="number" id="edit_max_students" name="max_students" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_instructor_id">Instructor</label>
                        <select id="edit_instructor_id" name="instructor_id">
                            <option value="">-- Select Instructor --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor['user_id']; ?>">
                                    <?php echo $instructor['first_name'] . ' ' . $instructor['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_category_id">Category</label>
                        <select id="edit_category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo $category['category_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_schedule_day">Schedule Day</label>
                        <input type="text" id="edit_schedule_day" name="schedule_day" placeholder="e.g., Monday">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_schedule_time">Schedule Time</label>
                        <input type="text" id="edit_schedule_time" name="schedule_time" placeholder="e.g., 9:00 AM - 11:00 AM">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_classroom">Classroom</label>
                        <input type="text" id="edit_classroom" name="classroom" placeholder="e.g., FSKTM Lab 1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_prerequisites">Prerequisites</label>
                    <textarea id="edit_prerequisites" name="prerequisites" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_active" name="is_active">
                        Active Course
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
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
                if (modalId === '#edit-course-modal') {
                    document.getElementById('edit_course_id').value = this.dataset.courseId;
                    document.getElementById('edit_course_code').value = this.dataset.courseCode;
                    document.getElementById('edit_course_name').value = this.dataset.courseName;
                    document.getElementById('edit_description').value = this.dataset.description;
                    document.getElementById('edit_credits').value = this.dataset.credits;
                    document.getElementById('edit_semester').value = this.dataset.semester;
                    document.getElementById('edit_academic_year').value = this.dataset.academicYear;
                    document.getElementById('edit_max_students').value = this.dataset.maxStudents;
                    document.getElementById('edit_instructor_id').value = this.dataset.instructorId;
                    document.getElementById('edit_schedule_day').value = this.dataset.scheduleDay;
                    document.getElementById('edit_schedule_time').value = this.dataset.scheduleTime;
                    document.getElementById('edit_classroom').value = this.dataset.classroom;
                    document.getElementById('edit_prerequisites').value = this.dataset.prerequisites;
                    document.getElementById('edit_category_id').value = this.dataset.categoryId;
                    document.getElementById('edit_is_active').checked = this.dataset.isActive === '1';
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
    
    // Form submission handling
    const addCourseForm = document.getElementById('add-course-form');
    const editCourseForm = document.getElementById('edit-course-form');
    
    if (addCourseForm) {
        addCourseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCourseForm(this, 'add');
        });
    }
    
    if (editCourseForm) {
        editCourseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCourseForm(this, 'update');
        });
    }
    
    function submitCourseForm(form, action) {
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