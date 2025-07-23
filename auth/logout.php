<?php
/**
 * Logout Handler
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

require_once '../includes/config/constants.php';
require_once '../includes/config/database.php';
require_once '../includes/classes/Auth.php';

$auth = new Auth();

// Log the logout activity if user was logged in
if ($auth->isLoggedIn()) {
    $auth->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Perform logout
$auth->logout();

// Redirect to login page with success message
header('Location: login.php?message=logout_success');
exit();
?> 