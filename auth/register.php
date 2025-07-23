<?php
/**
 * Registration Page
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

require_once '../includes/config/constants.php';
require_once '../includes/config/database.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/classes/User.php';
require_once '../includes/functions/helpers.php';

$auth = new Auth();
$userManager = new User();
$error = '';
$success = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $auth->redirectByRole();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request. Please try again.');
        }
        
        // Sanitize input
        $data = [
            'username' => sanitizeInput($_POST['username'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'middle_name' => sanitizeInput($_POST['middle_name'] ?? '')
        ];
        
        // Register user
        $userId = $userManager->register($data);
        
        // Handle profile photo upload if provided
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $userManager->uploadProfilePhoto($userId, $_FILES['profile_photo']);
        }
        
        $success = 'Registration successful! You can now login with your credentials.';
        
        // Clear form data
        $_POST = [];
        
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
    <title>Register | Create Account | e-Barangay ni Kap Portal</title>
    <meta name="title" content="Register | Create Account | e-Barangay ni Kap Portal">
    <meta name="description" content="Create your account for e-Barangay ni Kap portal. Register to access barangay services, certificate requests, and community information for residents of San Joaquin, Palo, Leyte.">
    <meta name="keywords" content="register account, create account, e-barangay registration, barangay san joaquin registration, government portal registration">
    <meta name="author" content="Barangay San Joaquin Administration">
    <meta name="robots" content="noindex, nofollow">
    <meta name="language" content="English">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://ebarangay-ni-kap.com/auth/register.php">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ebarangay-ni-kap.com/auth/register.php">
    <meta property="og:title" content="Register | Create Account | e-Barangay ni Kap Portal">
    <meta property="og:description" content="Create your account for e-Barangay ni Kap portal. Register to access barangay services and community information.">
    <meta property="og:image" content="https://ebarangay-ni-kap.com/assets/images/sjlg.png">
    <meta property="og:site_name" content="e-Barangay ni Kap">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://ebarangay-ni-kap.com/auth/register.php">
    <meta property="twitter:title" content="Register | Create Account | e-Barangay ni Kap Portal">
    <meta property="twitter:description" content="Create your account for e-Barangay ni Kap portal. Register to access barangay services and community information.">
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
    <div class="register-container">
        <div class="register-header">
            <img src="../assets/images/sjlg.png" alt="Barangay Logo" class="register-logo">
            <h1 class="register-title">e-Barangay ni Kap</h1>
            <p class="register-subtitle">Create your account</p>
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
        
        <form method="POST" action="" enctype="multipart/form-data" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Username" required 
                               data-bs-toggle="tooltip" title="Choose a unique username (minimum 3 characters)"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <label for="username">
                            <i class="fas fa-user me-2"></i>Username
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Email" required 
                               data-bs-toggle="tooltip" title="Enter a valid email address for account verification"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               placeholder="First Name" required 
                               data-bs-toggle="tooltip" title="Enter your first name as it appears on official documents"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        <label for="first_name">
                            <i class="fas fa-user me-2"></i>First Name
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="middle_name" name="middle_name" 
                               placeholder="Middle Name" 
                               data-bs-toggle="tooltip" title="Enter your middle name (optional)"
                               value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                        <label for="middle_name">
                            <i class="fas fa-user me-2"></i>Middle Name
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               placeholder="Last Name" required 
                               data-bs-toggle="tooltip" title="Enter your last name/surname"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        <label for="last_name">
                            <i class="fas fa-user me-2"></i>Last Name
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required
                               data-bs-toggle="tooltip" title="Minimum 8 characters with letters, numbers, and special characters">
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                    </div>
                    <div class="password-strength">
                        <small class="text-muted">Password strength: <span id="strengthText">Weak</span></small>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthBar"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm Password" required
                               data-bs-toggle="tooltip" title="Re-enter your password to confirm">
                        <label for="confirm_password">
                            <i class="fas fa-lock me-2"></i>Confirm Password
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="photo-upload" onclick="document.getElementById('profile_photo').click()"
                 data-bs-toggle="tooltip" title="Upload a profile photo (JPG, PNG, GIF - Max 5MB)">
                <i class="fas fa-camera fa-3x text-primary mb-3"></i>
                <h5>Profile Photo (Optional)</h5>
                <p class="text-muted">Click to upload a profile photo</p>
                <input type="file" id="profile_photo" name="profile_photo" accept="image/*" onchange="previewPhoto(this)">
                <img id="photoPreview" class="photo-preview" alt="Profile Preview">
            </div>
            
            <button type="submit" class="btn btn-register"
                    data-bs-toggle="tooltip" title="Create your new account">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>
        
        <div class="register-footer">
            <p class="mb-0">
                Already have an account? 
                <a href="login.php" data-bs-toggle="tooltip" title="Sign in to your existing account">
                    <i class="fas fa-sign-in-alt me-1"></i>Login here
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
        
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }
        
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            strengthBar.className = 'strength-fill';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak';
                strengthBar.style.width = '25%';
            } else if (strength === 3) {
                strengthBar.classList.add('strength-fair');
                strengthText.textContent = 'Fair';
                strengthBar.style.width = '50%';
            } else if (strength === 4) {
                strengthBar.classList.add('strength-good');
                strengthText.textContent = 'Good';
                strengthBar.style.width = '75%';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong';
                strengthBar.style.width = '100%';
            }
        }
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
        });
        
        // Auto-focus on first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Tooltips are now initialized automatically via custom.js
    </script>
</body>
</html> 