<?php
/**
 * Login Page
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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request. Please try again.');
        }
        
        // Sanitize input
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($username) || empty($password)) {
            throw new Exception('Username and password are required.');
        }
        
        // Attempt login
        $user = $auth->login($username, $password);
        
        // Redirect based on role
        $auth->redirectByRole();
        
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
    <title>Login | e-Barangay ni Kap Portal | Barangay San Joaquin</title>
    <meta name="title" content="Login | e-Barangay ni Kap Portal | Barangay San Joaquin">
    <meta name="description" content="Secure login portal for e-Barangay ni Kap. Access barangay services, certificate requests, and community information for residents of San Joaquin, Palo, Leyte.">
    <meta name="keywords" content="login portal, e-barangay login, barangay san joaquin portal, secure login, government portal">
    <meta name="author" content="Barangay San Joaquin Administration">
    <meta name="robots" content="noindex, nofollow">
    <meta name="language" content="English">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://ebarangay-ni-kap.com/auth/login.php">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ebarangay-ni-kap.com/auth/login.php">
    <meta property="og:title" content="Login | e-Barangay ni Kap Portal | Barangay San Joaquin">
    <meta property="og:description" content="Secure login portal for e-Barangay ni Kap. Access barangay services and community information.">
    <meta property="og:image" content="https://ebarangay-ni-kap.com/assets/images/sjlg.png">
    <meta property="og:site_name" content="e-Barangay ni Kap">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://ebarangay-ni-kap.com/auth/login.php">
    <meta property="twitter:title" content="Login | e-Barangay ni Kap Portal | Barangay San Joaquin">
    <meta property="twitter:description" content="Secure login portal for e-Barangay ni Kap. Access barangay services and community information.">
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
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/images/sjlg.png" alt="Barangay Logo" class="login-logo">
            <h1 class="login-title">e-Barangay ni Kap</h1>
            <p class="login-subtitle">Login to your account</p>
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
        
        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Username or Email" required 
                       data-bs-toggle="tooltip" title="Enter your username or email address"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <label for="username">
                    <i class="fas fa-user me-2"></i>Username or Email
                </label>
            </div>
            
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Password" required
                       data-bs-toggle="tooltip" title="Enter your account password">
                <label for="password">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
            </div>
            
            <button type="submit" class="btn btn-login" 
                    data-bs-toggle="tooltip" title="Sign in to your account">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        
        <div class="login-footer">
            <p class="mb-2">
                <a href="register.php" data-bs-toggle="tooltip" title="Create a new account">
                    <i class="fas fa-user-plus me-1"></i>Create new account
                </a>
            </p>
            <p class="mb-0">
                <a href="forgot-password.php" data-bs-toggle="tooltip" title="Reset your password">
                    <i class="fas fa-key me-1"></i>Forgot password?
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
        // Password toggle function now available globally via custom.js
        
        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
        });
        
        // Tooltips are now initialized automatically via custom.js
    </script>
</body>
</html> 