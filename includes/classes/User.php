<?php
/**
 * User Management Class
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

class User {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = getDB();
        $this->auth = new Auth();
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        // Validate input
        $this->validateRegistrationData($data);
        
        // Check if username or email already exists
        if ($this->userExists($data['username'], $data['email'])) {
            throw new Exception('Username or email already exists.');
        }
        
        // Hash password
        $data['password'] = $this->auth->hashPassword($data['password']);
        
        // Set default role as Resident
        $data['role_id'] = 4; // Resident role
        $data['is_active'] = 1;
        
        // Insert user
        $userId = $this->db->insert('users', $data);
        
        // Log activity
        $this->auth->logActivity($userId, 'registration', 'New user registered');
        
        return $userId;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        // Validate input
        $this->validateProfileData($data);
        
        // Check if email is already taken by another user
        if (isset($data['email'])) {
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$data['email'], $userId]
            );
            
            if ($existingUser) {
                throw new Exception('Email is already taken by another user.');
            }
        }
        
        // Update user
        $this->db->update('users', $data, 'id = ?', [$userId]);
        
        // Log activity
        $this->auth->logActivity($userId, 'profile_update', 'Profile updated');
        
        return true;
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current user
        $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            throw new Exception('User not found.');
        }
        
        // Verify current password
        if (!$this->auth->verifyPassword($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect.');
        }
        
        // Validate new password
        $this->validatePassword($newPassword);
        
        // Hash new password
        $hashedPassword = $this->auth->hashPassword($newPassword);
        
        // Update password
        $this->db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
        
        // Log activity
        $this->auth->logActivity($userId, 'password_change', 'Password changed');
        
        return true;
    }
    
    /**
     * Upload profile photo
     */
    public function uploadProfilePhoto($userId, $file) {
        // Validate file
        $this->validateImageFile($file);
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $filepath = PROFILE_PHOTOS_PATH . $filename;
        
        // Create directory if it doesn't exist
        if (!is_dir(PROFILE_PHOTOS_PATH)) {
            mkdir(PROFILE_PHOTOS_PATH, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to upload file.');
        }
        
        // Get current profile photo
        $currentUser = $this->db->fetchOne("SELECT profile_photo FROM users WHERE id = ?", [$userId]);
        
        // Delete old profile photo if exists
        if ($currentUser && $currentUser['profile_photo'] && file_exists($currentUser['profile_photo'])) {
            unlink($currentUser['profile_photo']);
        }
        
        // Update database
        $this->db->update('users', ['profile_photo' => $filepath], 'id = ?', [$userId]);
        
        // Log activity
        $this->auth->logActivity($userId, 'photo_upload', 'Profile photo uploaded');
        
        return $filepath;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        return $this->db->fetchOne(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ?",
            [$userId]
        );
    }
    
    /**
     * Get all users with pagination
     */
    public function getAllUsers($page = 1, $search = '') {
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $limit = ITEMS_PER_PAGE;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                $whereClause 
                ORDER BY u.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $users = $this->db->fetchAll($sql, $params);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u $whereClause";
        $total = $this->db->fetchOne($countSql, $params);
        
        return [
            'users' => $users,
            'total' => $total['total'],
            'pages' => ceil($total['total'] / ITEMS_PER_PAGE),
            'current_page' => $page
        ];
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        // Get user info
        $user = $this->getUserById($userId);
        
        if (!$user) {
            throw new Exception('User not found.');
        }
        
        // Delete profile photo if exists
        if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
            unlink($user['profile_photo']);
        }
        
        // Delete user
        $this->db->delete('users', 'id = ?', [$userId]);
        
        // Log activity
        $this->auth->logActivity(null, 'user_deleted', "User {$user['username']} deleted");
        
        return true;
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistrationData($data) {
        $required = ['username', 'email', 'password', 'confirm_password', 'first_name', 'last_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("$field is required.");
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }
        
        // Validate password
        $this->validatePassword($data['password']);
        
        // Check password confirmation
        if ($data['password'] !== $data['confirm_password']) {
            throw new Exception('Passwords do not match.');
        }
        
        // Validate username
        if (strlen($data['username']) < 3) {
            throw new Exception('Username must be at least 3 characters long.');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            throw new Exception('Username can only contain letters, numbers, and underscores.');
        }
    }
    
    /**
     * Validate profile data
     */
    private function validateProfileData($data) {
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }
        
        if (isset($data['first_name']) && empty($data['first_name'])) {
            throw new Exception('First name is required.');
        }
        
        if (isset($data['last_name']) && empty($data['last_name'])) {
            throw new Exception('Last name is required.');
        }
    }
    
    /**
     * Validate password
     */
    private function validatePassword($password) {
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, and one number.');
        }
    }
    
    /**
     * Validate image file
     */
    private function validateImageFile($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('No file uploaded.');
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum limit.');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.');
        }
        
        // Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Invalid image file.');
        }
    }
    
    /**
     * Check if user exists
     */
    private function userExists($username, $email) {
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        return $existingUser !== false;
    }
}
?> 