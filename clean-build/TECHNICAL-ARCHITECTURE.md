# 🏗️ Kanho Ads Manager - 技術アーキテクチャ詳細

## 🎯 アーキテクチャ概要

### システム構成
```
┌─────────────────────────────────────────┐
│           Frontend Layer                │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐   │
│  │ Dashboard│ │ Client  │ │ Setup   │   │
│  │   UI     │ │  Mgmt   │ │ Tools   │   │
│  └─────────┘ └─────────┘ └─────────┘   │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Application Layer               │
│  ┌─────────────────────────────────────┐ │
│  │        PHP 7.4.33 Runtime          │ │
│  └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Data Access Layer               │
│  ┌─────────────────────────────────────┐ │
│  │     Connection-simple.php           │ │
│  │   (PDO + Auto-Reconnection)         │ │
│  └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Database Layer                  │
│  ┌─────────────────────────────────────┐ │
│  │      MariaDB 10.5 (localhost)      │ │
│  │         kanho_adsmanager            │ │
│  └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

---

## 🔧 データベース設計

### ERD (Entity Relationship Diagram)
```
┌─────────────┐     ┌─────────────────┐     ┌──────────────────┐
│   clients   │────▶│   ad_accounts   │────▶│  daily_ad_data   │
└─────────────┘     └─────────────────┘     └──────────────────┘
       │                      │                        │
       │                      │                        │
       ▼                      ▼                        ▼
┌─────────────┐     ┌─────────────────┐     ┌──────────────────┐
│ fee_settings│     │  cost_markups   │     │   tiered_fees    │
└─────────────┘     └─────────────────┘     └──────────────────┘
       │                                               │
       │                                               │
       ▼                                               ▼
┌─────────────┐                               ┌──────────────────┐
│  invoices   │────▶──────────────────────────│  invoice_items   │
└─────────────┘                               └──────────────────┘
```

### テーブル詳細設計

#### 1. clients (クライアント)
```sql
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```
**用途**: 顧客基本情報管理  
**関連**: ad_accounts, fee_settings, invoices

#### 2. ad_accounts (広告アカウント)
```sql
CREATE TABLE ad_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    platform ENUM('google', 'yahoo') NOT NULL,
    account_id VARCHAR(100) NOT NULL,
    account_name VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (client_id) REFERENCES clients(id)
);
```
**用途**: 広告プラットフォーム別アカウント管理  
**関連**: clients, daily_ad_data, cost_markups

#### 3. daily_ad_data (日次広告データ)
```sql
CREATE TABLE daily_ad_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_account_id INT NOT NULL,
    date DATE NOT NULL,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0.00,
    conversions INT DEFAULT 0,
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id)
);
```
**用途**: 日次パフォーマンスデータ保存  
**集計**: CPA、CTR、ROI計算に使用

#### 4. cost_markups (コスト上乗せ)
```sql
CREATE TABLE cost_markups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_account_id INT NOT NULL,
    markup_type ENUM('percentage', 'fixed') NOT NULL,
    markup_value DECIMAL(8,2) NOT NULL,
    effective_from DATE NOT NULL,
    FOREIGN KEY (ad_account_id) REFERENCES ad_accounts(id)
);
```
**用途**: 広告費への上乗せ設定  
**計算**: 実際コスト + マークアップ = 請求額

#### 5. fee_settings (フィー設定)
```sql
CREATE TABLE fee_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    fee_type ENUM('percentage', 'fixed', 'tiered') NOT NULL,
    fee_value DECIMAL(8,2),
    minimum_fee DECIMAL(8,2) DEFAULT 0.00,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);
```
**用途**: クライアント別フィー体系  
**種類**: パーセンテージ、固定額、階層制

---

## 💻 アプリケーション層設計

### MVC パターン (簡素版)

#### Model Layer
```php
// Database Access Object
class Database {
    // Connection Management
    private static $instance = null;
    private $connection;
    
    // CRUD Operations
    public static function select($sql, $params = [])
    public static function insert($table, $data)
    public static function update($table, $data, $where, $params)
    public static function delete($table, $where, $params)
}
```

#### View Layer  
```html
<!-- PHP + HTML Template -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <!-- Bootstrap-style CSS -->
</head>
<body>
    <!-- Dashboard Components -->
    <!-- Client Management Forms -->
    <!-- Statistics Cards -->
</body>
</html>
```

#### Controller Layer
```php
// Business Logic in PHP Files
// clients-simple.php
// - Request Processing
// - Form Validation  
// - Database Operations
// - Response Generation
```

### コンポーネント設計

#### 1. Database Connection Component
```php
class Database {
    // Singleton Pattern
    // Auto-Reconnection
    // Connection Pooling
    // Error Handling
    // Query Logging
}
```

#### 2. Client Management Component
```php
// Client CRUD Operations
// Status Management
// Statistics Aggregation  
// Form Processing
```

#### 3. Dashboard Component
```php
// System Status Monitoring
// Performance Metrics
// Navigation Management
// Alert System
```

---

## 🔄 データフロー設計

### リクエスト処理フロー
```
User Request
     │
     ▼
