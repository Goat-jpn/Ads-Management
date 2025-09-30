-- 請求記録テーブル
CREATE TABLE billing_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    
    -- 請求基本情報
    billing_month DATE NOT NULL,
    invoice_number VARCHAR(100) UNIQUE,
    
    -- 金額詳細
    ad_spend_amount DECIMAL(12,2) DEFAULT 0.00,
    management_fee DECIMAL(10,2) DEFAULT 0.00,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    additional_fees DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL,
    
    -- ステータス管理
    status ENUM('draft', 'pending', 'billed', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    
    -- 日付管理
    issue_date DATE,
    due_date DATE,
    paid_date DATE NULL,
    
    -- 請求書情報
    invoice_pdf_path VARCHAR(500),
    payment_method ENUM('bank_transfer', 'credit_card', 'cash', 'other') DEFAULT 'bank_transfer',
    
    -- メモ
    billing_notes TEXT,
    payment_notes TEXT,
    
    -- 作成・更新情報
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 外部キー制約
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_billing_records_client_id ON billing_records(client_id);
CREATE INDEX idx_billing_records_billing_month ON billing_records(billing_month);
CREATE INDEX idx_billing_records_status ON billing_records(status);
CREATE INDEX idx_billing_records_due_date ON billing_records(due_date);
CREATE INDEX idx_billing_records_invoice_number ON billing_records(invoice_number);