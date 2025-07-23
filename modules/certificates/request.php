<?php
/**
 * Certificate Request Form
 * e-Barangay ni Kap
 * Created: July 21, 2025
 */

require_once '../../includes/config/constants.php';
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/NotificationManager.php';
require_once '../../includes/functions/helpers.php';

$auth = new Auth();
$userManager = new User();
$db = getDB();
$notificationManager = new NotificationManager();

// Require authentication
$auth->requireAuth();
$user = $auth->getCurrentUser();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request. Please try again.');
        }
        
        // Sanitize input
        $data = [
            'resident_id' => $user['id'],
            'certificate_type' => sanitizeInput($_POST['certificate_type'] ?? ''),
            'purpose' => sanitizeInput($_POST['purpose'] ?? ''),
            'remarks' => sanitizeInput($_POST['remarks'] ?? ''),
            'request_date' => date('Y-m-d H:i:s'),
            'status' => 'Pending'
        ];
        
        // Validate required fields
        if (empty($data['certificate_type']) || empty($data['purpose'])) {
            throw new Exception('Certificate type and purpose are required.');
        }
        
        // Validate certificate type
        $validTypes = ['Barangay Clearance', 'Certificate of Indigency', 'Certificate of Residency', 'Business Permit'];
        if (!in_array($data['certificate_type'], $validTypes)) {
            throw new Exception('Invalid certificate type selected.');
        }
        
        // Insert certificate request
        $requestId = $db->insert('certificate_requests', $data);
        
        // Handle file uploads
        if (isset($_FILES['supporting_documents']) && $_FILES['supporting_documents']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = CERTIFICATES_PATH . 'supporting_docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = cleanFilename($_FILES['supporting_documents']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($fileExt, ALLOWED_DOCUMENT_TYPES)) {
                throw new Exception('Invalid file type. Only PDF, DOC, DOCX files are allowed.');
            }
            
            // Validate file size
            if ($_FILES['supporting_documents']['size'] > MAX_FILE_SIZE) {
                throw new Exception('File size exceeds maximum limit of 5MB.');
            }
            
            $newFileName = 'request_' . $requestId . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['supporting_documents']['tmp_name'], $filePath)) {
                // Save file record
                $fileData = [
                    'request_id' => $requestId,
                    'file_name' => $newFileName,
                    'original_name' => $fileName,
                    'file_path' => $filePath,
                    'upload_date' => date('Y-m-d H:i:s')
                ];
                $db->insert('request_documents', $fileData);
            }
        }
        
        // Log activity
        $auth->logActivity($user['id'], 'certificate_request', 'Requested ' . $data['certificate_type']);
        
        // Create admin notification
        $notificationManager->createAdminNotification($requestId);
        
        $success = 'Certificate request submitted successfully! Your request ID is: ' . $requestId;
        
        // Clear form data
        $_POST = [];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Generate CSRF token
$csrfToken = $auth->generateCSRFToken();

