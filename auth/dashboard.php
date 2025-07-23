<?php
/**
 * Main Dashboard Redirect
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

require_once '../includes/config/constants.php';
require_once '../includes/config/database.php';
require_once '../includes/classes/Auth.php';

$auth = new Auth();

// Require authentication
$auth->requireAuth();

// Redirect to role-specific dashboard
$auth->redirectByRole();
?> 