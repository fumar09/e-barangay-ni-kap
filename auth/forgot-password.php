<?php
/**
 * Forgot Password Page
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

require_once '../includes/config/constants.php';
require_once '../includes/config/database.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/functions/helpers.php';

$auth = new Auth();
$error = '';
$success = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $auth->redirectByRole();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request. Please try again.');
        }
        
        // Sanitize input
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Validate email
        if (empty($email) || !validateEmail($email)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Check if user exists
        $db = getDB();
        $user = $db->fetchOne("SELECT id, username, first_name, last_name FROM users WHERE email = ? AND is_active = 1", [$email]);
        
        if (!$user) {
            // Don't reveal if email exists or not for security
            $success = 'If the email address exists in our system, you will receive password reset instructions.';
        } else {
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database (you might want to create a separate table for this)
            // For now, we'll just show a success message
            $success = 'If the email address exists in our system, you will receive password reset instructions.';
            
            // Log the password reset request
            $auth->logActivity($user['id'], 'password_reset_requested', 'Password reset requested for email: ' . $email);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Generate CSRF token
$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Primary Meta Tags -->
    <title>Forgot Password | Reset Password | e-Barangay ni Kap Portal</title>
    <meta name="title" content="Forgot Password | Reset Password | e-Barangay ni Kap Portal">
    <meta name="description" content="Reset your password for e-Barangay ni Kap portal. Secure password recovery for residents of San Joaquin, Palo, Leyte.">
    <meta name="keywords" content="forgot password, reset password, password recovery, e-barangay password reset, secure password recovery">
    <meta name="author" content="Barangay San Joaquin Administration">
    <meta name="robots" content="noindex, nofollow">
    <meta name="language" content="English">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://ebarangay-ni-kap.com/auth/forgot-password.php">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ebarangay-ni-kap.com/auth/forgot-password.php">
    <meta property="og:title" content="Forgot Password | Reset Password | e-Barangay ni Kap Portal">
    <meta property="og:description" content="Reset your password for e-Barangay ni Kap portal. Secure password recovery.">
    <meta property="og:image" content="https://ebarangay-ni-kap.com/assets/images/sjlg.png">
    <meta property="og:site_name" content="e-Barangay ni Kap">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://ebarangay-ni-kap.com/auth/forgot-password.php">
    <meta property="twitter:title" content="Forgot Password | Reset Password | e-Barangay ni Kap Portal">
    <meta property="twitter:description" content="Reset your password for e-Barangay ni Kap portal. Secure password recovery.">
    <meta property="twitter:image" content="https://ebarangay-ni-kap.com/assets/images/sjlg.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/custom.css">
    
</head>
<body class="auth-page">
    <div class="forgot-container">
        <div class="forgot-header">
            <img src="../assets/images/sjlg.png" alt="Barangay Logo" class="forgot-logo">
            <h1 class="forgot-title">Forgot Password</h1>
            <p class="forgot-subtitle">Enter your email to reset your password</p>
        </div>
        
        <div class="info-box">
            <h6><i class="fas fa-info-circle me-2"></i>How it works</h6>
            <p>Enter your email address and we'll send you a link to reset your password. The link will expire in 1 hour for security.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="forgotForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Email" required 
                       data-bs-toggle="tooltip" title="Enter the email address associated with your account"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <label for="email">
                    <i class="fas fa-envelope me-2"></i>Email Address
                </label>
            </div>
            
            <button type="submit" class="btn btn-reset"
                    data-bs-toggle="tooltip" title="Send password reset instructions to your email">
                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
            </button>
        </form>
        
        <div class="forgot-footer">
            <p class="mb-2">
                <a href="login.php" data-bs-toggle="tooltip" title="Return to login page">
                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                </a>
            </p>
            <p class="mb-0">
                <a href="register.php" data-bs-toggle="tooltip" title="Create a new account">
                    <i class="fas fa-user-plus me-1"></i>Create new account
                </a>
            </p>
            <p class="mt-3 mb-0">
                <a href="../index.html" data-bs-toggle="tooltip" title="Return to homepage">
                    <i class="fas fa-home me-1"></i>Back to homepage
                </a>
            </p>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/custom.js"></script>
    
    <script>
        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
        
        // Form validation
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                e.preventDefault();
                alert('Please enter your email address.');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });
        
        // Tooltips are now initialized automatically via custom.js
    </script>
</body>
</html> 