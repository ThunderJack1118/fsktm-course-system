<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';


if (!isLoggedIn() || !isAdmin()) {
    redirect('/pages/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get course enrollment statistics
$enrollmentStats = [];
$stmt = $conn->prepare("SELECT c.course_id, c.course_code, c.course_name, 
                               COUNT(r.registration_id) as enrolled,
                               c.max_students,
                               (COUNT(r.registration_id) / c.max_students * 100) as percentage
                        FROM courses c
                        LEFT JOIN registrations r ON c.course_id = r.course_id AND r.status = 'approved'
                        GROUP BY c.course_id
                        ORDER BY percentage DESC");
$stmt->execute();
$enrollmentStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get registration status counts
$statusCounts = [];
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM registrations GROUP BY status");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $statusCounts[$row['status']] = $row['count'];
}
$stmt->close();

// Get user type counts
$userTypeCounts = [];
$stmt = $conn->prepare("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $userTypeCounts[$row['user_type']] = $row['count'];
}
$stmt->close();

$db->closeConnection();

$pageTitle = "Reports & Analytics";
include '../../includes/header.php';
?>

<div class="admin-container">
    <h1>Reports & Analytics</h1>
    
    <div class="report-section">
        <h2>Course Enrollment Statistics</h2>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Enrolled</th>
                        <th>Capacity</th>
                        <th>Percentage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrollmentStats as $course): ?>
                        <tr>
                            <td><?php echo $course['course_code']; ?></td>
                            <td><?php echo $course['course_name']; ?></td>
                            <td><?php echo $course['enrolled']; ?></td>
                            <td><?php echo $course['max_students']; ?></td>
                            <td><?php echo number_format($course['percentage'], 2); ?>%</td>
                            <td>
                                <?php if ($course['percentage'] >= 90): ?>
                                    <span class="status-badge danger">Full</span>
                                <?php elseif ($course['percentage'] >= 75): ?>
                                    <span class="status-badge warning">Almost Full</span>
                                <?php else: ?>
                                    <span class="status-badge success">Available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="report-charts">
        <div class="chart-container">
            <h3>Registration Status Distribution</h3>
            <canvas id="statusChart" width="400" height="300"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>User Type Distribution</h3>
            <canvas id="userTypeChart" width="400" height="300"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Registration Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($statusCounts)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($statusCounts)); ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    // User Type Chart
    const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
    const userTypeChart = new Chart(userTypeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($userTypeCounts)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($userTypeCounts)); ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>