<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    header("Location: " . BASE_URL . "/pages/auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get stats for dashboard
$stats = [
    'total_courses' => 0,
    'total_users' => 0,
    'active_courses' => 0,
    'pending_registrations' => 0
];

// Get total courses
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_courses'] = $result->fetch_assoc()['count'];
$stmt->close();

// Get total users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_users'] = $result->fetch_assoc()['count'];
$stmt->close();

// Get active courses
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE is_active = 1");
$stmt->execute();
$result = $stmt->get_result();
$stats['active_courses'] = $result->fetch_assoc()['count'];
$stmt->close();

// Get pending registrations
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_registrations'] = $result->fetch_assoc()['count'];
$stmt->close();

// Get recent activities

$pageTitle = "Admin Dashboard";
include '../../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <div class="last-login">
            Last login: <?php echo date('Y-m-d H:i', strtotime($_SESSION['last_login'] ?? 'now')); ?>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_courses']; ?></h3>
                <p>Total Courses</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_users']; ?></h3>
                <p>Total Users</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['active_courses']; ?></h3>
                <p>Active Courses</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['pending_registrations']; ?></h3>
                <p>Pending Registrations</p>
            </div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="dashboard-section quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-buttons">
                <a href="<?php echo BASE_URL; ?>/pages/admin/manage-courses.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Course
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/admin/manage-users.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/admin/reports.php" class="btn btn-info">
                    <i class="fas fa-file-export"></i> Generate Reports
                </a>
            </div>
        </div>

        <div class="dashboard-section recent-activities">
            <h2><i class="fas fa-history"></i> Recent Activities</h2>
            <div class="activity-list">
                <?php if (empty($recent_activities)): ?>
                    <div class="no-activities">
                        <p>No recent activities found</p>
                    </div>
                <?php else: ?>
                    <ul>
                        <?php foreach ($recent_activities as $activity): ?>
                            <li>
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['action_type']); ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <p class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <p class="activity-meta">
                                        <span class="activity-user"><?php echo htmlspecialchars($activity['user_id']); ?></span>
                                        <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                                    </p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to get icons for different activity types
function getActivityIcon($type) {
    switch ($type) {
        case 'login': return 'sign-in-alt';
        case 'create': return 'plus-circle';
        case 'update': return 'edit';
        case 'delete': return 'trash';
        default: return 'info-circle';
    }
}

include '../../includes/footer.php';
?>