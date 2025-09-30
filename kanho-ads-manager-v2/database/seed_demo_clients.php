<?php
/**
 * Demo Clients Seeder
 * Creates sample client data for testing
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = \Database::getInstance();
    
    echo "Creating demo clients...\n";
    
    // Sample clients to create
    $demoClients = [
        [
            'user_id' => 1, // admin user
            'company_name' => '株式会社Eコマース・ジャパン',
            'contact_person' => '田中 太郎',
            'email' => 'tanaka@ecommerce-japan.co.jp',
            'phone' => '03-1234-5678',
            'address' => '東京都千代田区神田1-2-3 オフィスビル5F',
            'tax_number' => '1234567890123',
            'status' => 'active',
            'tags' => json_encode(['EC', 'オンライン販売', 'BtoC']),
            'notes' => '主力商品は健康食品。Google Ads、Yahoo Adsの両方を運用中。月予算は200万円程度。'
        ],
        [
            'user_id' => 1,
            'company_name' => '美容クリニック グロー',
            'contact_person' => '佐藤 美香',
            'email' => 'info@glow-clinic.jp',
            'phone' => '06-9876-5432',
            'address' => '大阪府大阪市中央区心斎橋2-5-10 クリニックモール3F',
            'tax_number' => '9876543210987',
            'status' => 'active',
            'tags' => json_encode(['美容', 'クリニック', '医療']),
            'notes' => '美容整形・脱毛を中心としたクリニック。リスティング広告でお客様獲得に力を入れている。'
        ],
        [
            'user_id' => 1,
            'company_name' => 'テックスタートアップ株式会社',
            'contact_person' => '鈴木 健介',
            'email' => 'suzuki@techstartup.co.jp',
            'phone' => '03-2468-1357',
            'address' => '東京都渋谷区原宿3-15-7 スタートアップハブ8F',
            'tax_number' => '1357246810123',
            'status' => 'active',
            'tags' => json_encode(['IT', 'スタートアップ', 'SaaS']),
            'notes' => 'BtoB向けのSaaSプロダクトを展開。デジタルマーケティングに積極的で成長中。'
        ],
        [
            'user_id' => 2, // regular user
            'company_name' => '地域密着型レストラン まる',
            'contact_person' => '高橋 花子',
            'email' => 'takahashi@maru-restaurant.jp',
            'phone' => '075-333-4444',
            'address' => '京都府京都市中京区河原町通り三条下る 1F',
            'tax_number' => '',
            'status' => 'active',
            'tags' => json_encode(['飲食', 'レストラン', '地域密着']),
            'notes' => '京都の老舗和食レストラン。観光客向けのWeb集客を強化したいとのご相談。'
        ],
        [
            'user_id' => 1,
            'company_name' => 'フィットネスジム パワーアップ',
            'contact_person' => '山田 強志',
            'email' => 'yamada@powerup-gym.com',
            'phone' => '045-111-2222',
            'address' => '神奈川県横浜市港北区新横浜2-10-15 フィットネスビル2F',
            'tax_number' => '2468135791357',
            'status' => 'inactive',
            'tags' => json_encode(['フィットネス', 'ジム', '健康']),
            'notes' => '2023年12月に契約終了。会員数増加により広告予算を削減したため。'
        ]
    ];
    
    $db->beginTransaction();
    
    foreach ($demoClients as $client) {
        // Check if client already exists
        $existing = $db->selectOne(
            'SELECT id FROM clients WHERE company_name = ?', 
            [$client['company_name']]
        );
        
        if ($existing) {
            echo "Client {$client['company_name']} already exists, skipping...\n";
            continue;
        }
        
        // Insert client data
        $clientData = array_merge($client, [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $clientId = $db->insert('clients', $clientData);
        
        echo "✓ Created client: {$client['company_name']} (ID: {$clientId})\n";
    }
    
    $db->commit();
    
    echo "\nDemo clients created successfully!\n";
    echo "You can now view them in the client management section.\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    echo "Error creating demo clients: " . $e->getMessage() . "\n";
    exit(1);
}