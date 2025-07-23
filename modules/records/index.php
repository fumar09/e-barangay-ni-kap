<?php
session_start();
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit();
}

$user = $auth->getCurrentUser();
$userRole = $user['role'];

if (!in_array($userRole, ['admin', 'staff'])) {
    header('Location: ../../index.html');
    exit();
}

// Backend logic for records management
// Resident count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM residents WHERE status = 'active'");
$stmt->execute();
$residentCount = $stmt->fetch();

// Family count
$stmt = $conn->prepare("SELECT COUNT(DISTINCT family_id) as total FROM residents WHERE status = 'active'");
$stmt->execute();
$familyCount = $stmt->fetch();

// Active cases
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM blotter_reports WHERE status = 'active'");
$stmt->execute();
$activeCases = $stmt->fetch();

// Health records
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM health_records");
$stmt->execute();
$healthRecords = $stmt->fetch();

// Recent activities
$stmt = $conn->prepare("
    SELECT 'resident' as type, 'New resident registered' as description, created_at 
    FROM residents 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'blotter' as type, 'New blotter report filed' as description, created_at 
    FROM blotter_reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute();
$activities = $stmt->fetchAll(); 