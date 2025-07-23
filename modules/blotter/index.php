<?php
require_once '../../includes/bootstrap.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!hasPermission('blotter_management')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access blotter management.');
    redirectToDashboard();
}

$db = getDatabaseConnection();

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$purok_filter = $_GET['purok_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.case_number LIKE ? OR b.incident_type LIKE ? OR b.incident_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if (!empty($purok_filter)) {
    $where_conditions[] = "b.purok_id = ?";
    $params[] = $purok_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(b.incident_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(b.incident_date) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

$query = "
    SELECT b.*, 
           p.name as purok_name,
           c.first_name as complainant_first_name, c.last_name as complainant_last_name,
           r.first_name as respondent_first_name, r.last_name as respondent_last_name,
           u.username as created_by_name
    FROM blotter_reports b
    LEFT JOIN puroks p ON b.purok_id = p.id
    LEFT JOIN residents c ON b.complainant_id = c.id
    LEFT JOIN residents r ON b.respondent_id = r.id
    LEFT JOIN users u ON b.created_by = u.id
    WHERE $where_clause
    ORDER BY b.incident_date DESC
    LIMIT 50
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$blotter_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_query = "
    SELECT 
        COUNT(*) as total_cases,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_cases,
        COUNT(CASE WHEN status = 'Under Investigation' THEN 1 END) as investigation_cases,
        COUNT(CASE WHEN status = 'Scheduled for Mediation' THEN 1 END) as mediation_cases,
        COUNT(CASE WHEN status = 'Resolved' THEN 1 END) as resolved_cases,
        COUNT(CASE WHEN status = 'Dismissed' THEN 1 END) as dismissed_cases
    FROM blotter_reports
    WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";

$stats_stmt = $db->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$puroks_query = "SELECT id, name FROM puroks WHERE is_active = 1 ORDER BY name";
$puroks_stmt = $db->query($puroks_query);
$puroks = $puroks_stmt->fetchAll(PDO::FETCH_ASSOC); 