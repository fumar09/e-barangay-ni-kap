<?php
require_once '../../includes/bootstrap.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!hasPermission('resident_management')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access resident management.');
    redirectToDashboard();
}

$db = getDatabaseConnection();

$search = $_GET['search'] ?? '';
$purok_filter = $_GET['purok_id'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = ['r.is_active = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR r.contact_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($purok_filter)) {
    $where_conditions[] = "r.purok_id = ?";
    $params[] = $purok_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "r.is_active = ?";
    $params[] = $status_filter === 'active' ? 1 : 0;
}

$where_clause = implode(' AND ', $where_conditions);

$query = "
    SELECT r.*, p.name as purok_name, u.email, u.username
    FROM residents r
    LEFT JOIN puroks p ON r.purok_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE $where_clause
    ORDER BY r.last_name, r.first_name
    LIMIT 50
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_query = "
    SELECT 
        COUNT(*) as total_residents,
        COUNT(CASE WHEN r.gender = 'Male' THEN 1 END) as male_count,
        COUNT(CASE WHEN r.gender = 'Female' THEN 1 END) as female_count,
        COUNT(CASE WHEN r.is_head_of_family = 1 THEN 1 END) as family_heads,
        COUNT(CASE WHEN r.education IN ('College', 'Post Graduate') THEN 1 END) as college_educated
    FROM residents r
    WHERE r.is_active = 1
";

$stats_stmt = $db->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$puroks_query = "SELECT id, name FROM puroks WHERE is_active = 1 ORDER BY name";
$puroks_stmt = $db->query($puroks_query);
$puroks = $puroks_stmt->fetchAll(PDO::FETCH_ASSOC); 