<?php
/**
 * Demo Ad Accounts Seeder
 * Creates sample ad account data for testing
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = \Database::getInstance();
    
    echo "Creating demo ad accounts...\n";
    
    // Get existing clients
    $clients = $db->select('SELECT id, company_name FROM clients LIMIT 5');
    
    if (empty($clients)) {
        echo "No clients found. Please run seed_demo_clients.php first.\n";
        exit(1);
    }
    
    // Sample ad accounts to create
    $demoAccounts = [
        [
            'client_id' => $clients[0]['id'], // 株式会社Eコマース・ジャパン
            'platform' => 'google',
            'account_id' => '123-456-7890',
            'account_name' => 'Eコマース・ジャパン - 検索広告',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'sync_enabled' => 1,
            'status' => 'active'
        ],
        [
            'client_id' => $clients[0]['id'], // 株式会社Eコマース・ジャパン
            'platform' => 'yahoo',
            'account_id' => '9876543210',
            'account_name' => 'Eコマース・ジャパン - ディスプレイ広告',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'sync_enabled' => 1,
            'status' => 'active'
        ],
        [
            'client_id' => $clients[1]['id'], // 美容クリニック グロー
            'platform' => 'google',
            'account_id' => '234-567-8901',
            'account_name' => 'グロークリニック - 美容広告',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'sync_enabled' => 1,
            'status' => 'active'
        ],
        [
            'client_id' => $clients[2]['id'], // テックスタートアップ株式会社
            'platform' => 'google',
            'account_id' => '345-678-9012',
            'account_name' => 'テックスタートアップ - BtoB SaaS',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'sync_enabled' => 1,
            'status' => 'active'
        ],
        [
            'client_id' => $clients[2]['id'], // テックスタートアップ株式会社
            'platform' => 'yahoo',
            'account_id' => '8765432109',
            'account_name' => 'テックスタートアップ - リターゲティング',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'sync_enabled' => 0,
            'status' => 'inactive'
        ],
        [
            'client_id' => $clients[3]['id'], // 地域密着型レストラン まる
            'platform' => 'google',
            'account_id' => '456-789-0123',
            'account_name' => 'レストランまる - 地域集客',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'sync_enabled' => 1,
            'status' => 'inactive'
        ],
        [
            'client_id' => $clients[4]['id'], // フィットネスジム パワーアップ
            'platform' => 'yahoo',
            'account_id' => '7654321098',
            'account_name' => 'パワーアップ - 会員募集',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'sync_enabled' => 0,
            'status' => 'suspended'
        ]
    ];
    
    $db->beginTransaction();
    
    foreach ($demoAccounts as $account) {
        // Check if account already exists
        $existing = $db->selectOne(
            'SELECT id FROM ad_accounts WHERE platform = ? AND account_id = ?', 
            [$account['platform'], $account['account_id']]
        );
        
        if ($existing) {
            echo "Ad Account {$account['platform']}: {$account['account_id']} already exists, skipping...\n";
            continue;
        }
        
        // Set sync dates for active accounts
        if ($account['status'] === 'active') {
            $account['last_sync'] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 6) . ' hours'));
        }
        
        // Insert ad account data
        $accountData = array_merge($account, [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $accountId = $db->insert('ad_accounts', $accountData);
        
        $clientName = '';
        foreach ($clients as $client) {
            if ($client['id'] == $account['client_id']) {
                $clientName = $client['company_name'];
                break;
            }
        }
        
        echo "✓ Created ad account: {$account['account_name']} for {$clientName} (ID: {$accountId})\n";
    }
    
    $db->commit();
    
    echo "\nDemo ad accounts created successfully!\n";
    echo "Summary:\n";
    echo "- Google Ads accounts: 4\n";
    echo "- Yahoo Ads accounts: 3\n";
    echo "- Active accounts: 4\n";
    echo "- Inactive accounts: 2\n";
    echo "- Suspended accounts: 1\n";
    echo "\nYou can now view them in the ad accounts management section.\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    echo "Error creating demo ad accounts: " . $e->getMessage() . "\n";
    exit(1);
}