┌─────────────┐
│ PHP Router  │ (clients-simple.php)
└─────────────┘
     │
     ▼
┌─────────────┐
│ Validation  │ (Input Sanitization)
└─────────────┘
     │
     ▼
┌─────────────┐
│ Database    │ (Connection-simple.php)
│ Operations  │
└─────────────┘
     │
     ▼
┌─────────────┐
│ Response    │ (HTML Generation)
│ Generation  │
└─────────────┘
     │
     ▼
User Interface
```

### データベース操作フロー
```
Application Request
        │
        ▼
┌─────────────────┐
│ Database Class  │
└─────────────────┘
        │
        ▼
┌─────────────────┐
│ Connection Test │ (Auto-Check)
└─────────────────┘
        │
        ▼
┌─────────────────┐
│ Query Execution │ (PDO Prepared)
└─────────────────┘
        │
        ▼
┌─────────────────┐
│ Result Processing│
└─────────────────┘
        │
        ▼
Application Response
```

---

## 🛡️ セキュリティアーキテクチャ

### 入力検証レイヤー
```php
// Input Sanitization
$name = trim($_POST['name'] ?? '');
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

// SQL Injection Prevention  
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);

// XSS Prevention
echo htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8');
```

### 認証・認可 (Future Implementation)
```php
// Session Management
// CSRF Token Validation
// Role-Based Access Control
// API Authentication
```

---

## 📊 パフォーマンス最適化

### データベース最適化
```sql
-- インデックス設計
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_daily_ad_data_date ON daily_ad_data(date);
CREATE INDEX idx_ad_accounts_client ON ad_accounts(client_id);

-- クエリ最適化
-- JOIN操作の最小化
-- 適切なLIMIT使用
-- 集計クエリの効率化
```

### アプリケーション最適化
```php
// Connection Pooling
// Query Caching (Future)
// Lazy Loading
// Pagination Implementation
```

---

## 🔧 環境設定アーキテクチャ

### 設定管理階層
```
┌─────────────────────────────────┐
│    Application Configuration    │
│  ┌─────────────────────────────┐ │
│  │     config-localhost.php    │ │ ← Direct Config
│  └─────────────────────────────┘ │
└─────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────┐
│    Database Configuration       │
│  ┌─────────────────────────────┐ │
│  │   Connection-simple.php     │ │ ← Embedded Config
│  └─────────────────────────────┘ │
└─────────────────────────────────┘
```

### 環境分離
```php
// Development
$config['debug'] = true;
$config['error_reporting'] = E_ALL;

// Production  
$config['debug'] = false;
$config['error_reporting'] = E_ERROR;

// Testing
$config['database'] = 'test_kanho_adsmanager';
```

---

## 🚀 スケーラビリティ設計

### 水平スケーリング準備
```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Web App   │    │   Web App   │    │   Web App   │
│  Instance 1 │    │  Instance 2 │    │  Instance 3 │
└─────────────┘    └─────────────┘    └─────────────┘
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
                           ▼
                ┌─────────────────┐
                │  Load Balancer  │
                └─────────────────┘
                           │
                           ▼
                ┌─────────────────┐
                │    Database     │
                │   (MariaDB)     │
                └─────────────────┘
```

### 垂直スケーリング
```
- CPU: マルチプロセッシング対応
- Memory: 大量データ処理用メモリ最適化  
- Storage: SSD使用、適切なインデックス
- Network: 高速ネットワーク接続
```

---

## 🔄 API設計 (Future Extension)

### RESTful API 構造
```
GET    /api/clients          - クライアント一覧
POST   /api/clients          - クライアント作成
GET    /api/clients/{id}     - クライアント詳細
PUT    /api/clients/{id}     - クライアント更新
DELETE /api/clients/{id}     - クライアント削除

GET    /api/ad-data/{id}     - 広告データ取得
POST   /api/ad-sync          - API同期実行
```

### API認証設計
```php
// JWT Token Authentication
// API Key Management
// Rate Limiting
// Request Logging
```

---

## 📈 監視・ログ設計

### アプリケーションログ
```php
// Error Logging
error_log("Database connection failed: " . $e->getMessage());

// Performance Logging  
$start_time = microtime(true);
// ... operations ...
$execution_time = microtime(true) - $start_time;
```

### システム監視
```
- Database Connection Status
- Response Time Monitoring
- Error Rate Tracking
- Resource Usage Monitoring
```

---

## 🔮 将来のアーキテクチャ拡張

### マイクロサービス化
```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   Client    │  │     Ads     │  │   Billing   │
│  Service    │  │   Service   │  │   Service   │
└─────────────┘  └─────────────┘  └─────────────┘
        │                │                │
        └────────────────┼────────────────┘
                         │
                         ▼
                ┌─────────────┐
                │  API Gateway │
                └─────────────┘
```

### クラウド対応
```
- Docker Containerization
- Kubernetes Orchestration  
- Cloud Database (RDS)
- CDN Integration
- Auto Scaling Groups
```

---

**📅 設計日**: 2024年9月22日  
**🏗️ アーキテクト**: Claude AI Assistant  
**📋 プロジェクト**: Kanho Ads Manager  
**🔧 バージョン**: v1.0 Architecture