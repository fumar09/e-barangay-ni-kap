<?php
/**
 * Admin Certificate Request Processing
 * e-Barangay ni Kap
 * Created: July 21, 2025
 */

require_once '../../includes/config/constants.php';
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/classes/User.php';
require_once '../../includes/classes/CertificateGenerator.php';
require_once '../../includes/classes/EmailNotifier.php';
require_once '../../includes/classes/NotificationManager.php';
require_once '../../includes/functions/helpers.php';

$auth = new Auth();
$userManager = new User();
$db = getDB();
$emailNotifier = new EmailNotifier();
$notificationManager = new NotificationManager();

// Require admin authentication
$auth->requireAuth();
$user = $auth->getCurrentUser();

// Check if user is admin or staff
if (!in_array($user['role'], ['admin', 'staff'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$error = '';
$success = '';

// Handle request processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request. Please try again.');
        }
        
        $requestId = (int)($_POST['request_id'] ?? 0);
        $action = sanitizeInput($_POST['action'] ?? '');
        $remarks = sanitizeInput($_POST['remarks'] ?? '');
        
        if (!$requestId) {
            throw new Exception('Invalid request ID.');
        }
        
        if (!in_array($action, ['approve', 'reject', 'process', 'complete'])) {
            throw new Exception('Invalid action.');
        }
        
        // Get request details
        $request = $db->fetchOne(
            "SELECT cr.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name, r.email
             FROM certificate_requests cr
             JOIN residents r ON cr.resident_id = r.id
             WHERE cr.id = ?",
            [$requestId]
        );
        
        if (!$request) {
            throw new Exception('Request not found.');
        }
        
        // Update request status
        $status = '';
        switch ($action) {
            case 'approve':
                $status = 'Approved';
                break;
            case 'reject':
                $status = 'Rejected';
                break;
            case 'process':
                $status = 'Processing';
                break;
            case 'complete':
                $status = 'Completed';
                break;
        }
        
        $updateData = [
            'status' => $status,
            'admin_remarks' => $remarks,
            'processed_by' => $user['id'],
            'processed_date' => date('Y-m-d H:i:s')
        ];
        
        // Get current status before update
        $currentRequest = $db->fetchOne(
            "SELECT status FROM certificate_requests WHERE id = ?",
            [$requestId]
        );
        $statusFrom = $currentRequest['status'] ?? 'Unknown';
        
        $db->update('certificate_requests', $updateData, ['id' => $requestId]);
        
        // Log activity
        $auth->logActivity($user['id'], 'process_request', "Processed request #$requestId - $status");
        
        // Add to request history for audit trail
        $historyData = [
            'request_id' => $requestId,
            'action' => $action,
            'status_from' => $statusFrom,
            'status_to' => $status,
            'remarks' => $remarks,
            'performed_by' => $user['id'],
            'performed_at' => date('Y-m-d H:i:s')
        ];
        $db->insert('request_history', $historyData);
        
        // Send email notification
        $emailNotifier->sendRequestStatusNotification($requestId, $status, $remarks);
        
        // Create notification
        $notificationManager->createCertificateRequestNotification($requestId, $status);
        
        // Generate PDF if approved
        if ($action === 'approve') {
            try {
                $certificateGenerator = new CertificateGenerator();
                $certificateGenerator->generateCertificate($requestId);
                
                // Send completion notification
                $emailNotifier->sendCertificateCompletionNotification($requestId);
                $notificationManager->createCertificateRequestNotification($requestId, 'completed');
                
            } catch (Exception $e) {
                error_log('PDF generation error: ' . $e->getMessage());
                // Continue with success message even if PDF generation fails
            }
        }
        
        $success = "Request #$requestId has been $status successfully.";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get pending requests
$pendingRequests = $db->fetchAll(
    "SELECT cr.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name, r.email, r.contact_number
     FROM certificate_requests cr
     JOIN residents r ON cr.resident_id = r.id
     WHERE cr.status IN ('Pending', 'Processing')
     ORDER BY cr.request_date ASC",
    []
);

// Get recent processed requests
$recentRequests = $db->fetchAll(
    "SELECT cr.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name, r.email
     FROM certificate_requests cr
     JOIN residents r ON cr.resident_id = r.id
     WHERE cr.status IN ('Approved', 'Rejected', 'Completed')
     ORDER BY cr.processed_date DESC
     LIMIT 10",
    []
);

// Get request history for audit trail
$requestHistory = [];
if (isset($_GET['view_history']) && $_GET['view_history']) {
    $requestId = (int)$_GET['view_history'];
    $requestHistory = $db->fetchAll(
        "SELECT rh.*, CONCAT(u.first_name, ' ', u.last_name) as performed_by_name
         FROM request_history rh
         JOIN users u ON rh.performed_by = u.id
         WHERE rh.request_id = ?
         ORDER BY rh.performed_at DESC",
        [$requestId]
    );
    
    // Handle AJAX request for audit trail
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        if (empty($requestHistory)) {
            echo '<div class="text-center py-4">
                    <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                    <h6 class="text-muted">No audit trail found</h6>
                    <p class="text-muted">This request has no history yet.</p>
                  </div>';
        } else {
            echo '<div class="audit-trail">';
            echo '<h6 class="mb-3"><i class="fas fa-history me-2"></i>Request History</h6>';
            echo '<div class="timeline">';
            
            foreach ($requestHistory as $history) {
                $statusColor = '';
                switch ($history['status_to']) {
                    case 'Approved': $statusColor = 'success'; break;
                    case 'Rejected': $statusColor = 'danger'; break;
                    case 'Processing': $statusColor = 'warning'; break;
                    case 'Completed': $statusColor = 'primary'; break;
                    default: $statusColor = 'secondary';
                }
                
                echo '<div class="timeline-item">';
                echo '<div class="timeline-marker bg-' . $statusColor . '"></div>';
                echo '<div class="timeline-content">';
                echo '<div class="d-flex justify-content-between align-items-start">';
                echo '<div>';
                echo '<h6 class="mb-1">' . ucfirst($history['action']) . ' Request</h6>';
                echo '<p class="mb-1 text-muted">Status changed from <strong>' . htmlspecialchars($history['status_from']) . '</strong> to <strong>' . htmlspecialchars($history['status_to']) . '</strong></p>';
                if (!empty($history['remarks'])) {
                    echo '<p class="mb-1"><strong>Remarks:</strong> ' . htmlspecialchars($history['remarks']) . '</p>';
                }
                echo '</div>';
                echo '<small class="text-muted">' . formatDate($history['performed_at'], 'M j, Y g:i A') . '</small>';
                echo '</div>';
                echo '<div class="mt-2">';
                echo '<small class="text-muted"><i class="fas fa-user me-1"></i>By: ' . htmlspecialchars($history['performed_by_name']) . '</small>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        exit;
    }
}

// Generate CSRF token
$csrfToken = $auth->generateCSRFToken();

$page_title = 'Process Certificate Requests | Admin Dashboard | e-Barangay ni Kap';
$page_description = 'Admin interface for processing certificate requests. Review, approve, reject, and manage certificate applications for Barangay San Joaquin residents with comprehensive tracking and audit trail.';
$page_keywords = 'admin dashboard, process requests, certificate approval, request management, admin interface, barangay administration';
$page_author = 'Barangay San Joaquin Administration';
$page_canonical = 'https://ebarangay-ni-kap.com/modules/admin/process-requests.php';
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
<body class="admin-processing-page">
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
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($user['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="process-requests.php">
                                <i class="fas fa-tasks"></i> Process Requests
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
                    <i class="fas fa-tasks me-3"></i>Process Certificate Requests
                </h1>
                <p class="page-subtitle">Review and process pending certificate requests</p>
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

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                            <h5 class="card-title"><?php echo count(array_filter($pendingRequests, function($r) { return $r['status'] === 'Pending'; })); ?></h5>
                            <p class="card-text">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-cogs fa-2x text-primary mb-2"></i>
                            <h5 class="card-title"><?php echo count(array_filter($pendingRequests, function($r) { return $r['status'] === 'Processing'; })); ?></h5>
                            <p class="card-text">Processing</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h5 class="card-title"><?php echo count(array_filter($recentRequests, function($r) { return $r['status'] === 'Completed'; })); ?></h5>
                            <p class="card-text">Completed Today</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                            <h5 class="card-title"><?php echo count(array_filter($recentRequests, function($r) { return $r['status'] === 'Rejected'; })); ?></h5>
                            <p class="card-text">Rejected Today</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Pending Requests
                                <span class="badge bg-warning ms-2"><?php echo count($pendingRequests); ?></span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($pendingRequests)): ?>
                                <div class="processing-table">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Resident</th>
                                                <th>Certificate Type</th>
                                                <th>Purpose</th>
                                                <th>Date Requested</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingRequests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo $request['id']; ?></strong>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($request['resident_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($request['certificate_type']); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate request-purpose" 
                                                             data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($request['purpose']); ?>">
                                                            <?php echo htmlspecialchars($request['purpose']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php echo formatDate($request['request_date'], 'M j, Y g:i A'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="request-status status-<?php echo strtolower($request['status']); ?>">
                                                            <?php echo $request['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-view btn-sm" 
                                                                    onclick="viewRequestDetails(<?php echo $request['id']; ?>)"
                                                                    data-bs-toggle="tooltip" title="View details">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            
                                                            <?php if ($request['status'] === 'Pending'): ?>
                                                                <button class="btn btn-approve btn-sm" 
                                                                        onclick="processRequest(<?php echo $request['id']; ?>, 'approve')"
                                                                        data-bs-toggle="tooltip" title="Approve request">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button class="btn btn-reject btn-sm" 
                                                                        onclick="processRequest(<?php echo $request['id']; ?>, 'reject')"
                                                                        data-bs-toggle="tooltip" title="Reject request">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            <?php elseif ($request['status'] === 'Approved'): ?>
                                                                <button class="btn btn-success btn-sm" 
                                                                        onclick="processRequest(<?php echo $request['id']; ?>, 'complete')"
                                                                        data-bs-toggle="tooltip" title="Mark as completed">
                                                                    <i class="fas fa-check-double"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h5 class="text-success">No pending requests!</h5>
                                    <p class="text-muted">All certificate requests have been processed.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Processed Requests -->
            <?php if (!empty($recentRequests)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>Recently Processed
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="processing-table">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Resident</th>
                                                <th>Certificate Type</th>
                                                <th>Status</th>
                                                <th>Processed Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentRequests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo $request['id']; ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($request['resident_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($request['certificate_type']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="request-status status-<?php echo strtolower($request['status']); ?>">
                                                            <?php echo $request['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo formatDate($request['processed_date'], 'M j, Y g:i A'); ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-view btn-sm" 
                                                                    onclick="viewRequestDetails(<?php echo $request['id']; ?>)"
                                                                    data-bs-toggle="tooltip" title="View details">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-info btn-sm" 
                                                                    onclick="viewRequestHistory(<?php echo $request['id']; ?>)"
                                                                    data-bs-toggle="tooltip" title="View audit trail">
                                                                <i class="fas fa-history"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Request Processing Modal -->
    <div class="modal fade" id="processModal" tabindex="-1" aria-labelledby="processModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content request-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="processModalLabel">Process Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="processForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="request_id" id="processRequestId">
                        <input type="hidden" name="action" id="processAction">
                        
                        <div class="form-group">
                            <label for="remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                                      placeholder="Add any remarks or notes about this request..."
                                      data-bs-toggle="tooltip" title="Add remarks for the resident"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="processMessage">Processing request...</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="processSubmitBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

    <!-- Request History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content request-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyModalLabel">
                        <i class="fas fa-history me-2"></i>Request Audit Trail
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="historyModalBody">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading audit trail...</p>
                    </div>
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
        "name": "Process Certificate Requests",
        "description": "Admin interface for processing certificate requests. Review, approve, reject, and manage certificate applications for Barangay San Joaquin residents with comprehensive tracking and audit trail.",
        "url": "https://ebarangay-ni-kap.com/modules/admin/process-requests.php",
        "mainEntity": {
            "@type": "Service",
            "name": "Certificate Request Processing",
            "description": "Administrative interface for processing certificate requests",
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
                    "name": "Admin Dashboard",
                    "item": "https://ebarangay-ni-kap.com/admin/dashboard.php"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "Process Requests",
                    "item": "https://ebarangay-ni-kap.com/modules/admin/process-requests.php"
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

        // Process request
        function processRequest(requestId, action) {
            document.getElementById('processRequestId').value = requestId;
            document.getElementById('processAction').value = action;
            
            const actionText = action.charAt(0).toUpperCase() + action.slice(1);
            document.getElementById('processMessage').textContent = `Are you sure you want to ${action} this request?`;
            document.getElementById('processSubmitBtn').textContent = actionText;
            
            const modal = new bootstrap.Modal(document.getElementById('processModal'));
            modal.show();
        }

        // View request details
        function viewRequestDetails(requestId) {
            // This would typically make an AJAX call to get detailed information
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
                        <span class="detail-value">Pending</span>
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

        // Auto-refresh page after successful processing
        <?php if ($success): ?>
        setTimeout(function() {
            location.reload();
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html> 