<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notification_service.php';
require_login();

$user = $_SESSION['user'];
$notificationService = new NotificationService();

// Mark as read
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    $notificationService->markAsRead($id, $user['id']);
    header('Location: notifications.php');
    exit;
}

// Mark all as read
if (isset($_GET['read_all'])) {
    $notificationService->markAllAsRead($user['id']);
    header('Location: notifications.php');
    exit;
}

// Delete notification
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $notificationService->delete($id, $user['id']);
    header('Location: notifications.php');
    exit;
}

$notifications = $notificationService->getNotifications($user['id'], 50);
$unreadCount = $notificationService->getUnreadCount($user['id']);
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="bi bi-bell-fill text-primary"></i> Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?> new</span>
            <?php endif; ?>
        </h4>
        <?php if ($unreadCount > 0): ?>
            <a href="?read_all=1" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-check-all"></i> Mark all as read
            </a>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <div class="card-body p-0">
            <?php if ($notifications->num_rows === 0): ?>
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash display-1 text-muted"></i>
                    <p class="text-muted mt-3">No notifications yet</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <div class="list-group-item <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge bg-<?php 
                                            echo $notif['type'] === 'danger' ? 'danger' : 
                                                ($notif['type'] === 'warning' ? 'warning' : 
                                                ($notif['type'] === 'success' ? 'success' : 'info')); 
                                        ?> me-2">
                                            <?php echo ucfirst($notif['type']); ?>
                                        </span>
                                        <h6 class="mb-0 <?php echo $notif['is_read'] ? '' : 'fw-bold'; ?>">
                                            <?php echo h($notif['title']); ?>
                                        </h6>
                                    </div>
                                    <p class="mb-1 text-muted"><?php echo h($notif['message']); ?></p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="ms-3">
                                    <div class="btn-group-vertical btn-group-sm">
                                        <?php if (!$notif['is_read']): ?>
                                            <a href="?read=<?php echo $notif['id']; ?>" class="btn btn-outline-primary btn-sm" title="Mark as read">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($notif['link']): ?>
                                            <a href="<?php echo h($notif['link']); ?>" class="btn btn-outline-info btn-sm" title="View">
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $notif['id']; ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Delete this notification?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>

