-- キャンペーンテーブル
CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_account_id INT NOT NULL,
    campaign_id VARCHAR(100) NOT NULL,
    campaign_name VARCHAR(255),
    campaign_type VARCHAR(50),
    status VARCHAR(20),
    budget_amount DECIMAL(15,2),
    budget_type VARCHAR(20),
    start_date DATE,
    end_date DATE,
    target_cpa DECIMAL(10,2),
    target_roas DECIMAL(8,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign (ad_account_id, campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_campaigns_ad_account_id ON campaigns(ad_account_id);
CREATE INDEX idx_campaigns_campaign_id ON campaigns(campaign_id);
CREATE INDEX idx_campaigns_status ON campaigns(status);
CREATE INDEX idx_campaigns_start_date ON campaigns(start_date);
CREATE INDEX idx_campaigns_end_date ON campaigns(end_date);