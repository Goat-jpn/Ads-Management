-- 広告管理システム用 MariaDB 10.5 セットアップ
-- Xbizサーバー用データベース初期化スクリプト

-- データベース作成（必要に応じて）
-- CREATE DATABASE IF NOT EXISTS `kanho_adsmanager` 
-- CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- USE kanho_adsmanager;

-- 管理者テーブル
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','manager','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- クライアントテーブル
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `contact_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `billing_day` int(2) DEFAULT 25,
  `payment_terms` int(3) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `company_name` (`company_name`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 手数料設定テーブル
CREATE TABLE IF NOT EXISTS `fee_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `fee_type` enum('percentage','fixed','tiered') DEFAULT 'percentage',
  `fee_value` decimal(10,4) DEFAULT NULL,
  `min_fee` decimal(10,2) DEFAULT NULL,
  `max_fee` decimal(10,2) DEFAULT NULL,
  `tier_config` json DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `effective_from` (`effective_from`),
  CONSTRAINT `fee_settings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 広告アカウントテーブル
CREATE TABLE IF NOT EXISTS `ad_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `platform` enum('google_ads','yahoo_search','yahoo_display','facebook','other') NOT NULL,
  `account_id` varchar(100) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `currency` varchar(3) DEFAULT 'JPY',
  `timezone` varchar(50) DEFAULT 'Asia/Tokyo',
  `is_active` tinyint(1) DEFAULT 1,
  `last_sync` datetime DEFAULT NULL,
  `sync_status` enum('success','error','pending','never') DEFAULT 'never',
  `api_credentials` json DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_account` (`platform`,`account_id`),
  KEY `client_id` (`client_id`),
  KEY `platform` (`platform`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `ad_accounts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 日次広告データテーブル
CREATE TABLE IF NOT EXISTS `daily_ad_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_account_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `impressions` bigint(20) DEFAULT 0,
  `clicks` bigint(20) DEFAULT 0,
  `cost` decimal(12,2) DEFAULT 0.00,
  `conversions` decimal(10,2) DEFAULT 0.00,
  `conversion_value` decimal(12,2) DEFAULT 0.00,
  `ctr` decimal(6,4) DEFAULT NULL,
  `cpc` decimal(10,2) DEFAULT NULL,
  `cpa` decimal(10,2) DEFAULT NULL,
  `roas` decimal(6,4) DEFAULT NULL,
  `quality_score` decimal(3,2) DEFAULT NULL,
  `raw_data` json DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_date` (`ad_account_id`,`date`),
  KEY `date` (`date`),
  KEY `cost` (`cost`),
  CONSTRAINT `daily_ad_data_ibfk_1` FOREIGN KEY (`ad_account_id`) REFERENCES `ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 月次サマリーテーブル
CREATE TABLE IF NOT EXISTS `monthly_summaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `total_cost` decimal(12,2) DEFAULT 0.00,
  `total_impressions` bigint(20) DEFAULT 0,
  `total_clicks` bigint(20) DEFAULT 0,
  `total_conversions` decimal(10,2) DEFAULT 0.00,
  `average_ctr` decimal(6,4) DEFAULT NULL,
  `average_cpc` decimal(10,2) DEFAULT NULL,
  `average_cpa` decimal(10,2) DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT 0.00,
  `total_billing` decimal(12,2) DEFAULT 0.00,
  `is_finalized` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_year_month` (`client_id`,`year`,`month`),
  KEY `year_month` (`year`,`month`),
  CONSTRAINT `monthly_summaries_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 費用マークアップテーブル
