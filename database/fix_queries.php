<?php
/**
 * Database Query Fix Script
 * Identifies and fixes common database query issues
 */

echo "Database Query Fix Script\n";
echo "========================\n\n";

// List of files that need fixing
$files_to_fix = [
    'modules/admin/process-requests.php',
    'modules/certificates/track.php',
    'modules/certificates/download.php',
    'modules/residents/export.php',
    'modules/residents/index.php',
    'includes/classes/EmailNotifier.php',
    'includes/classes/NotificationManager.php',
    'includes/classes/CertificateGenerator.php'
];

echo "Files that need query fixes:\n";
foreach ($files_to_fix as $file) {
    echo "- $file\n";
}

echo "\nCommon Issues Found:\n";
echo "1. certificate_requests JOIN users ON resident_id (should be JOIN residents)\n";
echo "2. announcements JOIN users ON author_id (should be created_by)\n";
echo "3. Missing proper table relationships\n\n";

echo "Manual Fixes Required:\n\n";

echo "1. In modules/admin/process-requests.php:\n";
echo "   Change: JOIN users u ON cr.resident_id = u.id\n";
echo "   To: JOIN residents r ON cr.resident_id = r.id\n";
echo "   And use: CONCAT(r.first_name, ' ', r.last_name) as resident_name\n\n";

echo "2. In modules/certificates/track.php:\n";
echo "   Change: JOIN users u ON cr.resident_id = u.id\n";
echo "   To: JOIN residents r ON cr.resident_id = r.id\n";
echo "   And use: CONCAT(r.first_name, ' ', r.last_name) as resident_name\n\n";

echo "3. In modules/certificates/download.php:\n";
echo "   Change: JOIN users u ON cr.resident_id = u.id\n";
echo "   To: JOIN residents r ON cr.resident_id = r.id\n";
echo "   And use: CONCAT(r.first_name, ' ', r.last_name) as resident_name\n\n";

echo "4. In modules/residents/export.php:\n";
echo "   Change: LEFT JOIN users u ON r.user_id = u.id\n";
echo "   To: Use resident data directly (r.first_name, r.last_name)\n\n";

echo "5. In modules/residents/index.php:\n";
echo "   Change: LEFT JOIN users u ON r.user_id = u.id\n";
echo "   To: Use resident data directly (r.first_name, r.last_name)\n\n";

echo "6. In includes/classes/EmailNotifier.php:\n";
echo "   Change: JOIN users u ON cr.resident_id = u.id\n";
echo "   To: JOIN residents r ON cr.resident_id = r.id\n";
echo "   And use: r.email for email address\n\n";

echo "7. In includes/classes/NotificationManager.php:\n";
echo "   Change: JOIN users u ON cr.resident_id = u.id\n";
echo "   To: JOIN residents r ON cr.resident_id = r.id\n";
echo "   And use: r.user_id for notifications\n\n";

echo "8. In includes/classes/CertificateGenerator.php:\n";
echo "   Change: JOIN users u ON cr.resident_id = u.id\n";
echo "   To: JOIN residents r ON cr.resident_id = r.id\n";
echo "   And use: CONCAT(r.first_name, ' ', r.last_name) as resident_name\n\n";

echo "Correct Query Patterns:\n";
echo "======================\n\n";

echo "For certificate requests with resident names:\n";
echo "SELECT cr.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name\n";
echo "FROM certificate_requests cr\n";
echo "JOIN residents r ON cr.resident_id = r.id\n";
echo "WHERE cr.status = 'Pending'\n\n";

echo "For announcements with author names:\n";
echo "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as author_name\n";
echo "FROM announcements a\n";
echo "JOIN users u ON a.created_by = u.id\n";
echo "WHERE a.is_published = 1\n\n";

echo "For residents with user info (if user_id exists):\n";
echo "SELECT r.*, u.username, u.email as user_email\n";
echo "FROM residents r\n";
echo "LEFT JOIN users u ON r.user_id = u.id\n\n";

echo "For certificate requests with processed by info:\n";
echo "SELECT cr.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name,\n";
echo "       CONCAT(u.first_name, ' ', u.last_name) as processed_by_name\n";
echo "FROM certificate_requests cr\n";
echo "JOIN residents r ON cr.resident_id = r.id\n";
echo "LEFT JOIN users u ON cr.processed_by = u.id\n\n";

echo "Database Schema Relationships:\n";
echo "=============================\n\n";

echo "certificate_requests:\n";
echo "- resident_id → residents.id\n";
echo "- processed_by → users.id\n\n";

echo "residents:\n";
echo "- user_id → users.id (optional, can be NULL)\n";
echo "- purok_id → puroks.id\n";
echo "- family_id → family_records.id\n\n";

echo "announcements:\n";
echo "- created_by → users.id\n\n";

echo "blotter_reports:\n";
echo "- complainant_id → residents.id\n";
echo "- respondent_id → residents.id\n";
echo "- created_by → users.id\n\n";

echo "health_records:\n";
echo "- resident_id → residents.id\n";
echo "- created_by → users.id\n\n";

echo "To fix all issues, update the queries in the listed files\n";
echo "according to the patterns shown above.\n\n";

echo "After fixing, test the system by:\n";
echo "1. Logging in with different user roles\n";
echo "2. Accessing dashboard pages\n";
echo "3. Viewing certificate requests\n";
echo "4. Checking announcements\n";
echo "5. Testing resident management\n";
?> 