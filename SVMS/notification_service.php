<?php
require_once __DIR__ . '/config.php';

class NotificationService {
    private $db;
    
    public function __construct() {
        $this->db = get_db_connection();
    }
    
    public function create($userId, $title, $message, $type = 'info', $link = null) {
        $stmt = $this->db->prepare('INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $userId, $title, $message, $type, $link);
        return $stmt->execute();
    }
    
    public function createForAllAdmins($title, $message, $type = 'info', $link = null) {
        $admins = $this->db->query("SELECT id FROM users WHERE role = 'admin'");
        $count = 0;
        while ($admin = $admins->fetch_assoc()) {
            if ($this->create($admin['id'], $title, $message, $type, $link)) {
                $count++;
            }
        }
        return $count;
    }
    
    public function createForAllStaff($title, $message, $type = 'info', $link = null) {
        $staff = $this->db->query("SELECT id FROM users WHERE role IN ('admin', 'staff')");
        $count = 0;
        while ($member = $staff->fetch_assoc()) {
            if ($this->create($member['id'], $title, $message, $type, $link)) {
                $count++;
            }
        }
        return $count;
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return (int)$result->fetch_assoc()['count'];
    }
    
    public function getNotifications($userId, $limit = 10, $unreadOnly = false) {
        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        if ($unreadOnly) {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notificationId, $userId);
        return $stmt->execute();
    }
    
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }
    
    public function delete($notificationId, $userId) {
        $stmt = $this->db->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notificationId, $userId);
        return $stmt->execute();
    }
}

