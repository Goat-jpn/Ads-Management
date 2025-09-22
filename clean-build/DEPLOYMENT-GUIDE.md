# 🚀 Kanho Ads Manager - デプロイメントガイド

## 📋 デプロイメント概要

このガイドでは、Kanho Ads Managerをapp.kanho.co.jp/ads_reports/にデプロイする完全な手順を説明します。

### 🎯 デプロイ対象環境
- **URL**: `https://app.kanho.co.jp/ads_reports/`
- **サーバー**: Xbizサーバー (sv301.xbiz.ne.jp)  
- **PHP**: 7.4.33
- **Database**: MariaDB 10.5
- **接続**: localhost (shared hosting)

---

## 📦 事前準備

### 必要なファイル確認
```
✅ kanho-ads-manager-ftp-ready.zip (25KB)
✅ FTP-UPLOAD-GUIDE.md
✅ PROJECT-SUMMARY-DOCUMENTATION.md
✅ TECHNICAL-ARCHITECTURE.md  
✅ DEPLOYMENT-GUIDE.md (本ファイル)
```

### FTPクライアント準備
- FileZilla, WinSCP, または任意のFTPクライアント
- XbizサーバーのFTP接続情報
- 適切なファイル転送モード（ASCII/Binary）

---

## 🔧 Step 1: ファイルのアップロード

### 1-1. ZIPファイル展開
```bash
# ローカルでZIPファイルを展開
unzip kanho-ads-manager-ftp-ready.zip
cd ftp-upload/
```

### 1-2. ディレクトリ構造確認
```
ftp-upload/
├── clients-simple.php              # メインクライアント管理
├── index-simple.php                # アプリケーションダッシュボード
├── setup-improved.php              # データベースセットアップ
├── test-connection.php             # 接続診断ツール
├── database-setup-simple.sql       # データベーススキーマ
├── config-localhost.php            # メイン設定ファイル
├── config/
│   └── database/
│       └── Connection-simple.php   # データベース接続クラス
├── app/
│   └── utils/
│       └── Environment-direct.php  # 環境設定クラス
├── clients-original.php            # 参考ファイル
├── index-original.php              # 参考ファイル
└── setup-original.php              # 参考ファイル
```

### 1-3. FTPアップロード
```
FTP接続設定:
Host: app.kanho.co.jp (またはXbizサーバー情報)
Directory: /ads_reports/

アップロード対象:
- 上記全ファイル・フォルダ
- ディレクトリ構造を維持
```

### 1-4. ファイル権限設定
```bash
# PHPファイル
chmod 644 *.php

# SQLファイル  
chmod 644 *.sql

# ディレクトリ
chmod 755 config/
chmod 755 config/database/
chmod 755 app/
chmod 755 app/utils/
```

---

## 🔍 Step 2: 接続テスト

### 2-1. 基本接続確認
```
URL: https://app.kanho.co.jp/ads_reports/test-connection.php
```

**期待される結果:**
```
✅ 基本接続成功
テスト値: 1
現在時刻: 2024-09-22 17:30:00
```

**エラーが出た場合:**
```html
❌ 基本接続失敗: SQLSTATE[HY000] [2006] MySQL server has gone away
→ データベース設定を確認
```

### 2-2. トラブルシューティング

#### データベース接続エラー
```php
// config-localhost.php の設定確認
$_ENV['DB_HOST'] = 'localhost';        // ← localhostが正しい
$_ENV['DB_DATABASE'] = 'kanho_adsmanager';
$_ENV['DB_USERNAME'] = 'kanho_adsmanager';
$_ENV['DB_PASSWORD'] = 'Kanho20200701';
```

#### ファイル権限エラー
```bash
# 500 Internal Server Errorの場合
chmod 644 test-connection.php
chmod 644 config-localhost.php
chmod 755 config/
```

#### PHPエラー
```php
// PHP 7.4.33 互換性確認
// 以下の構文は使用不可:
// - Null coalescing assignment (??=)  
// - Match expression
// - Constructor property promotion
```

