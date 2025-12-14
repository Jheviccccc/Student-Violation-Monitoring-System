<?php 
$user = $_SESSION['user'] ?? null; 
$unreadNotifications = 0;
if ($user) {
    require_once __DIR__ . '/notification_service.php';
    $notificationService = new NotificationService();
    $unreadNotifications = $notificationService->getUnreadCount($user['id']);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
	<div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?php echo ($user && $user['role']==='student') ? 'student.php' : (($user && $user['role']==='viewer') ? 'viewer.php' : 'dashboard.php'); ?>">
            <i class="bi bi-shield-check"></i> SVMS
        </a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample" aria-controls="navbarsExample" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarsExample">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($user && $user['role'] === 'student'): ?>
                    <li class="nav-item"><a class="nav-link" href="student.php"><i class="bi bi-person-circle"></i> My Violations</a></li>
                <?php elseif ($user && $user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="students.php"><i class="bi bi-people"></i> Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="violations.php"><i class="bi bi-exclamation-triangle"></i> Violations</a></li>
                    <li class="nav-item"><a class="nav-link" href="records.php"><i class="bi bi-file-earmark-text"></i> Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="stats.php"><i class="bi bi-bar-chart"></i> Statistics</a></li>
                    <li class="nav-item"><a class="nav-link" href="email_settings.php"><i class="bi bi-envelope-gear"></i> Email Settings</a></li>
                <?php elseif ($user && $user['role'] === 'viewer'): ?>
                    <li class="nav-item"><a class="nav-link" href="viewer.php"><i class="bi bi-eye"></i> View Records</a></li>
                <?php endif; ?>
            </ul>
			<div class="d-flex align-items-center text-white">
				<?php if ($user): ?>
                    <!-- Notifications Dropdown -->
                    <div class="dropdown me-3">
                        <a class="btn btn-link text-white text-decoration-none position-relative" href="#" role="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-badge">
                                    <?php echo $unreadNotifications > 9 ? '9+' : $unreadNotifications; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 380px; max-height: 500px; overflow-y: auto;" aria-labelledby="notificationDropdown" id="notification-dropdown">
                            <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-bell"></i> Notifications</span>
                                <?php if ($unreadNotifications > 0): ?>
                                    <span class="badge bg-danger"><?php echo $unreadNotifications; ?> new</span>
                                <?php endif; ?>
                            </h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <div id="notification-list">
                                <li>
                                    <div class="dropdown-item text-center text-muted py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </li>
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center fw-semibold" href="notifications.php">
                                <i class="bi bi-arrow-right-circle"></i> View All Notifications
                            </a></li>
                        </ul>
                    </div>
                    <span class="me-3 small d-none d-md-block user-display">
                        <i class="bi bi-person-circle"></i> <?php echo h($user['full_name']); ?> 
                        <span class="badge bg-light text-dark"><?php echo h($user['role']); ?></span>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm logout-btn">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</nav>
