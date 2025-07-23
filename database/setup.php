<?php
/**
 * e-Barangay ni Kap - Database Setup Script
 * Created: July 21, 2025
 */

// Database configuration
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'dbname' => 'ebarangay_ni_kap',
    'charset' => 'utf8mb4'
];

echo "==========================================\n";
echo "e-Barangay ni Kap - Database Setup\n";
echo "==========================================\n\n";

try {
    // Connect to MySQL server
    echo "Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host={$config['host']};charset={$config['charset']}", $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to MySQL server successfully\n\n";

    // Read SQL file
    echo "Reading database schema file...\n";
    $sqlFile = __DIR__ . '/ebarangay_complete_schema.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ SQL file loaded successfully\n\n";

    // Split SQL into individual statements
    echo "Preparing SQL statements...\n";
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    echo "✓ Found " . count($statements) . " SQL statements\n\n";

    // Execute statements
    echo "Executing database setup...\n";
    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ Error executing statement: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✓ Database setup completed\n";
    echo "  - Successful statements: $successCount\n";
    echo "  - Failed statements: $errorCount\n\n";

    // Verify installation
    echo "Verifying installation...\n";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['dbname']}'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database '{$config['dbname']}' created successfully\n";
    } else {
        throw new Exception("Database creation failed");
    }

    // Connect to the new database
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Found " . count($tables) . " tables\n";

    // Check user accounts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Found $userCount user accounts\n";

    // Check sample data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM residents");
    $residentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Found $residentCount sample residents\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_requests");
    $requestCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Found $requestCount sample certificate requests\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM blotter_reports");
    $blotterCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Found $blotterCount sample blotter cases\n";

    // Test user accounts
    echo "\nTesting user accounts...\n";
    $testUsers = [
        ['email' => 'admin@ebarangay.com', 'role' => 'Administrator'],
        ['email' => 'staff@ebarangay.com', 'role' => 'Staff'],
        ['email' => 'purok@ebarangay.com', 'role' => 'Purok Leader'],
        ['email' => 'resident@ebarangay.com', 'role' => 'Resident']
    ];

    foreach ($testUsers as $user) {
        $stmt = $pdo->prepare("SELECT u.username, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->execute([$user['email']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "✓ {$user['email']} - {$result['role_name']} (username: {$result['username']})\n";
        } else {
            echo "✗ {$user['email']} - Not found\n";
        }
    }

    // Check triggers
    echo "\nChecking database triggers...\n";
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Found " . count($triggers) . " triggers\n";

    // Check views
    echo "\nChecking database views...\n";
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Found " . count($views) . " views\n";

    // Check stored procedures
    echo "\nChecking stored procedures...\n";
    $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = '{$config['dbname']}'");
    $procedures = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Found " . count($procedures) . " stored procedures\n";

    echo "\n==========================================\n";
    echo "SETUP COMPLETED SUCCESSFULLY!\n";
    echo "==========================================\n\n";

    echo "Database Information:\n";
    echo "- Database Name: {$config['dbname']}\n";
    echo "- Character Set: {$config['charset']}\n";
    echo "- Tables: " . count($tables) . "\n";
    echo "- Users: $userCount\n";
    echo "- Sample Residents: $residentCount\n";
    echo "- Sample Requests: $requestCount\n";
    echo "- Sample Cases: $blotterCount\n";
    echo "- Triggers: " . count($triggers) . "\n";
    echo "- Views: " . count($views) . "\n";
    echo "- Stored Procedures: " . count($procedures) . "\n\n";

    echo "User Accounts (Password: admin123, staff123, purok123, resident123):\n";
    echo "- Administrator: admin@ebarangay.com\n";
    echo "- Staff: staff@ebarangay.com\n";
    echo "- Purok Leader: purok@ebarangay.com\n";
    echo "- Resident: resident@ebarangay.com\n\n";

    echo "Next Steps:\n";
    echo "1. Update your PHP configuration with the database settings\n";
    echo "2. Test the login functionality with the provided credentials\n";
    echo "3. Review the README.md file for detailed documentation\n";
    echo "4. Start using the e-Barangay ni Kap system!\n\n";

} catch (Exception $e) {
    echo "\n==========================================\n";
    echo "SETUP FAILED!\n";
    echo "==========================================\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Ensure MySQL server is running\n";
    echo "2. Verify database credentials\n";
    echo "3. Check if you have sufficient privileges\n";
    echo "4. Ensure the SQL file exists in the database directory\n\n";
    exit(1);
}
?> 