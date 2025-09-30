-- 広告アカウント管理テーブル
CREATE TABLE ad_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    
    -- アカウント基本情報
    platform ENUM('google_ads', 'yahoo_ads', 'facebook_ads', 'line_ads') NOT NULL,
    account_id VARCHAR(100) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    
    -- API認証情報 (暗号化して保存)
    access_token TEXT,
    refresh_token TEXT,
    api_credentials JSON,
    
    -- アカウント状態
    account_status ENUM('active', 'inactive', 'suspended', 'sync_error') DEFAULT 'inactive',
    last_sync_at TIMESTAMP NULL,
    sync_error_message TEXT,
    
    -- 設定
    auto_sync_enabled BOOLEAN DEFAULT TRUE,
    sync_frequency ENUM('hourly', 'daily', 'weekly') DEFAULT 'daily',
    
    -- 権限・制限
    daily_budget_limit DECIMAL(10,2),
    monthly_budget_limit DECIMAL(10,2),
    
    -- メタデータ
    notes TEXT,
    
    -- 作成・更新情報
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 外部キー制約
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- ユニーク制約
    UNIQUE KEY unique_platform_account (platform, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_ad_accounts_client_id ON ad_accounts(client_id);
CREATE INDEX idx_ad_accounts_platform ON ad_accounts(platform);
CREATE INDEX idx_ad_accounts_status ON ad_accounts(account_status);
CREATE INDEX idx_ad_accounts_last_sync ON ad_accounts(last_sync_at);
CREATE INDEX idx_ad_accounts_auto_sync ON ad_accounts(auto_sync_enabled);