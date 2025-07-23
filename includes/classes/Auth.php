<?php
/**
 * Authentication Class
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $user = null;
    
    public function __construct() {
        $this->db = getDB();
        $this->startSession();
    }
    
    /**
     * Start secure session
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration']) || 
            time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Hash password
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !$this->isSessionExpired();
    }
    
    /**
     * Check if session is expired
     */
    private function isSessionExpired() {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            // Don't call logout() here to avoid infinite loop
            // Just clear session data directly
            session_unset();
            session_destroy();
            session_start();
            $this->user = null;
            return true;
        }
        
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        if ($this->user === null) {
            try {
                $this->user = $this->db->fetchOne(
                    "SELECT u.*, r.name as role_name, r.permissions 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.id 
                     WHERE u.id = ? AND u.is_active = 1",
                    [$_SESSION['user_id']]
                );
                
                // If user not found, clear session
                if (!$this->user) {
                    session_unset();
                    session_destroy();
                    session_start();
                    return null;
                }
            } catch (Exception $e) {
                error_log("Error fetching current user: " . $e->getMessage());
                return null;
            }
        }
        
        return $this->user;
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        // Check for login attempts
        if ($this->isAccountLocked($username)) {
            throw new Exception('Account is temporarily locked due to multiple failed login attempts.');
        }
        
        // Get user by username or email
        $user = $this->db->fetchOne(
            "SELECT u.*, r.name as role_name, r.permissions 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1",
            [$username, $username]
        );
        
        if (!$user || !$this->verifyPassword($password, $user['password'])) {
            $this->recordFailedLogin($username);
            throw new Exception('Invalid username or password.');
        }
        
        // Clear failed login attempts
        $this->clearFailedLogins($username);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$user['id']]
        );
        
        // Log activity
        $this->logActivity($user['id'], 'login', 'User logged in successfully');
        
        return $user;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Clear session
        session_unset();
        session_destroy();
        session_start();
        
        // Clear user object
        $this->user = null;
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $permissions = json_decode($user['permissions'], true);
        
        // Administrator has all permissions
        if (isset($permissions['all']) && $permissions['all'] === true) {
            return true;
        }
        
        // Check specific permission
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($username) {
        $failedLogins = $this->db->fetchOne(
            "SELECT COUNT(*) as count, MAX(created_at) as last_attempt 
             FROM activity_logs 
             WHERE action = 'failed_login' 
             AND description LIKE ? 
             AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            ["%$username%", LOGIN_LOCKOUT_TIME]
        );
        
        return $failedLogins && 
               $failedLogins['count'] >= MAX_LOGIN_ATTEMPTS && 
               strtotime($failedLogins['last_attempt']) > (time() - LOGIN_LOCKOUT_TIME);
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedLogin($username) {
        $this->logActivity(null, 'failed_login', "Failed login attempt for username: $username");
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedLogins($username) {
        // This could be implemented by deleting old failed login records
        // For now, we just rely on the time-based check
    }
    
    /**
     * Log user activity
     */
    public function logActivity($userId, $action, $description = '') {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $this->db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . APP_URL . '/auth/login.php');
            exit();
        }
    }
    
    /**
     * Require specific permission
     */
    public function requirePermission($permission) {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            die('Access denied. You do not have permission to access this resource.');
        }
    }
    
    /**
     * Redirect based on user role
     */
    public function redirectByRole() {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: ' . APP_URL . '/auth/login.php');
            exit();
        }
        
        switch ($user['role_name']) {
            case 'Administrator':
                header('Location: ' . APP_URL . '/admin/dashboard.php');
                break;
            case 'Staff':
                header('Location: ' . APP_URL . '/staff/dashboard.php');
                break;
            case 'Purok Leader':
                header('Location: ' . APP_URL . '/purok/dashboard.php');
                break;
            case 'Resident':
                header('Location: ' . APP_URL . '/resident/dashboard.php');
                break;
            default:
                header('Location: ' . APP_URL . '/auth/dashboard.php');
        }
        exit();
    }
}
?> 