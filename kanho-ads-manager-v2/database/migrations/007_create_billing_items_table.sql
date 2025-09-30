-- 請求明細テーブル
CREATE TABLE billing_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billing_record_id INT NOT NULL,
    ad_account_id INT NOT NULL,
    
    -- 期間データ
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- パフォーマンスデータ
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    ad_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    conversions DECIMAL(10,2) DEFAULT 0.00,
    
    -- 計算結果
    fee_rate DECIMAL(5,2) DEFAULT 0.00,              -- フィー率(%)
    fee_amount DECIMAL(15,2) DEFAULT 0.00,           -- フィー額
    markup_rate DECIMAL(5,2) DEFAULT 0.00,           -- 上乗せ率(%)
    markup_amount DECIMAL(15,2) DEFAULT 0.00,        -- 上乗せ額
    total_amount DECIMAL(15,2) NOT NULL,             -- 小計
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (billing_record_id) REFERENCES billing_records(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_billing_items_billing_record_id ON billing_items(billing_record_id);
CREATE INDEX idx_billing_items_ad_account_id ON billing_items(ad_account_id);
CREATE INDEX idx_billing_items_period ON billing_items(period_start, period_end);
CREATE INDEX idx_billing_items_total_amount ON billing_items(total_amount);