CREATE TABLE IF NOT EXISTS `cost_markups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `markup_type` enum('percentage','fixed_amount') DEFAULT 'percentage',
  `markup_value` decimal(10,4) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `effective_from` (`effective_from`),
  CONSTRAINT `cost_markups_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 請求書テーブル
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `billing_period_start` date NOT NULL,
  `billing_period_end` date NOT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_rate` decimal(5,4) DEFAULT 0.1000,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  KEY `due_date` (`due_date`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- デモデータ挿入

-- 管理者デモアカウント
INSERT IGNORE INTO `admins` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'admin@kanho.co.jp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理者', 'admin'),
('manager', 'manager@kanho.co.jp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'マネージャー', 'manager');

-- クライアントデモデータ
INSERT IGNORE INTO `clients` (`id`, `company_name`, `contact_name`, `email`, `phone`, `address`, `contract_start_date`, `contract_end_date`, `billing_day`, `payment_terms`) VALUES
(1, '株式会社サンプル商事', '田中太郎', 'tanaka@sample-corp.co.jp', '03-1234-5678', '東京都港区サンプル1-2-3', '2024-01-01', '2024-12-31', 25, 30),
(2, 'テクノロジー株式会社', '佐藤花子', 'sato@technology-inc.co.jp', '06-9876-5432', '大阪府大阪市技術区2-4-6', '2024-02-15', '2025-02-14', 20, 45),
(3, 'マーケティング合同会社', '鈴木一郎', 'suzuki@marketing-llc.co.jp', '052-1111-2222', '愛知県名古屋市販売区3-6-9', '2024-03-01', '2025-02-28', 15, 30);

-- 広告アカウントデモデータ
INSERT IGNORE INTO `ad_accounts` (`id`, `client_id`, `platform`, `account_id`, `account_name`, `is_active`) VALUES
(1, 1, 'google_ads', '123-456-7890', 'サンプル商事 Google Ads メイン', 1),
(2, 1, 'yahoo_search', 'YSS001', 'サンプル商事 Yahoo検索広告', 1),
(3, 1, 'yahoo_display', 'YDN001', 'サンプル商事 Yahooディスプレイ広告', 1),
(4, 2, 'google_ads', '234-567-8901', 'テクノロジー Google Ads', 1),
(5, 2, 'yahoo_search', 'YSS002', 'テクノロジー Yahoo検索広告', 1),
(6, 3, 'google_ads', '345-678-9012', 'マーケティング Google Ads', 1),
(7, 3, 'facebook', 'FB789123', 'マーケティング Facebook広告', 1);

-- 手数料設定デモデータ
INSERT IGNORE INTO `fee_settings` (`client_id`, `fee_type`, `fee_value`, `effective_from`) VALUES
(1, 'percentage', 15.0000, '2024-01-01'),
(2, 'percentage', 20.0000, '2024-02-15'),
(3, 'fixed', 50000.0000, '2024-03-01');

-- 費用マークアップデモデータ
INSERT IGNORE INTO `cost_markups` (`client_id`, `markup_type`, `markup_value`, `description`, `effective_from`) VALUES
(1, 'percentage', 4.0000, '標準マークアップ 4%', '2024-01-01'),
(2, 'percentage', 5.0000, 'プレミアムマークアップ 5%', '2024-02-15'),
(3, 'fixed_amount', 10000.0000, '固定マークアップ', '2024-03-01');

-- 日次広告データ生成（過去30日分のサンプルデータ）
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS GenerateDailyAdData()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE account_id INT;
    DECLARE cur CURSOR FOR SELECT id FROM ad_accounts WHERE is_active = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO account_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- 過去30日分のデータを生成
        SET @date_counter = 30;
        WHILE @date_counter > 0 DO
            SET @target_date = DATE_SUB(CURDATE(), INTERVAL @date_counter DAY);
            
            -- ランダムなパフォーマンスデータを生成
            SET @impressions = FLOOR(RAND() * 50000) + 10000;
            SET @clicks = FLOOR(@impressions * (RAND() * 0.05 + 0.01)); -- CTR 1-6%
            SET @cost = ROUND(@clicks * (RAND() * 300 + 100), 2); -- CPC 100-400円
            SET @conversions = ROUND(@clicks * (RAND() * 0.1 + 0.02), 2); -- CVR 2-12%
            SET @ctr = ROUND((@clicks / @impressions) * 100, 4);
            SET @cpc = ROUND(@cost / @clicks, 2);
            SET @cpa = ROUND(@cost / @conversions, 2);
            
            INSERT IGNORE INTO daily_ad_data 
            (ad_account_id, date, impressions, clicks, cost, conversions, ctr, cpc, cpa)
            VALUES 
            (account_id, @target_date, @impressions, @clicks, @cost, @conversions, @ctr, @cpc, @cpa);
            
            SET @date_counter = @date_counter - 1;
        END WHILE;
        
    END LOOP;
    
    CLOSE cur;
END$$
DELIMITER ;

-- データ生成プロシージャ実行
CALL GenerateDailyAdData();

-- プロシージャ削除（一回限りの使用）
DROP PROCEDURE IF EXISTS GenerateDailyAdData;

-- 月次サマリー生成
INSERT IGNORE INTO monthly_summaries (client_id, year, month, total_cost, total_impressions, total_clicks, total_conversions, fee_amount, total_billing)
SELECT 
    c.id as client_id,
    YEAR(d.date) as year,
    MONTH(d.date) as month,
    ROUND(SUM(d.cost), 2) as total_cost,
    SUM(d.impressions) as total_impressions,
    SUM(d.clicks) as total_clicks,
    SUM(d.conversions) as total_conversions,
    ROUND(SUM(d.cost) * 0.15, 2) as fee_amount,
    ROUND(SUM(d.cost) * 1.15, 2) as total_billing
FROM clients c
JOIN ad_accounts a ON c.id = a.client_id
JOIN daily_ad_data d ON a.id = d.ad_account_id
WHERE d.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
GROUP BY c.id, YEAR(d.date), MONTH(d.date);

-- インデックス最適化
ANALYZE TABLE clients, ad_accounts, daily_ad_data, monthly_summaries, fee_settings, cost_markups, invoices;

-- 完了メッセージ
SELECT 'MariaDB広告管理システム データベースセットアップ完了!' as message;