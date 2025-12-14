<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notification_service.php';

require_login();
$user = $_SESSION['user'];
$notificationService = new NotificationService();

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $limit = (int)($_GET['limit'] ?? 10);
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';
    $notifications = $notificationService->getNotifications($user['id'], $limit, $unreadOnly);
    $result = [];
    while ($notif = $notifications->fetch_assoc()) {
        $result[] = [
            'id' => $notif['id'],
            'title' => $notif['title'],
            'message' => $notif['message'],
            'type' => $notif['type'],
            'link' => $notif['link'],
            'is_read' => (bool)$notif['is_read'],
            'created_at' => $notif['created_at'],
            'time_ago' => timeAgo($notif['created_at'])
        ];
    }
    echo json_encode(['success' => true, 'notifications' => $result], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'count') {
    $count = $notificationService->getUnreadCount($user['id']);
    echo json_encode(['success' => true, 'count' => $count]);
} elseif ($action === 'read' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $success = $notificationService->markAsRead($id, $user['id']);
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

