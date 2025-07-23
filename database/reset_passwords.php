<?php
/**
 * Password Reset Script
 * Updates user passwords in the database with correct hashes
 */

require_once '../includes/config/database.php';

$db = getDB();

// User credentials to update
$users = [
    ['admin@ebarangay.com', 'admin123'],
    ['staff@ebarangay.com', 'staff123'],
    ['purok@ebarangay.com', 'purok123'],
    ['resident@ebarangay.com', 'resident123']
];

echo "Password Reset Script\n";
echo "====================\n\n";

try {
    foreach ($users as $user) {
        $email = $user[0];
        $password = $user[1];
        
        // Generate hash
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Update user password
        $result = $db->update('users', 
            ['password' => $hash], 
            'email = ?', 
            [$email]
        );
        
        if ($result > 0) {
            echo "✓ Updated password for: $email\n";
            echo "  Password: $password\n";
            echo "  Hash: $hash\n";
        } else {
            echo "✗ User not found: $email\n";
        }
        echo "---\n";
    }
    
    echo "\nPassword reset completed successfully!\n";
    echo "You can now login with the following credentials:\n\n";
    
    foreach ($users as $user) {
        echo "- Email: {$user[0]}\n";
        echo "  Password: {$user[1]}\n\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 