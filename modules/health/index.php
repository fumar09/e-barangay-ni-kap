<?php
require_once '../../includes/bootstrap.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!hasPermission('health_management')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access health records.');
    redirectToDashboard();
}

$db = getDatabaseConnection();

$search = $_GET['search'] ?? '';
$record_type_filter = $_GET['record_type'] ?? '';
$purok_filter = $_GET['purok_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR h.health_provider LIKE ? OR h.diagnosis LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($record_type_filter)) {
    $where_conditions[] = "h.record_type = ?";
    $params[] = $record_type_filter;
}

if (!empty($purok_filter)) {
    $where_conditions[] = "r.purok_id = ?";
    $params[] = $purok_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "h.record_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "h.record_date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

$query = "
    SELECT h.*, 
           r.first_name, r.last_name, r.birth_date,
           p.name as purok_name,
           u.username as created_by_name
    FROM health_records h
    LEFT JOIN residents r ON h.resident_id = r.id
    LEFT JOIN puroks p ON r.purok_id = p.id
    LEFT JOIN users u ON h.created_by = u.id
    WHERE $where_clause
    ORDER BY h.record_date DESC
    LIMIT 50
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_query = "
    SELECT 
        COUNT(*) as total_records,
        COUNT(CASE WHEN record_type = 'Immunization' THEN 1 END) as immunization_records,
        COUNT(CASE WHEN record_type = 'Medical Check-up' THEN 1 END) as medical_records,
        COUNT(CASE WHEN record_type = 'Dental' THEN 1 END) as dental_records,
        COUNT(CASE WHEN record_type = 'Prenatal' THEN 1 END) as prenatal_records,
        COUNT(CASE WHEN is_alert = 1 THEN 1 END) as alert_records
    FROM health_records
    WHERE record_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";

$stats_stmt = $db->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$immunization_due_query = "
    SELECT COUNT(*) as due_count
    FROM immunization_records
    WHERE next_due_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
    AND next_due_date >= NOW()
";

$immunization_due_stmt = $db->query($immunization_due_query);
$immunization_due = $immunization_due_stmt->fetch(PDO::FETCH_ASSOC);

$puroks_query = "SELECT id, name FROM puroks WHERE is_active = 1 ORDER BY name";
$puroks_stmt = $db->query($puroks_query);
$puroks = $puroks_stmt->fetchAll(PDO::FETCH_ASSOC);

$health_alerts_query = "
    SELECT * FROM health_alerts 
    WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY severity DESC, start_date DESC
    LIMIT 5
";

$health_alerts_stmt = $db->query($health_alerts_query);
$health_alerts = $health_alerts_stmt->fetchAll(PDO::FETCH_ASSOC); 