$page_title = 'Request Certificate | Online Application | e-Barangay ni Kap';
$page_description = 'Submit certificate requests online for Barangay Clearance, Certificate of Indigency, Residency Certificate, and Business Permit. Same-day processing available for residents of San Joaquin, Palo, Leyte.';
$page_keywords = 'certificate request, barangay clearance, indigency certificate, residency certificate, business permit, online application, same day processing';
$page_author = 'Barangay San Joaquin Administration';
$page_canonical = 'https://ebarangay-ni-kap.com/modules/certificates/request.php';
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../resident/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="track.php">
                                <i class="fas fa-search"></i> Track Requests
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
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class="fas fa-certificate me-3"></i>Request Certificate
                        </h1>
                        <p class="page-subtitle">Submit your certificate request for processing</p>
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

                    <!-- Certificate Request Form -->
                    <div class="certificate-form-card">
                        <form method="POST" action="" enctype="multipart/form-data" id="certificateForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            
                            <!-- Certificate Type -->
                            <div class="form-group">
                                <label for="certificate_type" class="form-label">
                                    <i class="fas fa-certificate me-2"></i>Certificate Type *
                                </label>
                                <select class="form-select" id="certificate_type" name="certificate_type" required 
                                        data-bs-toggle="tooltip" title="Select the type of certificate you need">
                                    <option value="">Choose certificate type...</option>
                                    <option value="Barangay Clearance" <?php echo ($_POST['certificate_type'] ?? '') === 'Barangay Clearance' ? 'selected' : ''; ?>>
                                        Barangay Clearance - ₱30.00
                                    </option>
                                    <option value="Certificate of Indigency" <?php echo ($_POST['certificate_type'] ?? '') === 'Certificate of Indigency' ? 'selected' : ''; ?>>
                                        Certificate of Indigency - Free
                                    </option>
                                    <option value="Certificate of Residency" <?php echo ($_POST['certificate_type'] ?? '') === 'Certificate of Residency' ? 'selected' : ''; ?>>
                                        Certificate of Residency - ₱25.00
                                    </option>
                                    <option value="Business Permit" <?php echo ($_POST['certificate_type'] ?? '') === 'Business Permit' ? 'selected' : ''; ?>>
                                        Business Permit - ₱50.00
                                    </option>
                                </select>
                            </div>

                            <!-- Purpose -->
                            <div class="form-group">
                                <label for="purpose" class="form-label">
                                    <i class="fas fa-bullseye me-2"></i>Purpose *
                                </label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" required 
                                          placeholder="Please specify the purpose of your certificate request..."
                                          data-bs-toggle="tooltip" title="Explain why you need this certificate"><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
                            </div>

                            <!-- Supporting Documents -->
                            <div class="form-group">
                                <label for="supporting_documents" class="form-label">
                                    <i class="fas fa-file-upload me-2"></i>Supporting Documents
                                </label>
                                <input type="file" class="form-control" id="supporting_documents" name="supporting_documents" 
                                       accept=".pdf,.doc,.docx"
                                       data-bs-toggle="tooltip" title="Upload supporting documents (PDF, DOC, DOCX - Max 5MB)">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Accepted formats: PDF, DOC, DOCX. Maximum file size: 5MB
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="form-group">
                                <label for="remarks" class="form-label">
                                    <i class="fas fa-comment me-2"></i>Additional Remarks
                                </label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                                          placeholder="Any additional information or special requests..."
                                          data-bs-toggle="tooltip" title="Add any additional information"><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-lg" 
                                        data-bs-toggle="tooltip" title="Submit your certificate request">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Request
                                </button>
                                <a href="../resident/dashboard.php" class="btn btn-secondary btn-lg ms-2" 
                                   data-bs-toggle="tooltip" title="Return to dashboard">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Information Card -->
                    <div class="info-card">
                        <h5><i class="fas fa-info-circle me-2"></i>Important Information</h5>
                        <ul class="info-list">
                            <li><i class="fas fa-clock me-2"></i>Processing time: Same day for most certificates</li>
                            <li><i class="fas fa-money-bill me-2"></i>Payment is required upon pickup</li>
                            <li><i class="fas fa-bell me-2"></i>You will receive notifications on status updates</li>
                            <li><i class="fas fa-search me-2"></i>Track your request status anytime</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
        "name": "Certificate Request Form",
        "description": "Submit certificate requests online for Barangay Clearance, Certificate of Indigency, Residency Certificate, and Business Permit. Same-day processing available for residents of San Joaquin, Palo, Leyte.",
        "url": "https://ebarangay-ni-kap.com/modules/certificates/request.php",
        "mainEntity": {
            "@type": "Service",
            "name": "Certificate Request Service",
            "description": "Online certificate request system for barangay services",
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
            },
            "hasOfferCatalog": {
                "@type": "OfferCatalog",
                "name": "Certificate Services",
                "itemListElement": [
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Barangay Clearance",
                            "description": "Official clearance for employment, business, or other legal purposes"
                        },
                        "price": "30.00",
                        "priceCurrency": "PHP"
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Certificate of Indigency",
                            "description": "Certificate for indigent residents"
                        },
                        "price": "30.00",
                        "priceCurrency": "PHP"
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Certificate of Residency",
                            "description": "Proof of residency certificate"
                        },
                        "price": "30.00",
                        "priceCurrency": "PHP"
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Business Permit",
                            "description": "Business permit for local enterprises"
                        },
                        "price": "30.00",
                        "priceCurrency": "PHP"
                    }
                ]
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
                    "name": "Request Certificate",
                    "item": "https://ebarangay-ni-kap.com/modules/certificates/request.php"
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

        // Form validation
        document.getElementById('certificateForm').addEventListener('submit', function(e) {
            const certificateType = document.getElementById('certificate_type').value;
            const purpose = document.getElementById('purpose').value.trim();
            
            if (!certificateType) {
                e.preventDefault();
                alert('Please select a certificate type.');
                return;
            }
            
            if (!purpose) {
                e.preventDefault();
                alert('Please specify the purpose of your request.');
                return;
            }
        });

        // File size validation
        document.getElementById('supporting_documents').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    alert('File size exceeds 5MB limit. Please choose a smaller file.');
                    e.target.value = '';
                }
            }
        });
    </script>
</body>
</html> 