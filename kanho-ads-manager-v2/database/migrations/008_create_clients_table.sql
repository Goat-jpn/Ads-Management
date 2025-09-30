-- クライアント管理テーブル
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_name_kana VARCHAR(255),
    contact_person_first_name VARCHAR(100),
    contact_person_last_name VARCHAR(100),
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    postal_code VARCHAR(10),
    address VARCHAR(500),
    website_url VARCHAR(255),
    
    -- 契約情報
    contract_start_date DATE,
    contract_end_date DATE,
    contract_status ENUM('draft', 'active', 'suspended', 'terminated') DEFAULT 'draft',
    
    -- 料金設定
    billing_type ENUM('monthly_fee', 'commission', 'hybrid') DEFAULT 'commission',
    monthly_fee_amount DECIMAL(10,2) DEFAULT 0.00,
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    
    -- メタデータ
    notes TEXT,
    tags VARCHAR(500),
    priority_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- 作成・更新情報
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 外部キー制約
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス
CREATE INDEX idx_clients_company_name ON clients(company_name);
CREATE INDEX idx_clients_email ON clients(email);
CREATE INDEX idx_clients_contract_status ON clients(contract_status);
CREATE INDEX idx_clients_billing_type ON clients(billing_type);
CREATE INDEX idx_clients_created_by ON clients(created_by);
CREATE INDEX idx_clients_contract_dates ON clients(contract_start_date, contract_end_date);