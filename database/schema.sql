-- 広告費・手数料管理システム データベーススキーマ
-- MySQL 8.0+ 対応

-- データベース作成
CREATE DATABASE IF NOT EXISTS ads_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ads_management;

-- 管理者テーブル
CREATE TABLE admins (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- クライアントテーブル
CREATE TABLE clients (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 手数料設定テーブル
CREATE TABLE fee_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 段階手数料テーブル（階段型手数料用）
CREATE TABLE tiered_fees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fee_setting_id INT UNSIGNED NOT NULL,
    min_amount DECIMAL(12,2) NOT NULL,
    max_amount DECIMAL(12,2),
    percentage DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (fee_setting_id) REFERENCES fee_settings(id) ON DELETE CASCADE,
    INDEX idx_fee_setting (fee_setting_id),
    INDEX idx_amount_range (min_amount, max_amount)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 広告アカウントテーブル
CREATE TABLE ad_accounts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 日次広告データテーブル
CREATE TABLE daily_ad_data (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 月次集計テーブル
CREATE TABLE monthly_summaries (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 請求書テーブル
CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,
    subtotal_ad_cost DECIMAL(12,2) DEFAULT 0 COMMENT '広告費小計',
    subtotal_fees DECIMAL(10,2) DEFAULT 0 COMMENT '手数料小計',
    tax_rate DECIMAL(5,4) DEFAULT 0.10 COMMENT '税率',
    tax_amount DECIMAL(10,2) DEFAULT 0 COMMENT '税額',
    total_amount DECIMAL(12,2) DEFAULT 0 COMMENT '合計金額',
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    issued_at TIMESTAMP NULL,
    due_date DATE NOT NULL,
    paid_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client_period (client_id, billing_period_start, billing_period_end),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 請求書明細テーブル
CREATE TABLE invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    ad_account_id INT UNSIGNED NOT NULL,
    platform ENUM('google_ads', 'yahoo_display', 'yahoo_search') NOT NULL,
    description TEXT NOT NULL,
    ad_cost DECIMAL(12,2) DEFAULT 0,
    fee_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
    INDEX idx_invoice (invoice_id),
    INDEX idx_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 費用上乗せ設定テーブル
CREATE TABLE cost_markups (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API同期ログテーブル
CREATE TABLE sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_account_id INT UNSIGNED NOT NULL,
    sync_type ENUM('daily_data', 'account_info', 'campaign_data') NOT NULL,
    sync_date DATE NOT NULL,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    records_processed INT DEFAULT 0,
    error_message TEXT,
    execution_time_ms INT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
    INDEX idx_account_date (ad_account_id, sync_date),
    INDEX idx_status (status),
    INDEX idx_sync_type (sync_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- システム設定テーブル
CREATE TABLE system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;