---

## 🛢️ Step 3: データベースセットアップ

### 3-1. セットアップ実行
```
URL: https://app.kanho.co.jp/ads_reports/setup-improved.php
```

**正常なセットアップ流れ:**
```
Step 1: Connection Test
✅ データベース接続成功

Step 2: Table Setup  
✅ SQL file loaded (9,134 characters)
✅ Created table: clients
✅ Created table: ad_accounts
✅ Created table: daily_ad_data
✅ Created table: cost_markups
✅ Created table: fee_settings
✅ Created table: tiered_fees
✅ Created table: invoices
✅ Created table: invoice_items
✅ Inserted data into: clients
✅ Inserted data into: ad_accounts

Step 3: Verification
✅ Successful: 16
❌ Errors: 0
```

### 3-2. テーブル作成確認
```sql
-- 以下のテーブルが作成される
- clients (3 records)
- ad_accounts (6 records)  
- daily_ad_data (0 records)
- cost_markups (0 records)
- fee_settings (3 records)
- tiered_fees (9 records)
- invoices (0 records)
- invoice_items (0 records)
```

### 3-3. エラー対応

#### テーブル作成失敗
```sql
-- 権限確認
SHOW GRANTS FOR 'kanho_adsmanager'@'localhost';

-- データベース存在確認  
SHOW DATABASES LIKE 'kanho_adsmanager';

-- 手動テーブル作成（緊急時）
SOURCE database-setup-simple.sql;
```

---

## 📊 Step 4: アプリケーション起動

### 4-1. ダッシュボード確認
```
URL: https://app.kanho.co.jp/ads_reports/index-simple.php
```

**期待される表示:**
```html
✅ データベース接続 - 正常に動作しています
📊 データベーステーブル - 8個のテーブルが利用可能です

統計:
- 登録クライアント数: 3  
- データベーステーブル: 8
- システム状態: 正常
```

### 4-2. クライアント管理確認  
```
URL: https://app.kanho.co.jp/ads_reports/clients-simple.php
```

**期待される機能:**
```
✅ データベース接続成功表示
✅ 既存クライアント一覧表示
✅ 新規クライアント追加フォーム
✅ ステータス変更ボタン
✅ 統計カード表示
```

---

## ⚙️ Step 5: 設定の最適化

### 5-1. パフォーマンス設定
```php
// config-localhost.php に追加設定
$_ENV['DB_TIMEOUT'] = '60';
$_ENV['DB_RETRY_ATTEMPTS'] = '3';
$_ENV['APP_CACHE_ENABLED'] = 'false';
```

### 5-2. セキュリティ設定
```php  
// 本番環境用設定
$_ENV['APP_DEBUG'] = 'false';        # デバッグ無効
$_ENV['APP_ENV'] = 'production';     # 本番モード
ini_set('display_errors', 0);       # エラー非表示
```

### 5-3. ログ設定
```php
// エラーログ設定
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');

// アプリケーションログ
$_ENV['LOG_LEVEL'] = 'error';
$_ENV['LOG_PATH'] = 'logs/app.log';
```

---

## 🔒 Step 6: セキュリティ設定

### 6-1. ファイル保護
```apache
# .htaccess 作成（config/ディレクトリ）
<Files "*.php">
    Deny from all
</Files>

<Files "Connection-simple.php">
    Allow from all
</Files>
```

### 6-2. データベースセキュリティ
```sql
-- 不要な権限削除
REVOKE ALL PRIVILEGES ON *.* FROM 'kanho_adsmanager'@'localhost';

-- 必要最小限の権限付与
GRANT SELECT, INSERT, UPDATE, DELETE 
ON kanho_adsmanager.* 
TO 'kanho_adsmanager'@'localhost';
```

### 6-3. SSL/HTTPS確認
```
✅ https://app.kanho.co.jp で動作確認
✅ 混合コンテンツ警告がないこと
✅ セキュアクッキー設定
```

