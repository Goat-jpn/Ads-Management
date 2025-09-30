-- キャンペーンデータテーブル
CREATE TABLE IF NOT EXISTS campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ad_account_id INT NOT NULL,
    campaign_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    channel_type VARCHAR(50),
    start_date DATE,
    end_date DATE,
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    ctr DECIMAL(10, 4) DEFAULT 0,
    cost_micros BIGINT DEFAULT 0,
    average_cpc DECIMAL(12, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_account_campaign (ad_account_id, campaign_id),
    INDEX idx_ad_account_id (ad_account_id),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 広告グループテーブル
CREATE TABLE IF NOT EXISTS ad_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    ad_group_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    cpc_bid_micros BIGINT DEFAULT 0,
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    ctr DECIMAL(10, 4) DEFAULT 0,
    cost_micros BIGINT DEFAULT 0,
    average_cpc DECIMAL(12, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_adgroup (campaign_id, ad_group_id),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_ad_group_id (ad_group_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- パフォーマンスサマリーテーブル
CREATE TABLE IF NOT EXISTS performance_summaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ad_account_id INT NOT NULL,
    date_range VARCHAR(50) NOT NULL,
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    ctr DECIMAL(10, 4) DEFAULT 0,
    cost_micros BIGINT DEFAULT 0,
    cost_yen DECIMAL(12, 2) DEFAULT 0,
    average_cpc DECIMAL(12, 2) DEFAULT 0,
    conversions DECIMAL(10, 2) DEFAULT 0,
    conversion_rate DECIMAL(10, 4) DEFAULT 0,
    cost_per_conversion DECIMAL(12, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_account_daterange (ad_account_id, date_range),
    INDEX idx_ad_account_id (ad_account_id),
    INDEX idx_date_range (date_range)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 同期履歴テーブル
CREATE TABLE IF NOT EXISTS sync_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ad_account_id INT NOT NULL,
    sync_type VARCHAR(50) NOT NULL, -- 'full', 'campaigns', 'performance'
    status VARCHAR(20) NOT NULL,    -- 'success', 'error', 'warning'
    message TEXT,
    campaigns_synced INT DEFAULT 0,
    ad_groups_synced INT DEFAULT 0,
    execution_time_ms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
    INDEX idx_ad_account_id (ad_account_id),
    INDEX idx_status (status),
    INDEX idx_sync_type (sync_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ad_accountsテーブルにlast_sync_atカラムを追加（存在しない場合のみ）
ALTER TABLE ad_accounts 
ADD COLUMN IF NOT EXISTS last_sync_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS sync_status VARCHAR(20) DEFAULT 'never',
ADD COLUMN IF NOT EXISTS sync_error_message TEXT NULL;

-- インデックスを追加
CREATE INDEX IF NOT EXISTS idx_last_sync_at ON ad_accounts(last_sync_at);
CREATE INDEX IF NOT EXISTS idx_sync_status ON ad_accounts(sync_status);