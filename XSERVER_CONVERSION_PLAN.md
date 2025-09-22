# Xserver対応 PHP版変換計画

## 🎯 変換方針

Node.jsアプリケーションをXserver対応のPHP版に変換します。

### 📋 変換が必要なコンポーネント

#### 1. サーバー部分
**現在 (Node.js)**:
```javascript
// mariadb-server.js
const express = require('express');
const mysql = require('mysql2');
```

**変換後 (PHP)**:
```php
// index.php
<?php
require_once 'config/database/Connection.php';
```

#### 2. API エンドポイント
**現在**: Express.js ルーティング
**変換後**: PHP個別ファイル構造
```
api/
├── dashboard/data.php
├── clients/index.php
├── ad-accounts/index.php
└── invoices/index.php
```

#### 3. データベース接続
**現在**: mysql2 (Node.js)
**変換後**: PDO (PHP)

### 🔧 具体的変換作業

#### Step 1: PHPファイル構造作成
```
xserver-version/
├── index.php (メインダッシュボード)
├── config/
│   └── database/
│       └── Connection.php (既存)
├── api/
│   ├── dashboard/
│   │   └── data.php (API変換)
│   ├── clients/
│   │   └── index.php
│   └── invoices/
│       └── index.php
├── public/
│   ├── css/
│   ├── js/
│   └── assets/
└── app/
    ├── models/ (既存PHPクラス)
    └── controllers/ (既存PHPクラス)
```

#### Step 2: Express.js → PHP変換
**Dashboard API例**:
```php
<?php
// api/dashboard/data.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database/Connection.php';
require_once '../../app/models/Client.php';

try {
    $client = new Client();
    $summary = $client->getDashboardSummary();
    
    echo json_encode([
        'success' => true,
        'data' => $summary
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

#### Step 3: フロントエンド適応
```javascript
// public/js/dashboard.js
// API URLをPHP版に変更
const API_BASE = '/api';  // Node.js版
const API_BASE = './api'; // PHP版
```

### 🌐 Xserver固有の対応

#### 1. .htaccess設定
```apache
# .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1.php [QSA,L]

# セキュリティ設定
<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
```

#### 2. PHP設定対応
- **PHPバージョン**: 8.3（Xserver推奨）
- **composer.json**: PHP依存関係管理
- **環境変数**: .env → PHP設定

#### 3. MariaDB接続設定
```php
// Xserver MySQL接続設定
$config = [
    'host' => 'mysql**.xserver.jp',  // Xserver MySQL
    'dbname' => 'kanho_adsmanager',
    'username' => 'kanho_adsmanager',
    'password' => 'Kanho20200701',
    'charset' => 'utf8mb4'
];
```

## 📦 作業手順

### Phase 1: PHP版作成
1. ✅ 既存PHPクラス活用
2. 🔄 Node.js API → PHP API変換
3. 🔄 フロントエンド適応

### Phase 2: Xserver対応
1. 🔄 データベース設定調整
2. 🔄 .htaccess作成
3. 🔄 セキュリティ設定

### Phase 3: デプロイ
1. 🔄 FTPアップロード
2. 🔄 データベース初期化
3. 🔄 動作確認

## 🕐 所要時間

- **PHP版変換**: 2-3時間
- **Xserver適応**: 1時間
- **テスト・調整**: 1-2時間
- **総計**: 4-6時間

## 📋 必要情報

Xserverでの作業に必要な情報：
- Xserver FTP情報
- MySQL情報（ホスト名、DB名、ユーザー名、パスワード）
- ドメイン/サブドメイン設定

---

**💡 結論**: 完全にPHP版に変換すれば、Xserverで正常動作します！