---

## 📈 Step 7: 運用開始

### 7-1. 動作確認チェックリスト
```
基本機能:
□ ダッシュボード表示
□ クライアント一覧表示  
□ クライアント新規追加
□ クライアントステータス変更
□ データベース接続安定性

エラーハンドリング:
□ 不正入力の処理
□ データベース切断時の挙動
□ ファイル権限エラーの処理

パフォーマンス:
□ ページ読み込み速度
□ データベースクエリ実行時間
□ 同時接続処理
```

### 7-2. 継続監視項目
```
日次チェック:
- アプリケーション可用性
- データベース接続状態
- エラーログ確認

週次チェック:  
- パフォーマンス指標
- データベース容量
- セキュリティログ

月次チェック:
- 全機能動作確認
- データバックアップ
- システムアップデート確認
```

---

## 🚨 トラブルシューティング

### よくある問題と解決策

#### 1. "MySQL server has gone away"
```
原因: データベース接続タイムアウト
解決: Connection-simple.php の自動再接続機能で解決済み
確認: test-connection.php でステータス確認
```

#### 2. 500 Internal Server Error
```
原因: ファイル権限、PHP構文エラー
解決: 
- chmod 644 *.php
- PHP 7.4.33 互換性確認  
- エラーログ確認
```

#### 3. ファイルが見つからない
```
原因: ディレクトリ構造、ファイルパス
解決:
- FTPアップロード構造確認
- 相対パス設定確認
- ファイル名大文字小文字確認
```

#### 4. データベーステーブル未作成
```
原因: SQL実行権限、データベース接続
解決:
- setup-improved.php 再実行
- 手動SQLファイル実行
- データベース権限確認
```

---

## 📞 緊急時対応手順

### 1. サービス停止時
```
1. test-connection.php でデータベース状態確認
2. エラーログ確認 (logs/php_errors.log)
3. FTPでファイル権限確認
4. データベースサーバー状態確認
```

### 2. データ破損時
```
1. データベースバックアップから復元
2. setup-improved.php でテーブル再作成
3. 必要に応じて手動データ投入
```

### 3. パフォーマンス問題
```
1. データベース接続プール確認
2. クエリ実行時間測定
3. サーバーリソース使用量確認
4. 必要に応じてインデックス追加
```

---

## 🔮 今後の拡張計画

### Phase 2: API統合
```
Google Ads API:
- OAuth 2.0 認証設定
- API キー管理
- データ同期バッチ処理

Yahoo Ads API:  
- API 接続設定
- データ取得スケジュール
- エラーハンドリング
```

### Phase 3: 高度な機能
```
レポート機能:
- PDF生成ライブラリ導入
- グラフ表示機能
- メール配信機能

請求管理:
- 請求書自動生成
- 支払い状況管理
- 督促機能
```

---

## 📋 デプロイメントチェックシート

### 事前確認
```
□ FTPアクセス情報確認
□ データベース接続情報確認  
□ 必要ファイル準備完了
□ バックアップ取得
```

### アップロード確認
```
□ 全ファイルアップロード完了
□ ディレクトリ構造正常
□ ファイル権限設定完了
□ .htaccess 設定完了
```

### 動作確認  
```
□ test-connection.php 成功
□ setup-improved.php 成功
□ index-simple.php 表示
□ clients-simple.php 動作
□ 全機能テスト完了
```

### 本番化設定
```
□ デバッグモード無効
□ エラー表示無効  
□ ログ設定完了
□ セキュリティ設定完了
□ 監視設定完了
```

---

**🎉 デプロイメント完了！**

このガイドに従ってデプロイを実行すれば、Kanho Ads Managerが安定して動作するはずです。問題が発生した場合は、トラブルシューティングセクションを参照してください。

**📅 作成日**: 2024年9月22日  
**🚀 デプロイガイド**: v1.0  
**📋 対象**: Kanho Ads Manager