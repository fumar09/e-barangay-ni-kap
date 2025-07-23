<?php
/**
 * Notification Manager Class
 * Handles dashboard notifications and real-time updates
 * e-Barangay ni Kap
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

class NotificationManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($userId, $type, $title, $message, $data = []) {
        $notificationData = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('notifications', $notificationData);
    }
    
    /**
     * Create certificate request notification
     */
    public function createCertificateRequestNotification($requestId, $status) {
        try {
            // Get request details
            $request = $this->getRequestDetails($requestId);
            if (!$request) {
                return false;
            }
            
            $type = 'certificate_request';
            $title = 'Certificate Request Update';
            $message = "Your {$request['certificate_type']} request has been {$status}.";
            
            $data = [
                'request_id' => $requestId,
                'certificate_type' => $request['certificate_type'],
                'status' => $status,
                'action_url' => APP_URL . '/modules/certificates/track.php'
            ];
            
            return $this->createNotification($request['resident_id'], $type, $title, $message, $data);
            
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create admin notification for new request
     */
    public function createAdminNotification($requestId) {
        try {
            // Get request details
            $request = $this->getRequestDetails($requestId);
            if (!$request) {
                return false;
            }
            
            // Get admin users
            $admins = $this->getAdminUsers();
            
            foreach ($admins as $admin) {
                $type = 'new_request';
                $title = 'New Certificate Request';
                $message = "New {$request['certificate_type']} request from {$request['resident_name']}.";
                
                $data = [
                    'request_id' => $requestId,
                    'resident_name' => $request['resident_name'],
                    'certificate_type' => $request['certificate_type'],
                    'action_url' => APP_URL . '/modules/admin/process-requests.php'
                ];
                
                $this->createNotification($admin['id'], $type, $title, $message, $data);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Admin notification creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create announcement notification
     */
    public function createAnnouncementNotification($announcementId) {
        try {
            // Get announcement details
            $announcement = $this->getAnnouncementDetails($announcementId);
            if (!$announcement) {
                return false;
            }
            
            // Get all active users
            $users = $this->getActiveUsers();
            
            foreach ($users as $user) {
                $type = 'announcement';
                $title = 'New Announcement';
                $message = "New announcement: {$announcement['title']}";
                
                $data = [
                    'announcement_id' => $announcementId,
                    'title' => $announcement['title'],
                    'action_url' => APP_URL . '/pages/announcements.php'
                ];
                
                $this->createNotification($user['id'], $type, $title, $message, $data);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Announcement notification creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $result = $this->db->fetchOne($sql, [$userId]);
        
        return $result ? $result['count'] : 0;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $data = [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('notifications', $data, [
            'id' => $notificationId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        $data = [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('notifications', $data, [
            'user_id' => $userId,
            'is_read' => 0
        ]);
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($notificationId, $userId) {
        return $this->db->delete('notifications', [
            'id' => $notificationId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats($userId) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN type = 'certificate_request' THEN 1 ELSE 0 END) as certificate_requests,
                    SUM(CASE WHEN type = 'announcement' THEN 1 ELSE 0 END) as announcements,
                    SUM(CASE WHEN type = 'new_request' THEN 1 ELSE 0 END) as new_requests
                FROM notifications 
                WHERE user_id = ?";
        
        return $this->db->fetchOne($sql, [$userId]);
    }
    
    /**
     * Get recent notifications for dashboard
     */
    public function getRecentNotifications($userId, $limit = 5) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }
    
    /**
     * Clean old notifications
     */
    public function cleanOldNotifications($days = 30) {
        $sql = "DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                AND is_read = 1";
        
        return $this->db->execute($sql, [$days]);
    }
    
    /**
     * Get request details
     */
    private function getRequestDetails($requestId) {
        $sql = "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name, 
                       u.id as resident_id
                FROM certificate_requests cr
                JOIN users u ON cr.resident_id = u.id
                WHERE cr.id = ?";
        
        return $this->db->fetchOne($sql, [$requestId]);
    }
    
    /**
     * Get admin users
     */
    private function getAdminUsers() {
        $sql = "SELECT u.id FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE r.name IN ('Administrator', 'Staff')
                AND u.is_active = 1";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get active users
     */
    private function getActiveUsers() {
        $sql = "SELECT id FROM users WHERE is_active = 1";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get announcement details
     */
    private function getAnnouncementDetails($announcementId) {
        $sql = "SELECT * FROM announcements WHERE id = ?";
        return $this->db->fetchOne($sql, [$announcementId]);
    }
    
    /**
     * Get notification type icon
     */
    public function getNotificationIcon($type) {
        switch ($type) {
            case 'certificate_request':
                return 'fas fa-file-alt';
            case 'announcement':
                return 'fas fa-bullhorn';
            case 'new_request':
                return 'fas fa-plus-circle';
            case 'system':
                return 'fas fa-cog';
            default:
                return 'fas fa-bell';
        }
    }
    
    /**
     * Get notification type color
     */
    public function getNotificationColor($type) {
        switch ($type) {
            case 'certificate_request':
                return 'text-primary';
            case 'announcement':
                return 'text-info';
            case 'new_request':
                return 'text-warning';
            case 'system':
                return 'text-secondary';
            default:
                return 'text-muted';
        }
    }
    
    /**
     * Format notification time
     */
    public function formatNotificationTime($timestamp) {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}
?> 