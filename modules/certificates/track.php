<?php
/**
 * Certificate Request Tracking
 * e-Barangay ni Kap
 * Created: July 21, 2025
 */

require_once '../../includes/config/constants.php';
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/functions/helpers.php';

$auth = new Auth();
$userManager = new User();
$db = getDB();

// Require authentication
$auth->requireAuth();
$user = $auth->getCurrentUser();

$error = '';
$success = '';
$requests = [];

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request. Please try again.');
        }
        
        $searchType = sanitizeInput($_POST['search_type'] ?? '');
        $searchValue = sanitizeInput($_POST['search_value'] ?? '');
        
        if (empty($searchValue)) {
            throw new Exception('Please enter a search value.');
        }
        
        // Search requests based on type
        if ($searchType === 'request_id') {
            $requests = $db->fetchAll(
                "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name
                 FROM certificate_requests cr
                 JOIN users u ON cr.resident_id = u.id
                 WHERE cr.id = ? AND cr.resident_id = ?
                 ORDER BY cr.request_date DESC",
                [$searchValue, $user['id']]
            );
        } elseif ($searchType === 'certificate_type') {
            $requests = $db->fetchAll(
                "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name
                 FROM certificate_requests cr
                 JOIN users u ON cr.resident_id = u.id
                 WHERE cr.certificate_type LIKE ? AND cr.resident_id = ?
                 ORDER BY cr.request_date DESC",
                ['%' . $searchValue . '%', $user['id']]
            );
        } else {
            // Show all user's requests
            $requests = $db->fetchAll(
                "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name
                 FROM certificate_requests cr
                 JOIN users u ON cr.resident_id = u.id
                 WHERE cr.resident_id = ?
                 ORDER BY cr.request_date DESC",
                [$user['id']]
            );
        }
        
        if (empty($requests)) {
            $success = 'No requests found matching your search criteria.';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    // Show all user's requests by default
    $requests = $db->fetchAll(
        "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name
         FROM certificate_requests cr
         JOIN users u ON cr.resident_id = u.id
         WHERE cr.resident_id = ?
         ORDER BY cr.request_date DESC
         LIMIT 10",
        [$user['id']]
    );
}

// Generate CSRF token
$csrfToken = $auth->generateCSRFToken();

