<?php
/**
 * Application Bootstrap File
 * e-Barangay ni Kap
 * Centralizes common includes and initialization
 */

// Prevent direct access
if (!defined('BOOTSTRAP_LOADED')) {
    define('BOOTSTRAP_LOADED', true);
} else {
    return;
}

// Core configuration
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

// Core classes
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/User.php';

// Helper functions
require_once __DIR__ . '/functions/helpers.php';

// Initialize authentication
$auth = new Auth();
$db = getDB();

// Common page variables (can be overridden)
if (!isset($page_title)) $page_title = APP_NAME;
if (!isset($page_description)) $page_description = 'e-Barangay ni Kap Management System';
if (!isset($body_class)) $body_class = '';
?> 