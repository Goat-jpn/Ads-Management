-- Kanho Ads Manager データベーステーブル作成
-- MariaDB 10.5 対応 - Simple Version

-- 1. クライアントマスター
CREATE TABLE IF NOT EXISTS `clients` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL COMMENT 'クライアント名',
    `email` varchar(255) DEFAULT NULL COMMENT 'メールアドレス',
    `phone` varchar(50) DEFAULT NULL COMMENT '電話番号',
    `status` enum('active', 'inactive') DEFAULT 'active' COMMENT 'ステータス',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='クライアントマスター';

-- 2. 広告アカウント
CREATE TABLE IF NOT EXISTS `ad_accounts` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `client_id` bigint(20) unsigned NOT NULL,
    `account_id` varchar(100) NOT NULL COMMENT '広告アカウントID',
    `account_name` varchar(255) NOT NULL COMMENT 'アカウント名',
    `platform` enum('google', 'yahoo') NOT NULL COMMENT '広告プラットフォーム',
    `status` enum('active', 'inactive') DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_account` (`platform`, `account_id`),
    KEY `idx_client` (`client_id`),
    KEY `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='広告アカウント';

-- 3. 日別広告データ
CREATE TABLE IF NOT EXISTS `daily_ad_data` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `ad_account_id` bigint(20) unsigned NOT NULL,
    `date` date NOT NULL COMMENT 'データ日付',
    `impressions` bigint(20) unsigned DEFAULT 0 COMMENT 'インプレッション数',
    `clicks` bigint(20) unsigned DEFAULT 0 COMMENT 'クリック数',
    `cost` decimal(15,2) DEFAULT 0.00 COMMENT '実際の広告費',
    `conversions` int(11) DEFAULT 0 COMMENT 'コンバージョン数',
    `conversion_value` decimal(15,2) DEFAULT 0.00 COMMENT 'コンバージョン価値',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_daily_data` (`ad_account_id`, `date`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日別広告データ';

-- 4. コストマークアップ設定
CREATE TABLE IF NOT EXISTS `cost_markups` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `client_id` bigint(20) unsigned NOT NULL,
    `markup_type` enum('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `markup_value` decimal(10,2) NOT NULL COMMENT 'マークアップ率/固定額',
    `effective_from` date NOT NULL COMMENT '適用開始日',
    `effective_to` date DEFAULT NULL COMMENT '適用終了日',
    `status` enum('active', 'inactive') DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client` (`client_id`),
    KEY `idx_effective_dates` (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='コストマークアップ設定';

-- 5. 手数料設定
CREATE TABLE IF NOT EXISTS `fee_settings` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `client_id` bigint(20) unsigned NOT NULL,
    `fee_type` enum('percentage', 'fixed', 'tiered') NOT NULL DEFAULT 'percentage',
    `fee_value` decimal(10,2) DEFAULT NULL COMMENT '手数料率/固定額',
    `minimum_fee` decimal(15,2) DEFAULT NULL COMMENT '最低手数料',
    `maximum_fee` decimal(15,2) DEFAULT NULL COMMENT '最高手数料',
    `effective_from` date NOT NULL,
    `effective_to` date DEFAULT NULL,
    `status` enum('active', 'inactive') DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client` (`client_id`),
    KEY `idx_effective_dates` (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='手数料設定';

-- 6. 階層手数料
CREATE TABLE IF NOT EXISTS `tiered_fees` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `fee_setting_id` bigint(20) unsigned NOT NULL,
    `tier_from` decimal(15,2) NOT NULL COMMENT '階層開始金額',
    `tier_to` decimal(15,2) DEFAULT NULL COMMENT '階層終了金額',
    `fee_rate` decimal(5,2) NOT NULL COMMENT '手数料率',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_fee_setting` (`fee_setting_id`),
    KEY `idx_tier_range` (`tier_from`, `tier_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='階層手数料';

-- 7. 請求書
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `client_id` bigint(20) unsigned NOT NULL,
    `invoice_number` varchar(50) NOT NULL COMMENT '請求書番号',
    `billing_period_start` date NOT NULL COMMENT '請求期間開始',
    `billing_period_end` date NOT NULL COMMENT '請求期間終了',
    `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '小計',
    `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '消費税額',
    `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '合計金額',
    `due_date` date DEFAULT NULL COMMENT '支払期限',
    `status` enum('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_invoice_number` (`invoice_number`),
    KEY `idx_client` (`client_id`),
    KEY `idx_status` (`status`),
    KEY `idx_billing_period` (`billing_period_start`, `billing_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='請求書';

-- 8. 請求明細
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `invoice_id` bigint(20) unsigned NOT NULL,
    `description` varchar(500) NOT NULL COMMENT '明細説明',
    `quantity` decimal(10,2) DEFAULT 1.00 COMMENT '数量',
    `unit_price` decimal(15,2) NOT NULL COMMENT '単価',
    `amount` decimal(15,2) NOT NULL COMMENT '金額',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='請求明細';

-- サンプルデータ挿入
INSERT IGNORE INTO `clients` (`id`, `name`, `email`, `phone`, `status`) VALUES
(1, 'サンプル株式会社', 'sample@example.com', '03-1234-5678', 'active'),
(2, 'テスト商事', 'test@example.com', '03-8765-4321', 'active'),
(3, 'デモ企業', 'demo@example.com', '03-1111-2222', 'active');

INSERT IGNORE INTO `ad_accounts` (`id`, `client_id`, `account_id`, `account_name`, `platform`, `status`) VALUES
(1, 1, 'google-123456789', 'サンプル社 Google Ads', 'google', 'active'),
(2, 1, 'yahoo-987654321', 'サンプル社 Yahoo広告', 'yahoo', 'active'),
(3, 2, 'google-111222333', 'テスト商事 Google Ads', 'google', 'active'),
(4, 3, 'google-444555666', 'デモ企業 Google Ads', 'google', 'active');

-- サンプル広告データ（過去30日分）
INSERT IGNORE INTO `daily_ad_data` (`ad_account_id`, `date`, `impressions`, `clicks`, `cost`, `conversions`, `conversion_value`) VALUES
(1, CURDATE() - INTERVAL 1 DAY, 15000, 750, 25000.00, 15, 150000.00),
(1, CURDATE() - INTERVAL 2 DAY, 12000, 600, 20000.00, 12, 120000.00),
(1, CURDATE() - INTERVAL 3 DAY, 18000, 900, 30000.00, 18, 180000.00),
(2, CURDATE() - INTERVAL 1 DAY, 8000, 400, 15000.00, 8, 80000.00),
(2, CURDATE() - INTERVAL 2 DAY, 10000, 500, 18000.00, 10, 100000.00),
(3, CURDATE() - INTERVAL 1 DAY, 5000, 250, 12000.00, 5, 50000.00),
(4, CURDATE() - INTERVAL 1 DAY, 3000, 150, 8000.00, 3, 30000.00);

-- 基本的なマークアップ設定
INSERT IGNORE INTO `cost_markups` (`client_id`, `markup_type`, `markup_value`, `effective_from`) VALUES
(1, 'percentage', 20.00, '2024-01-01'),
(2, 'percentage', 15.00, '2024-01-01'),
(3, 'percentage', 25.00, '2024-01-01');

-- 基本的な手数料設定
INSERT IGNORE INTO `fee_settings` (`client_id`, `fee_type`, `fee_value`, `effective_from`) VALUES
(1, 'percentage', 10.00, '2024-01-01'),
(2, 'percentage', 12.00, '2024-01-01'),
(3, 'percentage', 8.00, '2024-01-01');