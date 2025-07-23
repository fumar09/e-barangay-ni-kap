<?php
/**
 * Helper Functions
 * e-Barangay ni Kap
 * Created: July 15, 2025
 */

require_once __DIR__ . '/../config/constants.php';

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Philippine format)
 */
function validatePhoneNumber($phone) {
    // Remove spaces, dashes, and parentheses
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Check if it's a valid Philippine mobile number
    return preg_match('/^(\+63|0)9\d{9}$/', $phone);
}

/**
 * Format phone number for display
 */
function formatPhoneNumber($phone) {
    // Remove spaces, dashes, and parentheses
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Convert to +63 format if it starts with 0
    if (preg_match('/^09/', $phone)) {
        $phone = '+63' . substr($phone, 1);
    }
    
    return $phone;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * Generate certificate number
 */
function generateCertificateNumber($type, $id) {
    $prefix = strtoupper(substr($type, 0, 3));
    $date = date('Ymd');
    $sequence = str_pad($id, 4, '0', STR_PAD_LEFT);
    
    return $prefix . '-' . $date . '-' . $sequence;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'F j, Y') {
    if (!$date) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'F j, Y g:i A') {
    if (!$datetime) return '';
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Get time ago
 */
function timeAgo($datetime) {
    if (!$datetime) return '';
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $timeDiff = time() - $timestamp;
    
    if ($timeDiff < 60) {
        return 'Just now';
    } elseif ($timeDiff < 3600) {
        $minutes = floor($timeDiff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($timeDiff < 86400) {
        $hours = floor($timeDiff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($timeDiff < 2592000) {
        $days = floor($timeDiff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($timeDiff < 31536000) {
        $months = floor($timeDiff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($timeDiff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Get file size in human readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Check if file is image
 */
function isImage($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_IMAGE_TYPES);
}

/**
 * Check if file is document
 */
function isDocument($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_DOCUMENT_TYPES);
}

/**
 * Get file icon based on type
 */
function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fas fa-file-image';
        default:
            return 'fas fa-file';
    }
}

/**
 * Create pagination links
 */
function createPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - floor(MAX_PAGES_SHOWN / 2));
    $end = min($totalPages, $start + MAX_PAGES_SHOWN - 1);
    
    if ($end - $start + 1 < MAX_PAGES_SHOWN) {
        $start = max(1, $end - MAX_PAGES_SHOWN + 1);
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'Pending' => 'badge bg-warning',
        'Processing' => 'badge bg-info',
        'Approved' => 'badge bg-success',
        'Rejected' => 'badge bg-danger',
        'Completed' => 'badge bg-primary',
        'Open' => 'badge bg-warning',
        'Under Investigation' => 'badge bg-info',
        'Resolved' => 'badge bg-success',
        'Closed' => 'badge bg-secondary',
        'Upcoming' => 'badge bg-info',
        'Ongoing' => 'badge bg-primary',
        'Cancelled' => 'badge bg-danger'
    ];
    
    $class = $badges[$status] ?? 'badge bg-secondary';
    return '<span class="' . $class . '">' . $status . '</span>';
}

/**
 * Get priority badge HTML
 */
function getPriorityBadge($priority) {
    $badges = [
        'Low' => 'badge bg-success',
        'Medium' => 'badge bg-warning',
        'High' => 'badge bg-danger',
        'Urgent' => 'badge bg-danger'
    ];
    
    $class = $badges[$priority] ?? 'badge bg-secondary';
    return '<span class="' . $class . '">' . $priority . '</span>';
}

/**
 * Log error message
 */
function logError($message, $context = []) {
    $logFile = APP_ROOT . '/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    
    $logMessage = "[$timestamp] ERROR: $message$contextStr" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Log info message
 */
function logInfo($message, $context = []) {
    if (!DEBUG_MODE) return;
    
    $logFile = APP_ROOT . '/logs/info.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    
    $logMessage = "[$timestamp] INFO: $message$contextStr" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    $message = $_SESSION['message'] ?? '';
    $type = $_SESSION['message_type'] ?? 'info';
    
    unset($_SESSION['message'], $_SESSION['message_type']);
    
    return ['message' => $message, 'type' => $type];
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    
    if (!empty($flash['message'])) {
        $alertClass = 'alert-' . $flash['type'];
        return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                    ' . $flash['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    
    return '';
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $errors = [];
    
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    return $errors;
}

/**
 * Clean filename for upload
 */
function cleanFilename($filename) {
    // Remove special characters and spaces
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    return $filename;
}

/**
 * Get dashboard statistics - eliminates duplicate queries
 */
function getDashboardStats($type, $params = []) {
    $db = getDB();
    
    switch ($type) {
        case 'admin':
            return [
                'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
                'total_residents' => $db->fetchOne("SELECT COUNT(*) as count FROM residents")['count'],
                'pending_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests WHERE status = 'Pending'")['count'],
                'total_announcements' => $db->fetchOne("SELECT COUNT(*) as count FROM announcements WHERE is_published = 1")['count']
            ];
            
        case 'staff':
            return [
                'total_residents' => $db->fetchOne("SELECT COUNT(*) as count FROM residents")['count'],
                'total_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests")['count'],
                'pending_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests WHERE status = 'Pending'")['count'],
                'completed_today' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests WHERE status = 'Completed' AND DATE(completed_date) = CURDATE()")['count']
            ];
            
        case 'resident':
            if (!isset($params['resident_id']) || !isset($params['user_id'])) {
                throw new InvalidArgumentException('Resident ID and User ID required for resident stats');
            }
            return [
                'total_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests WHERE resident_id = ?", [$params['resident_id']])['count'],
                'pending_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests WHERE resident_id = ? AND status = 'Pending'", [$params['resident_id']])['count'],
                'completed_certificates' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests WHERE resident_id = ? AND status = 'Completed'", [$params['resident_id']])['count'],
                'feedback_submitted' => $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE user_id = ?", [$params['user_id']])['count']
            ];
            
        case 'purok':
            if (!isset($params['purok_id'])) {
                throw new InvalidArgumentException('Purok ID required for purok stats');
            }
            return [
                'total_residents' => $db->fetchOne("SELECT COUNT(*) as count FROM residents WHERE purok_id = ?", [$params['purok_id']])['count'],
                'households' => $db->fetchOne("SELECT COUNT(*) as count FROM residents WHERE purok_id = ? AND is_head_of_family = 1", [$params['purok_id']])['count'],
                'total_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM certificate_requests cr JOIN residents r ON cr.resident_id = r.id WHERE r.purok_id = ?", [$params['purok_id']])['count'],
                'feedback_count' => $db->fetchOne("SELECT COUNT(*) as count FROM feedback f LEFT JOIN residents r ON f.user_id = r.user_id WHERE r.purok_id = ?", [$params['purok_id']])['count']
            ];
            
        default:
            throw new InvalidArgumentException('Invalid dashboard type');
    }
}

/**
 * Get resident photo URL
 */
function getResidentPhoto($resident_id) {
    $photo_path = 'assets/uploads/photos/resident_' . $resident_id . '.jpg';
    $default_photo = 'assets/images/default-avatar.png';
    
    if (file_exists($photo_path)) {
        return $photo_path;
    }
    
    return $default_photo;
}

/**
 * Calculate age from birth date
 */
function calculateAge($birth_date) {
    if (!$birth_date) return 0;
    
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $age = $today->diff($birth);
    
    return $age->y;
}

/**
 * Get age group from age
 */
function getAgeGroup($age) {
    if ($age < 15) return '0-14';
    if ($age < 65) return '15-64';
    return '65+';
}

/**
 * Format currency for display
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Get purok name by ID
 */
function getPurokName($purok_id) {
    global $db;
    
    if (!$purok_id) return 'Not assigned';
    
    $query = "SELECT name FROM puroks WHERE id = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$purok_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['name'] : 'Not assigned';
}

/**
 * Get family name by ID
 */
function getFamilyName($family_id) {
    global $db;
    
    if (!$family_id) return 'Not assigned';
    
    $query = "SELECT family_name FROM family_records WHERE id = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$family_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['family_name'] : 'Not assigned';
}

/**
 * Get resident full name
 */
function getResidentFullName($resident_id) {
    global $db;
    
    $query = "SELECT first_name, last_name, middle_name, suffix FROM residents WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$resident_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) return 'Unknown';
    
    $name = trim($result['first_name'] . ' ' . $result['last_name']);
    if ($result['middle_name']) {
        $name = trim($result['first_name'] . ' ' . $result['middle_name'] . ' ' . $result['last_name']);
    }
    if ($result['suffix']) {
        $name .= ' ' . $result['suffix'];
    }
    
    return $name;
}

/**
 * Get blotter case status badge
 */
function getBlotterStatusBadge($status) {
    $badges = [
        'Pending' => '<span class="badge bg-warning">Pending</span>',
        'Under Investigation' => '<span class="badge bg-info">Under Investigation</span>',
        'Scheduled for Mediation' => '<span class="badge bg-primary">Scheduled for Mediation</span>',
        'Resolved' => '<span class="badge bg-success">Resolved</span>',
        'Dismissed' => '<span class="badge bg-danger">Dismissed</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

/**
 * Get health record type badge
 */
function getHealthRecordTypeBadge($type) {
    $badges = [
        'Immunization' => '<span class="badge bg-success">Immunization</span>',
        'Medical Check-up' => '<span class="badge bg-info">Medical Check-up</span>',
        'Dental' => '<span class="badge bg-warning">Dental</span>',
        'Prenatal' => '<span class="badge bg-danger">Prenatal</span>',
        'Family Planning' => '<span class="badge bg-primary">Family Planning</span>',
        'Emergency' => '<span class="badge bg-danger">Emergency</span>',
        'Other' => '<span class="badge bg-secondary">Other</span>'
    ];
    
    return $badges[$type] ?? '<span class="badge bg-secondary">' . $type . '</span>';
}

/**
 * Check if immunization is due
 */
function isImmunizationDue($next_due_date) {
    if (!$next_due_date) return false;
    
    $due_date = new DateTime($next_due_date);
    $today = new DateTime();
    
    return $due_date <= $today;
}

/**
 * Get days until immunization due
 */
function getDaysUntilDue($next_due_date) {
    if (!$next_due_date) return null;
    
    $due_date = new DateTime($next_due_date);
    $today = new DateTime();
    $diff = $today->diff($due_date);
    
    return $diff->invert ? -$diff->days : $diff->days;
}

?> 