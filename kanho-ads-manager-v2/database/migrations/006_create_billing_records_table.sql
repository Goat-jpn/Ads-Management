-- 請求記録テーブル
CREATE TABLE billing_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,
    
    -- 金額関連
    ad_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,    -- 広告費
    fee_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00, -- フィー
    markup_amount DECIMAL(15,2) DEFAULT 0.00,       -- 上乗せ額
    total_amount DECIMAL(15,2) NOT NULL,             -- 請求総額
    
    -- 日付関連
    billing_date DATE NULL,                          -- 請求日
    due_date DATE NULL,                              -- 支払期限
    paid_date DATE NULL,                             -- 入金日
    
    -- ステータス管理
    status ENUM('draft', 'pending', 'billed', 'paid', 'overdue') DEFAULT 'draft',
    
    -- 管理情報
    notes TEXT,                                      -- メモ・備考
    created_by INT,                                  -- 作成者
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_billing_records_client_id ON billing_records(client_id);
CREATE INDEX idx_billing_records_status ON billing_records(status);
CREATE INDEX idx_billing_records_billing_period ON billing_records(billing_period_start, billing_period_end);
CREATE INDEX idx_billing_records_billing_date ON billing_records(billing_date);
CREATE INDEX idx_billing_records_due_date ON billing_records(due_date);
CREATE INDEX idx_billing_records_created_by ON billing_records(created_by);
CREATE INDEX idx_billing_records_total_amount ON billing_records(total_amount);