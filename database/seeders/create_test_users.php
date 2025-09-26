<?php
// filepath: database/seeders/create_test_users.php
require_once __DIR__ . '/../../src/config/db.php';

// Test users data
$users = [
    [
        'username' => 'admin',
        'email' => 'admin@company.com',
        'password' => 'password123',
        'first_name' => 'System',
        'last_name' => 'Administrator',
        'role' => 'admin',
        'department' => 'IT'
    ],
    [
        'username' => 'manager',
        'email' => 'manager@company.com',
        'password' => 'password123',
        'first_name' => 'John',
        'last_name' => 'Manager',
        'role' => 'org_admin',
        'department' => 'Management'
    ],
    [
        'username' => 'employee',
        'email' => 'employee@company.com',
        'password' => 'password123',
        'first_name' => 'Jane',
        'last_name' => 'Employee',
        'role' => 'employee',
        'department' => 'Sales'
    ]
];

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, first_name, last_name, role, department, is_active) 
        VALUES (:username, :email, :password_hash, :first_name, :last_name, :role, :department, 1)
    ");
    
    foreach ($users as $user) {
        $stmt->execute([
            'username' => $user['username'],
            'email' => $user['email'],
            'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'department' => $user['department']
        ]);
        echo "Created user: {$user['username']}\n";
    }
    
    echo "All test users created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error creating users: " . $e->getMessage() . "\n";
}
?>