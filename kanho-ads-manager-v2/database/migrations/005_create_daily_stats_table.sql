-- 日次統計テーブル
CREATE TABLE daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_account_id INT NOT NULL,
    campaign_id VARCHAR(100),
    date DATE NOT NULL,
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    cost DECIMAL(15,2) DEFAULT 0.00,
    conversions DECIMAL(10,2) DEFAULT 0.00,
    conversion_value DECIMAL(15,2) DEFAULT 0.00,
    ctr DECIMAL(8,4),
    cpc DECIMAL(10,2),
    cpa DECIMAL(10,2),
    roas DECIMAL(8,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_stat (ad_account_id, campaign_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_daily_stats_ad_account_id ON daily_stats(ad_account_id);
CREATE INDEX idx_daily_stats_date ON daily_stats(date);
CREATE INDEX idx_daily_stats_account_date ON daily_stats(ad_account_id, date);
CREATE INDEX idx_daily_stats_campaign_id ON daily_stats(campaign_id);
CREATE INDEX idx_daily_stats_cost ON daily_stats(cost);
CREATE INDEX idx_daily_stats_conversions ON daily_stats(conversions);