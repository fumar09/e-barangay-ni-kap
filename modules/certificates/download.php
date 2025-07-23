<?php
/**
 * Certificate Download
 * e-Barangay ni Kap
 * Created: July 21, 2025
 */

require_once '../../includes/config/constants.php';
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/classes/CertificateGenerator.php';
require_once '../../includes/functions/helpers.php';

$auth = new Auth();
$db = getDB();

// Require authentication
$auth->requireAuth();
$user = $auth->getCurrentUser();

$error = '';
$success = '';

// Handle download request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request. Please try again.');
        }
        
        $requestId = (int)($_POST['request_id'] ?? 0);
        
        if (!$requestId) {
            throw new Exception('Invalid request ID.');
        }
        
        // Verify user owns this request
        $request = $db->fetchOne(
            "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name
             FROM certificate_requests cr
             JOIN users u ON cr.resident_id = u.id
             WHERE cr.id = ? AND cr.resident_id = ? AND cr.status = 'Completed'",
            [$requestId, $user['id']]
        );
        
        if (!$request) {
            throw new Exception('Certificate not found or not ready for download.');
        }
        
        // Get certificate file
        $certificate = $db->fetchOne(
            "SELECT * FROM generated_certificates WHERE request_id = ? ORDER BY generated_at DESC LIMIT 1",
            [$requestId]
        );
        
        if (!$certificate || !file_exists($certificate['file_path'])) {
            throw new Exception('Certificate file not found.');
        }
        
        // Download the file
        $certificateGenerator = new CertificateGenerator();
        $filepath = $certificateGenerator->downloadCertificate($requestId);
        
        // Set headers for download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output file
        readfile($filepath);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's completed certificates
$completedCertificates = $db->fetchAll(
    "SELECT cr.*, gc.certificate_number, gc.generated_at, gc.is_downloaded
     FROM certificate_requests cr
     LEFT JOIN generated_certificates gc ON cr.id = gc.request_id
     WHERE cr.resident_id = ? AND cr.status = 'Completed'
     ORDER BY cr.processed_date DESC",
    [$user['id']]
);

// Generate CSRF token
$csrfToken = $auth->generateCSRFToken();

$page_title = 'Download Certificates | e-Barangay ni Kap';
$page_description = 'Download your completed certificates from e-Barangay ni Kap. Access your Barangay Clearance, Certificate of Indigency, Residency Certificate, and Business Permit documents.';
$page_keywords = 'download certificates, barangay clearance, certificate download, e-barangay certificates';
$page_author = 'Barangay San Joaquin Administration';
$page_canonical = 'https://ebarangay-ni-kap.com/modules/certificates/download.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title><?php echo $page_title; ?></title>
    <meta name="title" content="<?php echo $page_title; ?>">
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="<?php echo $page_keywords; ?>">
    <meta name="author" content="<?php echo $page_author; ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    <meta name="distribution" content="global">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo $page_canonical; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $page_canonical; ?>">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="https://ebarangay-ni-kap.com/assets/images/sjlg.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="e-Barangay ni Kap">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $page_canonical; ?>">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo $page_description; ?>">
    <meta property="twitter:image" content="https://ebarangay-ni-kap.com/assets/images/sjlg.png">
    
    <!-- Additional SEO Meta Tags -->
    <meta name="geo.region" content="PH-LEY">
    <meta name="geo.placename" content="Palo, Leyte">
    <meta name="geo.position" content="11.1575;124.9908">
    <meta name="ICBM" content="11.1575, 124.9908">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <link rel="stylesheet" href="../../assets/css/phase2.css">
</head>
<body class="certificate-request-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.html">
                <img src="../../assets/images/sjlg.png" alt="Barangay Logo" class="me-2">
                e-Barangay ni Kap
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.html" data-bs-toggle="tooltip" title="Home">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../about.html" data-bs-toggle="tooltip" title="About Us">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../pages/services.php" data-bs-toggle="tooltip" title="Our Services">
                            <i class="fas fa-cogs"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../pages/announcements.php" data-bs-toggle="tooltip" title="Announcements">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../resident/dashboard.php" data-bs-toggle="tooltip" title="Dashboard">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Download Certificates</h1>
                <p class="page-subtitle">Download your completed certificates</p>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Certificates List -->
            <div class="certificate-form-card">
                <?php if (empty($completedCertificates)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No certificates available for download</h5>
                        <p class="text-muted">You don't have any completed certificates yet.</p>
                        <a href="request.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Request Certificate
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Certificate Type</th>
                                    <th>Request ID</th>
                                    <th>Certificate Number</th>
                                    <th>Purpose</th>
                                    <th>Completed Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completedCertificates as $certificate): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($certificate['certificate_type']); ?></span>
                                        </td>
                                        <td>
                                            <strong>#<?php echo $certificate['id']; ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($certificate['certificate_number']): ?>
                                                <code><?php echo htmlspecialchars($certificate['certificate_number']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">Not generated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" 
                                                 data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($certificate['purpose']); ?>">
                                                <?php echo htmlspecialchars($certificate['purpose']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo formatDate($certificate['processed_date']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Completed</span>
                                            <?php if ($certificate['is_downloaded']): ?>
                                                <br><small class="text-muted">Downloaded</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($certificate['certificate_number']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="request_id" value="<?php echo $certificate['id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" 
                                                            data-bs-toggle="tooltip" title="Download Certificate">
                                                        <i class="fas fa-download"></i> Download
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Processing...</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
                <a href="request.php" class="btn btn-primary" data-bs-toggle="tooltip" title="Submit a new certificate request">
                    <i class="fas fa-plus me-2"></i>Request New Certificate
                </a>
                <a href="track.php" class="btn btn-secondary" data-bs-toggle="tooltip" title="Track your certificate requests">
                    <i class="fas fa-search me-2"></i>Track Requests
                </a>
                <a href="../resident/dashboard.php" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Return to dashboard">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../../assets/js/custom.js"></script>
    <script src="../../assets/js/phase2.js"></script>

    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html> 