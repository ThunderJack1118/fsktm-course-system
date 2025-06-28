<?php
// manage-courses.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/pages/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle course deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $course_id = (int)$_GET['delete'];
    
    try {
        // Check for registrations
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
                throw new Exception("Failed to delete course");
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    redirect('/pages/admin/manage-courses.php');
}

// Fetch data
try {
    // Get all courses with instructor and category info
    $query = "SELECT c.*, u.first_name, u.last_name, cc.category_name 
              FROM courses c 
              LEFT JOIN users u ON c.instructor_id = u.user_id
              LEFT JOIN course_categories cc ON c.category_id = cc.category_id
              ORDER BY c.course_name";
    $courses = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
    
    // Get all instructors
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_type = ?");
    $user_type = ROLE_LECTURER;
    $stmt->bind_param("s", $user_type);
    $stmt->execute();
    $instructors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get all categories
    $categories = getCourseCategories();
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to fetch data: " . $e->getMessage();
}

$db->closeConnection();

$pageTitle = "Manage Courses";
include '../../includes/header.php';
?>

<div class="admin-container">
    <h1>Manage Courses</h1>
    
    <?php displaySessionMessages(); ?>
    
    <div class="admin-actions">
        <button class="btn btn-primary" onclick="openModal('add-course-modal')">Add New Course</button>
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
                        <td><?= htmlspecialchars($course['course_code']) ?></td>
                        <td><?= htmlspecialchars($course['course_name']) ?></td>
                        <td><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></td>
                        <td><?= htmlspecialchars($course['category_name']) ?></td>
                        <td><?= htmlspecialchars($course['semester']) ?></td>
                        <td>
                            <span class="status-badge <?= $course['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $course['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="<?= BASE_URL ?>/pages/course-details.php?id=<?= $course['course_id'] ?>" class="btn btn-small btn-secondary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-small btn-primary" title="Edit" 
                               onclick="openEditModal(
                                   <?= $course['course_id'] ?>,
                                   '<?= htmlspecialchars($course['course_code'], ENT_QUOTES) ?>',
                                   '<?= htmlspecialchars($course['course_name'], ENT_QUOTES) ?>',
                                   '<?= htmlspecialchars($course['description'], ENT_QUOTES) ?>',
                                   <?= $course['credits'] ?>,
                                   '<?= $course['semester'] ?>',
                                   '<?= $course['academic_year'] ?>',
                                   <?= $course['max_students'] ?>,
                                   <?= $course['instructor_id'] ?? 'null' ?>,
                                   '<?= htmlspecialchars($course['schedule_day'], ENT_QUOTES) ?>',
                                   '<?= htmlspecialchars($course['schedule_time'], ENT_QUOTES) ?>',
                                   '<?= htmlspecialchars($course['classroom'], ENT_QUOTES) ?>',
                                   '<?= htmlspecialchars($course['prerequisites'], ENT_QUOTES) ?>',
                                   <?= $course['category_id'] ?? 'null' ?>,
                                   <?= $course['is_active'] ?>
                               )">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="<?= BASE_URL ?>/pages/admin/manage-courses.php?delete=<?= $course['course_id'] ?>" 
                               class="btn btn-small btn-danger" 
                               title="Delete" 
                               onclick="return confirm('Are you sure you want to delete this course?')">
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
            <button class="modal-close" onclick="closeModal('add-course-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="add-course-form" onsubmit="submitCourseForm(event, 'add')">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_code">Course Code *</label>
                        <input type="text" id="course_code" name="course_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_name">Course Name *</label>
                        <input type="text" id="course_name" name="course_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="credits">Credits *</label>
                        <input type="number" id="credits" name="credits" min="1" max="10" value="3" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semester">Semester *</label>
                        <select id="semester" name="semester" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year *</label>
                        <input type="text" id="academic_year" name="academic_year" placeholder="YYYY/YYYY" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_students">Max Students *</label>
                        <input type="number" id="max_students" name="max_students" min="1" value="40" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="instructor_id">Instructor</label>
                        <select id="instructor_id" name="instructor_id">
                            <option value="">-- Select Instructor --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?= $instructor['user_id'] ?>">
                                    <?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>">
                                    <?= htmlspecialchars($category['category_name']) ?>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('add-course-modal')">Cancel</button>
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
            <button class="modal-close" onclick="closeModal('edit-course-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-course-form" onsubmit="submitCourseForm(event, 'update')">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_course_id" name="course_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_course_code">Course Code *</label>
                        <input type="text" id="edit_course_code" name="course_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_course_name">Course Name *</label>
                        <input type="text" id="edit_course_name" name="course_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_credits">Credits *</label>
                        <input type="number" id="edit_credits" name="credits" min="1" max="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_semester">Semester *</label>
                        <select id="edit_semester" name="semester" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_academic_year">Academic Year *</label>
                        <input type="text" id="edit_academic_year" name="academic_year" placeholder="YYYY/YYYY" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_max_students">Max Students *</label>
                        <input type="number" id="edit_max_students" name="max_students" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_instructor_id">Instructor</label>
                        <select id="edit_instructor_id" name="instructor_id">
                            <option value="">-- Select Instructor --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?= $instructor['user_id'] ?>">
                                    <?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_category_id">Category</label>
                        <select id="edit_category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>">
                                    <?= htmlspecialchars($category['category_name']) ?>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('edit-course-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Edit modal population
function openEditModal(
    courseId, courseCode, courseName, description, credits, semester, 
    academicYear, maxStudents, instructorId, scheduleDay, scheduleTime, 
    classroom, prerequisites, categoryId, isActive
) {
    document.getElementById('edit_course_id').value = courseId;
    document.getElementById('edit_course_code').value = courseCode;
    document.getElementById('edit_course_name').value = courseName;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_credits').value = credits;
    document.getElementById('edit_semester').value = semester;
    document.getElementById('edit_academic_year').value = academicYear;
    document.getElementById('edit_max_students').value = maxStudents;
    document.getElementById('edit_instructor_id').value = instructorId;
    document.getElementById('edit_schedule_day').value = scheduleDay;
    document.getElementById('edit_schedule_time').value = scheduleTime;
    document.getElementById('edit_classroom').value = classroom;
    document.getElementById('edit_prerequisites').value = prerequisites;
    document.getElementById('edit_category_id').value = categoryId;
    document.getElementById('edit_is_active').checked = isActive === 1;
    
    openModal('edit-course-modal');
}

// Form submission
function submitCourseForm(event, action) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('<?= BASE_URL ?>/api/course-api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
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

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>