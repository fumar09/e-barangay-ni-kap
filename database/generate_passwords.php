<?php
/**
 * Password Hash Generator
 * Generates bcrypt hashes for the user accounts
 */

$passwords = [
    'admin123',
    'staff123', 
    'purok123',
    'resident123'
];

echo "Generated Password Hashes:\n";
echo "==========================\n\n";

foreach ($passwords as $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    echo "Password: $password\n";
    echo "Hash: $hash\n";
    echo "Verified: " . (password_verify($password, $hash) ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

echo "\nSQL INSERT statements:\n";
echo "======================\n\n";

$users = [
    ['admin', 'admin@ebarangay.com', 'admin123', 'System', 'Administrator', 1],
    ['staff', 'staff@ebarangay.com', 'staff123', 'Barangay', 'Staff', 2],
    ['purok', 'purok@ebarangay.com', 'purok123', 'Purok', 'Leader', 3],
    ['resident', 'resident@ebarangay.com', 'resident123', 'Sample', 'Resident', 4]
];

echo "INSERT INTO users (username, email, password, first_name, last_name, role_id, is_active) VALUES\n";

$inserts = [];
foreach ($users as $user) {
    $hash = password_hash($user[2], PASSWORD_BCRYPT, ['cost' => 12]);
    $inserts[] = "('{$user[0]}', '{$user[1]}', '$hash', '{$user[3]}', '{$user[4]}', {$user[5]}, 1)";
}

echo implode(",\n", $inserts) . ";";
?> 