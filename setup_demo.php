<?php

/**
 * デモ用セットアップスクリプト
 * SQLiteを使用してデモデータを作成
 */

require_once __DIR__ . '/bootstrap.php';

echo "🚀 広告管理システム デモセットアップを開始します...\n\n";

// SQLiteデータベースファイルのパス
$dbPath = __DIR__ . '/storage/demo_database.sqlite';

try {
    // SQLiteデータベースに接続
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ SQLiteデータベースに接続しました\n";
    
    // テーブル作成（SQLite用に調整）
    $tables = [
        'clients' => "
            CREATE TABLE IF NOT EXISTS clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_name VARCHAR(200) NOT NULL,
                contact_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                phone VARCHAR(20),
                address TEXT,
                contract_start_date DATE NOT NULL,
                contract_end_date DATE,
                billing_day INTEGER DEFAULT 25,
                payment_terms INTEGER DEFAULT 30,
                is_active BOOLEAN DEFAULT 1,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
        
        'ad_accounts' => "
            CREATE TABLE IF NOT EXISTS ad_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                platform TEXT NOT NULL CHECK (platform IN ('google_ads', 'yahoo_display', 'yahoo_search')),
                account_id VARCHAR(50) NOT NULL,
                account_name VARCHAR(200) NOT NULL,
                currency_code CHAR(3) DEFAULT 'JPY',
                timezone VARCHAR(50) DEFAULT 'Asia/Tokyo',
                is_active BOOLEAN DEFAULT 1,
                last_sync_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id)
            )",
        
        'daily_ad_data' => "
            CREATE TABLE IF NOT EXISTS daily_ad_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ad_account_id INTEGER NOT NULL,
                date_value DATE NOT NULL,
                impressions INTEGER DEFAULT 0,
                clicks INTEGER DEFAULT 0,
                conversions INTEGER DEFAULT 0,
                cost DECIMAL(12,2) DEFAULT 0,
                reported_cost DECIMAL(12,2) DEFAULT 0,
                ctr DECIMAL(5,4) DEFAULT 0,
                cpc DECIMAL(8,2) DEFAULT 0,
                cpa DECIMAL(8,2) DEFAULT 0,
                conversion_rate DECIMAL(5,4) DEFAULT 0,
                sync_status TEXT DEFAULT 'synced' CHECK (sync_status IN ('pending', 'synced', 'failed')),
                raw_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id)
            )",
        
        'monthly_summaries' => "
            CREATE TABLE IF NOT EXISTS monthly_summaries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                ad_account_id INTEGER NOT NULL,
                year_month CHAR(7) NOT NULL,
                total_cost DECIMAL(12,2) DEFAULT 0,
                total_reported_cost DECIMAL(12,2) DEFAULT 0,
                total_impressions INTEGER DEFAULT 0,
                total_clicks INTEGER DEFAULT 0,
                total_conversions INTEGER DEFAULT 0,
                average_ctr DECIMAL(5,4) DEFAULT 0,
                average_cpc DECIMAL(8,2) DEFAULT 0,
                average_cpa DECIMAL(8,2) DEFAULT 0,
                average_conversion_rate DECIMAL(5,4) DEFAULT 0,
                calculated_fee DECIMAL(10,2) DEFAULT 0,
                is_invoiced BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id),
                FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id)
            )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        $pdo->exec($sql);
        echo "✅ テーブル '$tableName' を作成しました\n";
    }
    
    // デモデータの投入
    echo "\n📊 デモデータを投入しています...\n";
    
    // クライアントデータ
    $pdo->exec("
        INSERT OR IGNORE INTO clients (id, company_name, contact_name, email, phone, contract_start_date, billing_day, payment_terms) VALUES
        (1, '株式会社サンプル商事', '田中太郎', 'tanaka@sample-corp.co.jp', '03-1234-5678', '2024-01-01', 25, 30),
        (2, '有限会社テスト工業', '佐藤花子', 'sato@test-industry.co.jp', '06-9876-5432', '2024-02-01', 20, 30),
        (3, 'エクサンプル株式会社', '鈴木次郎', 'suzuki@example-inc.co.jp', '052-1111-2222', '2024-03-01', 25, 45)
    ");
    
    // 広告アカウントデータ
    $pdo->exec("
        INSERT OR IGNORE INTO ad_accounts (id, client_id, platform, account_id, account_name) VALUES
        (1, 1, 'google_ads', '123-456-7890', 'サンプル商事 Google広告'),
        (2, 1, 'yahoo_display', 'YDN-1234567890', 'サンプル商事 Yahoo!ディスプレイ広告'),
        (3, 2, 'google_ads', '987-654-3210', 'テスト工業 Google広告'),
        (4, 2, 'yahoo_display', 'YDN-0987654321', 'テスト工業 Yahoo!ディスプレイ広告'),
        (5, 3, 'google_ads', '555-666-7777', 'エクサンプル株式会社 Google広告')
    ");
    
    // 日次データ（直近7日分）
    for ($i = 7; $i >= 1; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $baseCost = rand(50000, 150000);
        $impressions = rand(8000, 15000);
        $clicks = rand(300, 500);
        $conversions = rand(10, 25);
        
        $ctr = ($clicks / $impressions) * 100;
        $cpc = $baseCost / $clicks;
        $cpa = $conversions > 0 ? $baseCost / $conversions : 0;
        $conversionRate = ($conversions / $clicks) * 100;
        
        $reportedCost = $baseCost * 1.05; // 5%上乗せ
        
        $pdo->exec("
            INSERT OR IGNORE INTO daily_ad_data 
            (ad_account_id, date_value, impressions, clicks, conversions, cost, reported_cost, ctr, cpc, cpa, conversion_rate)
            VALUES 
            (1, '$date', $impressions, $clicks, $conversions, $baseCost, $reportedCost, $ctr, $cpc, $cpa, $conversionRate)
        ");
        
        // 他のアカウントのデータも生成
        for ($accountId = 2; $accountId <= 5; $accountId++) {
            $accountCost = rand(30000, 100000);
            $accountImpressions = rand(5000, 12000);
            $accountClicks = rand(200, 400);
            $accountConversions = rand(8, 20);
            
            $accountCtr = ($accountClicks / $accountImpressions) * 100;
            $accountCpc = $accountCost / $accountClicks;
            $accountCpa = $accountConversions > 0 ? $accountCost / $accountConversions : 0;
            $accountConversionRate = ($accountConversions / $accountClicks) * 100;
            $accountReportedCost = $accountCost * 1.03; // 3%上乗せ
            
            $pdo->exec("
                INSERT OR IGNORE INTO daily_ad_data 
                (ad_account_id, date_value, impressions, clicks, conversions, cost, reported_cost, ctr, cpc, cpa, conversion_rate)
                VALUES 
                ($accountId, '$date', $accountImpressions, $accountClicks, $accountConversions, $accountCost, $accountReportedCost, $accountCtr, $accountCpc, $accountCpa, $accountConversionRate)
            ");
        }
    }
    
    // 月次集計データ
    $currentMonth = date('Y-m');
    $pdo->exec("
        INSERT OR IGNORE INTO monthly_summaries 
        (client_id, ad_account_id, year_month, total_cost, total_reported_cost, total_impressions, total_clicks, total_conversions, calculated_fee)
        VALUES 
        (1, 1, '$currentMonth', 700000, 735000, 85000, 2800, 140, 140000),
        (1, 2, '$currentMonth', 450000, 463500, 65000, 1800, 95, 90700),
        (2, 3, '$currentMonth', 520000, 535600, 72000, 2100, 115, 104000),
        (2, 4, '$currentMonth', 380000, 391400, 58000, 1650, 88, 76000),
        (3, 5, '$currentMonth', 620000, 638600, 78000, 2300, 125, 124000)
    ");
    
    echo "✅ デモデータの投入が完了しました\n\n";
    
    // データベース設定を更新（SQLite用）
    $configContent = file_get_contents(__DIR__ . '/config/app.php');
    $sqliteConfig = str_replace(
        "    'database' => [
        'host' => \$_ENV['DB_HOST'] ?? 'localhost',
        'port' => \$_ENV['DB_PORT'] ?? '3306',
        'database' => \$_ENV['DB_NAME'] ?? 'ads_management',
        'username' => \$_ENV['DB_USER'] ?? 'root',
        'password' => \$_ENV['DB_PASS'] ?? '',
    ],",
        "    'database' => [
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../storage/demo_database.sqlite',
        'host' => \$_ENV['DB_HOST'] ?? 'localhost',
        'port' => \$_ENV['DB_PORT'] ?? '3306',
        'username' => \$_ENV['DB_USER'] ?? 'root',
        'password' => \$_ENV['DB_PASS'] ?? '',
    ],",
        $configContent
    );
    
    file_put_contents(__DIR__ . '/config/app.php', $sqliteConfig);
    
    // SQLite用のデータベース接続クラスを作成
    file_put_contents(__DIR__ . '/config/database/SqliteConnection.php', "<?php

namespace Config\\Database;

use PDO;
use PDOException;

class SqliteConnection extends Connection
{
    protected static function createConnection(): PDO
    {
        \$dbPath = __DIR__ . '/../../storage/demo_database.sqlite';
        
        try {
            \$pdo = new PDO(\"sqlite:{\$dbPath}\");
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return \$pdo;
        } catch (PDOException \$e) {
            throw new PDOException('SQLiteデータベース接続に失敗しました: ' . \$e->getMessage());
        }
    }
}");
    
    echo "🎉 デモセットアップが完了しました！\n";
    echo "📂 データベース: storage/demo_database.sqlite\n";
    echo "🔗 ウェブサーバーを起動してアクセスしてください\n\n";
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    exit(1);
}