-- 広告アカウントテーブル
CREATE TABLE ad_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    platform ENUM('google', 'yahoo') NOT NULL,
    account_id VARCHAR(100) NOT NULL,
    account_name VARCHAR(255),
    currency VARCHAR(10) DEFAULT 'JPY',
    timezone VARCHAR(50) DEFAULT 'Asia/Tokyo',
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at TIMESTAMP NULL,
    last_sync TIMESTAMP NULL,
    sync_enabled BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_account (platform, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_ad_accounts_client_id ON ad_accounts(client_id);
CREATE INDEX idx_ad_accounts_platform ON ad_accounts(platform);
CREATE INDEX idx_ad_accounts_status ON ad_accounts(status);
CREATE INDEX idx_ad_accounts_last_sync ON ad_accounts(last_sync);
CREATE INDEX idx_ad_accounts_sync_enabled ON ad_accounts(sync_enabled);