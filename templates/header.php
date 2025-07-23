<?php
/**
 * Header Template
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../includes/config/constants.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title><?php echo isset($page_title) ? $page_title : APP_NAME; ?></title>
    <meta name="title" content="<?php echo isset($page_title) ? $page_title : APP_NAME; ?>">
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Official website of Barangay San Joaquin, Palo, Leyte. Access barangay services, announcements, and community information.'; ?>">
    <meta name="keywords" content="<?php echo isset($page_keywords) ? $page_keywords : 'barangay san joaquin, palo leyte, barangay services, e-governance, local government'; ?>">
    <meta name="author" content="<?php echo isset($page_author) ? $page_author : 'Barangay San Joaquin Administration'; ?>">
    <meta name="robots" content="index, follow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    <meta name="distribution" content="global">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo isset($page_canonical) ? $page_canonical : APP_URL . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo APP_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title : APP_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Official website of Barangay San Joaquin, Palo, Leyte. Access barangay services, announcements, and community information.'; ?>">
    <meta property="og:image" content="<?php echo APP_URL; ?>/assets/images/sjlg.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="e-Barangay ni Kap">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo APP_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo isset($page_title) ? $page_title : APP_NAME; ?>">
    <meta property="twitter:description" content="<?php echo isset($page_description) ? $page_description : 'Official website of Barangay San Joaquin, Palo, Leyte. Access barangay services, announcements, and community information.'; ?>">
    <meta property="twitter:image" content="<?php echo APP_URL; ?>/assets/images/sjlg.png">
    
    <!-- Additional SEO Meta Tags -->
    <meta name="geo.region" content="PH-LEY">
    <meta name="geo.placename" content="Palo, Leyte">
    <meta name="geo.position" content="11.1575;124.9908">
    <meta name="ICBM" content="11.1575, 124.9908">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/phase3.css">
    
    <!-- Page-specific CSS -->
    <?php if (isset($page_css)): ?>
        <?php foreach ($page_css as $css): ?>
            <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/index.html">
                <img src="<?php echo APP_URL; ?>/assets/images/sjlg.png" alt="Barangay Logo" class="me-2">
                e-Barangay ni Kap
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.html' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/index.html" data-bs-toggle="tooltip" title="Home">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.html' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/about.html" data-bs-toggle="tooltip" title="About Us">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/index.html#map" data-bs-toggle="tooltip" title="Location Map">
                            <i class="fas fa-map-marker-alt"></i> Map
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/pages/services.php" data-bs-toggle="tooltip" title="Our Services">
                            <i class="fas fa-cogs"></i> Services
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User is logged in -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/auth/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/modules/<?php echo strtolower($_SESSION['role_name']); ?>/profile.php">
                                    <i class="fas fa-user-edit"></i> Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- User is not logged in -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/auth/login.php" data-bs-toggle="tooltip" title="Login to Portal">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="mt-80"> 