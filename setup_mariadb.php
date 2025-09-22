<?php

/**
 * MariaDB用セットアップスクリプト
 * データベースの作成とデモデータの投入
 */

// 環境変数の読み込み
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_NAME'] ?? 'kanho_adsmanager',
    'username' => $_ENV['DB_USER'] ?? 'kanho_adsmanager',
    'password' => $_ENV['DB_PASS'] ?? 'Kanho20200701'
];

echo "🚀 MariaDB広告管理システム セットアップを開始します...\n\n";
echo "📊 データベース情報:\n";
echo "   ホスト: {$dbConfig['host']}:{$dbConfig['port']}\n";
echo "   データベース: {$dbConfig['database']}\n";
echo "   ユーザー: {$dbConfig['username']}\n\n";

try {
    // MariaDBに接続
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    echo "✅ MariaDBサーバーに接続しました\n";
    
    // データベースを作成（存在しない場合）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ データベース '{$dbConfig['database']}' を作成しました\n";
    
    // データベースを選択
    $pdo->exec("USE `{$dbConfig['database']}`");
    
    // テーブル作成
    echo "\n📋 テーブルを作成しています...\n";
    
    // 管理者テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'operator') NOT NULL DEFAULT 'operator',
            is_active BOOLEAN DEFAULT TRUE,
            last_login_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ 管理者テーブルを作成しました\n";

    // クライアントテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clients (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(200) NOT NULL,
            contact_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            contract_start_date DATE NOT NULL,
            contract_end_date DATE,
            billing_day INT DEFAULT 25 COMMENT '請求締め日(月末基準)',
            payment_terms INT DEFAULT 30 COMMENT '支払い条件(日数)',
            is_active BOOLEAN DEFAULT TRUE,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_company_name (company_name),
            INDEX idx_email (email),
            INDEX idx_contract_dates (contract_start_date, contract_end_date),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ クライアントテーブルを作成しました\n";

    // 手数料設定テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fee_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            platform ENUM('google_ads', 'yahoo_display', 'yahoo_search') NOT NULL,
            fee_type ENUM('percentage', 'fixed', 'tiered') NOT NULL DEFAULT 'percentage',
            base_percentage DECIMAL(5,2) COMMENT '基本手数料率(%)',
            fixed_amount DECIMAL(10,2) COMMENT '固定手数料額',
            minimum_fee DECIMAL(10,2) COMMENT '最低手数料額',
            maximum_fee DECIMAL(10,2) COMMENT '最高手数料額',
            is_active BOOLEAN DEFAULT TRUE,
            effective_from DATE NOT NULL,
            effective_to DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_client_platform (client_id, platform),
            INDEX idx_effective_dates (effective_from, effective_to),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ 手数料設定テーブルを作成しました\n";

    // 広告アカウントテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ad_accounts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            platform ENUM('google_ads', 'yahoo_display', 'yahoo_search') NOT NULL,
            account_id VARCHAR(50) NOT NULL COMMENT 'プラットフォーム上のアカウントID',
            account_name VARCHAR(200) NOT NULL,
            currency_code CHAR(3) DEFAULT 'JPY',
            timezone VARCHAR(50) DEFAULT 'Asia/Tokyo',
            is_active BOOLEAN DEFAULT TRUE,
            last_sync_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            UNIQUE KEY unique_platform_account (platform, account_id),
            INDEX idx_client_platform (client_id, platform),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ 広告アカウントテーブルを作成しました\n";

    // 日次広告データテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS daily_ad_data (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ad_account_id INT UNSIGNED NOT NULL,
            date_value DATE NOT NULL,
            impressions BIGINT DEFAULT 0,
            clicks BIGINT DEFAULT 0,
            conversions INT DEFAULT 0,
            cost DECIMAL(12,2) DEFAULT 0 COMMENT '広告費（プラットフォーム実費）',
            reported_cost DECIMAL(12,2) DEFAULT 0 COMMENT '上乗せ後の報告費用',
            ctr DECIMAL(5,4) DEFAULT 0 COMMENT 'クリック率',
            cpc DECIMAL(8,2) DEFAULT 0 COMMENT '平均クリック単価',
            cpa DECIMAL(8,2) DEFAULT 0 COMMENT '獲得単価',
            conversion_rate DECIMAL(5,4) DEFAULT 0 COMMENT 'コンバージョン率',
            sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
            raw_data JSON COMMENT 'APIから取得した生データ',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
            UNIQUE KEY unique_account_date (ad_account_id, date_value),
            INDEX idx_date_range (date_value),
            INDEX idx_sync_status (sync_status),
            INDEX idx_account_date_range (ad_account_id, date_value)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ 日次広告データテーブルを作成しました\n";

    // 月次集計テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS monthly_summaries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            ad_account_id INT UNSIGNED NOT NULL,
            year_month CHAR(7) NOT NULL COMMENT 'YYYY-MM形式',
            total_cost DECIMAL(12,2) DEFAULT 0,
            total_reported_cost DECIMAL(12,2) DEFAULT 0,
            total_impressions BIGINT DEFAULT 0,
            total_clicks BIGINT DEFAULT 0,
            total_conversions INT DEFAULT 0,
            average_ctr DECIMAL(5,4) DEFAULT 0,
            average_cpc DECIMAL(8,2) DEFAULT 0,
            average_cpa DECIMAL(8,2) DEFAULT 0,
            average_conversion_rate DECIMAL(5,4) DEFAULT 0,
            calculated_fee DECIMAL(10,2) DEFAULT 0 COMMENT '計算された手数料',
            is_invoiced BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
            UNIQUE KEY unique_account_month (ad_account_id, year_month),
            INDEX idx_client_month (client_id, year_month),
            INDEX idx_is_invoiced (is_invoiced)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ 月次集計テーブルを作成しました\n";

    // 費用上乗せ設定テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cost_markups (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            ad_account_id INT UNSIGNED,
            markup_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
            markup_value DECIMAL(8,4) NOT NULL COMMENT '上乗せ率(%)または固定額',
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            effective_from DATE NOT NULL,
            effective_to DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
            INDEX idx_client_account (client_id, ad_account_id),
            INDEX idx_effective_dates (effective_from, effective_to),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ 費用上乗せ設定テーブルを作成しました\n";

    // デモデータの投入
    echo "\n📊 デモデータを投入しています...\n";
    
    // 管理者データ
    $pdo->exec("
        INSERT IGNORE INTO admins (name, email, password, role) VALUES
        ('システム管理者', 'admin@kanho-adsmanager.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin'),
        ('運用担当者', 'operator@kanho-adsmanager.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator')
    ");
    
    // クライアントデータ
    $pdo->exec("
        INSERT IGNORE INTO clients (company_name, contact_name, email, phone, address, contract_start_date, contract_end_date, billing_day, payment_terms) VALUES
        ('株式会社サンプル商事', '田中太郎', 'tanaka@sample-corp.co.jp', '03-1234-5678', '東京都港区サンプル1-2-3', '2024-01-01', '2024-12-31', 25, 30),
        ('有限会社テスト工業', '佐藤花子', 'sato@test-industry.co.jp', '06-9876-5432', '大阪府大阪市テスト区4-5-6', '2024-02-01', NULL, 20, 30),
        ('エクサンプル株式会社', '鈴木次郎', 'suzuki@example-inc.co.jp', '052-1111-2222', '愛知県名古屋市エクサンプル区7-8-9', '2024-03-01', '2025-02-28', 25, 45)
    ");
    
    // 手数料設定
    $pdo->exec("
        INSERT IGNORE INTO fee_settings (client_id, platform, fee_type, base_percentage, minimum_fee, effective_from) VALUES
        (1, 'google_ads', 'percentage', 20.00, 50000, '2024-01-01'),
        (1, 'yahoo_display', 'percentage', 20.00, 50000, '2024-01-01'),
        (1, 'yahoo_search', 'percentage', 20.00, 50000, '2024-01-01'),
        (2, 'google_ads', 'percentage', 15.00, 30000, '2024-02-01'),
        (2, 'yahoo_display', 'percentage', 15.00, 30000, '2024-02-01'),
        (3, 'google_ads', 'percentage', 18.00, 40000, '2024-03-01'),
        (3, 'yahoo_display', 'percentage', 18.00, 40000, '2024-03-01')
    ");
    
    // 広告アカウント
    $pdo->exec("
        INSERT IGNORE INTO ad_accounts (client_id, platform, account_id, account_name, currency_code, timezone) VALUES
        (1, 'google_ads', '123-456-7890', 'サンプル商事 Google広告', 'JPY', 'Asia/Tokyo'),
        (1, 'yahoo_display', 'YDN-1234567890', 'サンプル商事 Yahoo!ディスプレイ広告', 'JPY', 'Asia/Tokyo'),
        (1, 'yahoo_search', 'YSS-1234567890', 'サンプル商事 Yahoo!検索広告', 'JPY', 'Asia/Tokyo'),
        (2, 'google_ads', '987-654-3210', 'テスト工業 Google広告', 'JPY', 'Asia/Tokyo'),
        (2, 'yahoo_display', 'YDN-0987654321', 'テスト工業 Yahoo!ディスプレイ広告', 'JPY', 'Asia/Tokyo'),
        (3, 'google_ads', '555-666-7777', 'エクサンプル株式会社 Google広告', 'JPY', 'Asia/Tokyo'),
        (3, 'yahoo_display', 'YDN-5556667777', 'エクサンプル株式会社 Yahoo!ディスプレイ広告', 'JPY', 'Asia/Tokyo')
    ");
    
    // 費用上乗せ設定
    $pdo->exec("
        INSERT IGNORE INTO cost_markups (client_id, ad_account_id, markup_type, markup_value, description, effective_from) VALUES
        (1, 1, 'percentage', 5.0000, 'Google広告運用手数料として5%上乗せ', '2024-01-01'),
        (1, 2, 'percentage', 3.0000, 'Yahoo!ディスプレイ広告運用手数料として3%上乗せ', '2024-01-01'),
        (2, NULL, 'fixed', 10000.0000, 'テスト工業全アカウントに月額1万円固定上乗せ', '2024-02-01')
    ");
    
    // サンプル日次データ（直近30日分）
    echo "📈 日次データを生成しています...\n";
    
    $accountIds = [1, 2, 3, 4, 5, 6, 7]; // 7つのアカウント
    
    for ($i = 30; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        
        foreach ($accountIds as $accountId) {
            $baseCost = rand(30000, 120000);
            $impressions = rand(5000, 15000);
            $clicks = rand(200, 500);
            $conversions = rand(8, 25);
            
            $ctr = ($clicks / $impressions) * 100;
            $cpc = $baseCost / $clicks;
            $cpa = $conversions > 0 ? $baseCost / $conversions : 0;
            $conversionRate = ($conversions / $clicks) * 100;
            
            // 上乗せ率（アカウントによって異なる）
            $markupRate = match($accountId) {
                1 => 1.05, // 5%
                2 => 1.03, // 3%
                3 => 1.02, // 2%
                default => 1.04 // 4%
            };
            
            $reportedCost = $baseCost * $markupRate;
            
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO daily_ad_data 
                (ad_account_id, date_value, impressions, clicks, conversions, cost, reported_cost, ctr, cpc, cpa, conversion_rate, sync_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'synced')
            ");
            
            $stmt->execute([
                $accountId, $date, $impressions, $clicks, $conversions, 
                $baseCost, $reportedCost, $ctr, $cpc, $cpa, $conversionRate
            ]);
        }
    }
    
    // 月次集計データ
    $currentMonth = date('Y-m');
    $pdo->exec("
        INSERT IGNORE INTO monthly_summaries 
        (client_id, ad_account_id, year_month, total_cost, total_reported_cost, total_impressions, total_clicks, total_conversions, calculated_fee)
        VALUES 
        (1, 1, '{$currentMonth}', 2100000, 2205000, 320000, 10500, 420, 420000),
        (1, 2, '{$currentMonth}', 1350000, 1390500, 270000, 7200, 285, 270100),
        (1, 3, '{$currentMonth}', 980000, 999600, 195000, 5850, 195, 196000),
        (2, 4, '{$currentMonth}', 1560000, 1560000, 285000, 8100, 365, 234000),
        (2, 5, '{$currentMonth}', 1190000, 1190000, 220000, 6600, 275, 178500),
        (3, 6, '{$currentMonth}', 1860000, 1914400, 350000, 9800, 490, 334800),
        (3, 7, '{$currentMonth}', 1420000, 1476800, 285000, 7950, 385, 255600)
    ");
    
    echo "✅ デモデータの投入が完了しました\n\n";
    echo "🎉 MariaDBセットアップが完了しました！\n\n";
    echo "📋 作成されたテーブル:\n";
    echo "   - admins (管理者)\n";
    echo "   - clients (クライアント)\n";
    echo "   - fee_settings (手数料設定)\n";
    echo "   - ad_accounts (広告アカウント)\n";
    echo "   - daily_ad_data (日次データ)\n";
    echo "   - monthly_summaries (月次集計)\n";
    echo "   - cost_markups (費用上乗せ)\n\n";
    
    echo "👥 管理者アカウント:\n";
    echo "   Email: admin@kanho-adsmanager.com\n";
    echo "   Password: admin123\n\n";
    
    echo "🔗 システムにアクセスして動作確認してください\n";
    
} catch (PDOException $e) {
    echo "❌ データベースエラー: " . $e->getMessage() . "\n";
    echo "💡 接続情報を確認してください:\n";
    echo "   - ホスト: {$dbConfig['host']}\n";
    echo "   - ポート: {$dbConfig['port']}\n";
    echo "   - データベース: {$dbConfig['database']}\n";
    echo "   - ユーザー: {$dbConfig['username']}\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    exit(1);
}