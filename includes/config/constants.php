<?php
/**
 * Application Constants
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

// Application settings
define('APP_NAME', 'e-Barangay ni Kap');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/e-Barangay-ni-Kap');
define('APP_ROOT', dirname(dirname(__DIR__)));

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'ebarangay_session');
define('PASSWORD_COST', 12);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);
define('UPLOAD_PATH', APP_ROOT . '/assets/uploads/');
define('PROFILE_PHOTOS_PATH', UPLOAD_PATH . 'photos/');
define('DOCUMENTS_PATH', UPLOAD_PATH . 'documents/');
define('CERTIFICATES_PATH', UPLOAD_PATH . 'certificates/');

// Pagination settings
define('ITEMS_PER_PAGE', 10);
define('MAX_PAGES_SHOWN', 5);

// Email settings (for future use)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@ebarangay.com');
define('SMTP_FROM_NAME', 'e-Barangay ni Kap');

// Error reporting
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Barangay Information
define('BARANGAY_FACEBOOK', 'https://www.facebook.com/share/19QPMaXYPZ/');
define('BARANGAY_PHONE', '+63961 225 3924');
define('BARANGAY_ADDRESS', '42P4+QHM, San Joaquin, Palo, Leyte');

// Timezone
date_default_timezone_set('Asia/Manila');

// Memory and performance settings
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

// Session configuration (only set if session is not started)
if (session_status() === PHP_SESSION_NONE) {
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
}
?> 