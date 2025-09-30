<?php
/**
 * Demo Accounts Seeder
 * Creates the demo accounts shown on the login page
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = \Database::getInstance();
    
    echo "Creating demo accounts...\n";
    
    // Demo accounts to create
    $demoAccounts = [
        [
            'email' => 'admin@kanho-ads.com',
            'password' => 'admin123',
            'role' => 'admin',
            'first_name' => '管理者',
            'last_name' => 'ユーザー'
        ],
        [
            'email' => 'user@kanho-ads.com',
            'password' => 'user123',
            'role' => 'user',
            'first_name' => 'テスト',
            'last_name' => 'ユーザー'
        ]
    ];
    
    $db->beginTransaction();
    
    foreach ($demoAccounts as $account) {
        // Check if account already exists
        $existing = $db->selectOne(
            'SELECT id FROM users WHERE email = ?', 
            [$account['email']]
        );
        
        if ($existing) {
            echo "Account {$account['email']} already exists, skipping...\n";
            continue;
        }
        
        // Hash the password
        $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
        
        // Insert user data
        $userData = [
            'email' => $account['email'],
            'password_hash' => $hashedPassword,
            'role' => $account['role'],
            'first_name' => $account['first_name'],
            'last_name' => $account['last_name'],
            'email_verified' => 1, // Mark as verified for demo purposes
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $db->insert('users', $userData);
        
        echo "✓ Created {$account['role']} account: {$account['email']} (ID: {$userId})\n";
    }
    
    $db->commit();
    
    echo "\nDemo accounts created successfully!\n";
    echo "You can now login with:\n";
    echo "- Admin: admin@kanho-ads.com / admin123\n";
    echo "- User: user@kanho-ads.com / user123\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    echo "Error creating demo accounts: " . $e->getMessage() . "\n";
    exit(1);
}