$page_title = 'Track Certificate Requests | Status Monitoring | e-Barangay ni Kap';
$page_description = 'Track the status of your certificate requests in real-time. Monitor processing status, download completed certificates, and stay updated on your applications with Barangay San Joaquin.';
$page_keywords = 'track certificate requests, request status, certificate tracking, online status check, barangay certificate status';
$page_author = 'Barangay San Joaquin Administration';
$page_canonical = 'https://ebarangay-ni-kap.com/modules/certificates/track.php';
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
<body class="request-tracking-page">
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../resident/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="request.php">
                                <i class="fas fa-certificate"></i> Request Certificate
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-search me-3"></i>Track Certificate Requests
                </h1>
                <p class="page-subtitle">Monitor the status of your certificate requests</p>
            </div>

            <!-- Alert Messages -->
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

            <!-- Search Form -->
            <div class="search-form-card">
                <form method="POST" action="" id="searchForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search_type" class="form-label">
                                    <i class="fas fa-filter me-2"></i>Search By
                                </label>
                                <select class="form-select" id="search_type" name="search_type" 
                                        data-bs-toggle="tooltip" title="Choose how to search for your requests">
                                    <option value="all">All My Requests</option>
                                    <option value="request_id">Request ID</option>
                                    <option value="certificate_type">Certificate Type</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="search_value" class="form-label">
                                    <i class="fas fa-search me-2"></i>Search Value
                                </label>
                                <input type="text" class="form-control" id="search_value" name="search_value" 
                                       placeholder="Enter search value..."
                                       data-bs-toggle="tooltip" title="Enter the value to search for">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100" 
                                        data-bs-toggle="tooltip" title="Search for requests">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Request Results -->
            <?php if (!empty($requests)): ?>
                <div class="row">
                    <div class="col-12">
                        <h3 class="mb-3">
                            <i class="fas fa-list me-2"></i>Request Results
                            <span class="badge bg-primary ms-2"><?php echo count($requests); ?></span>
                        </h3>
                        
                        <?php foreach ($requests as $request): ?>
                            <div class="request-status-card">
                                <div class="request-header">
                                    <div class="request-id">
                                        <i class="fas fa-hashtag me-1"></i>Request #<?php echo $request['id']; ?>
                                    </div>
                                    <div class="request-date">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDate($request['request_date'], 'F j, Y g:i A'); ?>
                                    </div>
                                </div>
                                
                                <div class="request-type">
                                    <i class="fas fa-certificate me-2"></i><?php echo htmlspecialchars($request['certificate_type']); ?>
                                </div>
                                
                                <div class="request-purpose">
                                    <strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?>
                                </div>
                                
                                <?php if (!empty($request['remarks'])): ?>
                                    <div class="request-remarks mb-2">
                                        <strong>Remarks:</strong> <?php echo htmlspecialchars($request['remarks']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="request-status status-<?php echo strtolower($request['status']); ?>">
                                        <i class="fas fa-circle me-1"></i><?php echo $request['status']; ?>
                                    </div>
                                    
                                    <div class="request-actions">
                                        <button class="btn btn-view btn-sm" 
                                                onclick="viewRequestDetails(<?php echo $request['id']; ?>)"
                                                data-bs-toggle="tooltip" title="View request details">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </button>
                                        
                                        <?php if ($request['status'] === 'Completed'): ?>
                                            <button class="btn btn-success btn-sm ms-2" 
                                                    onclick="downloadCertificate(<?php echo $request['id']; ?>)"
                                                    data-bs-toggle="tooltip" title="Download certificate">
                                                <i class="fas fa-download me-1"></i>Download
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($request['admin_remarks'])): ?>
                                    <div class="admin-remarks mt-2">
                                        <strong>Admin Remarks:</strong> 
                                        <span class="text-muted"><?php echo htmlspecialchars($request['admin_remarks']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No requests found</h4>
                    <p class="text-muted">Try adjusting your search criteria or submit a new request.</p>
                    <a href="request.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Request New Certificate
                    </a>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="info-card">
                        <h5><i class="fas fa-lightbulb me-2"></i>Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <a href="request.php" class="btn btn-primary w-100 mb-2" 
                                   data-bs-toggle="tooltip" title="Submit a new certificate request">
                                    <i class="fas fa-certificate me-2"></i>Request New Certificate
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="../resident/dashboard.php" class="btn btn-secondary w-100 mb-2" 
                                   data-bs-toggle="tooltip" title="Return to dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Request Details Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content request-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalLabel">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="requestModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../../assets/js/custom.js"></script>
    <script src="../../assets/js/phase2.js"></script>
    
    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Track Certificate Requests",
        "description": "Track the status of your certificate requests in real-time. Monitor processing status, download completed certificates, and stay updated on your applications with Barangay San Joaquin.",
        "url": "https://ebarangay-ni-kap.com/modules/certificates/track.php",
        "mainEntity": {
            "@type": "Service",
            "name": "Certificate Request Tracking",
            "description": "Real-time tracking system for certificate requests",
            "provider": {
                "@type": "GovernmentOrganization",
                "name": "Barangay San Joaquin",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "Barangay San Joaquin",
                    "addressLocality": "Palo",
                    "addressRegion": "Leyte",
                    "addressCountry": "PH"
                }
            }
        },
        "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Home",
                    "item": "https://ebarangay-ni-kap.com/"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Services",
                    "item": "https://ebarangay-ni-kap.com/pages/services.php"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "Track Requests",
                    "item": "https://ebarangay-ni-kap.com/modules/certificates/track.php"
                }
            ]
        }
    }
    </script>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Search form handling
        document.getElementById('search_type').addEventListener('change', function() {
            const searchValue = document.getElementById('search_value');
            if (this.value === 'all') {
                searchValue.value = '';
                searchValue.placeholder = 'All requests will be shown';
                searchValue.disabled = true;
            } else {
                searchValue.disabled = false;
                searchValue.placeholder = 'Enter search value...';
            }
        });

        // View request details
        function viewRequestDetails(requestId) {
            // This would typically make an AJAX call to get detailed information
            // For now, we'll show a simple message
            const modalBody = document.getElementById('requestModalBody');
            modalBody.innerHTML = `
                <div class="request-details">
                    <h6>Request Information</h6>
                    <div class="detail-item">
                        <span class="detail-label">Request ID:</span>
                        <span class="detail-value">#${requestId}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">Processing</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Submitted:</span>
                        <span class="detail-value">Today</span>
                    </div>
                </div>
                <p class="text-muted">Detailed information will be loaded here.</p>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('requestModal'));
            modal.show();
        }

        // Download certificate
        function downloadCertificate(requestId) {
            // This would typically redirect to a download endpoint
            alert('Certificate download functionality will be implemented in the next phase.');
        }

        // Initialize search type
        document.addEventListener('DOMContentLoaded', function() {
            const searchType = document.getElementById('search_type');
            const searchValue = document.getElementById('search_value');
            
            if (searchType.value === 'all') {
                searchValue.disabled = true;
                searchValue.placeholder = 'All requests will be shown';
            }
        });
    </script>
</body>
</html> 