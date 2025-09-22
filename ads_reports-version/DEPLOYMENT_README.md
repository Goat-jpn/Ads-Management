# 広告管理システム - app.kanho.co.jp/ads_reports デプロイメント手順

## 🎯 システム概要

PHP版広告管理システムの `app.kanho.co.jp/ads_reports` ディレクトリへのデプロイメント完全ガイドです。

### 📋 システム仕様
- **PHP**: 8.3推奨（Xbizサーバー対応）
- **データベース**: MariaDB 10.5
- **Webサーバー**: Apache + mod_rewrite
- **文字セット**: UTF-8
- **配置場所**: `/public_html/app/ads_reports/`

## 🔧 **STEP 1: FTPアップロード**

### FTP接続情報
```
FTPサーバー(ホスト)名: sv301.xbiz.ne.jp
ユーザー名: app@kanho.co.jp
パスワード: Kanho20200701
対象ディレクトリ: /public_html/app/ads_reports/
```

### アップロード対象ファイル
以下のファイル・フォルダをすべて `/public_html/app/ads_reports/` ディレクトリにアップロードしてください：

```
ads_reports-version/ の全ファイル
├── .env (ads_reports用に設定済み)
├── .htaccess (ads_reports用URL設定済み)
├── index.php
├── clients.php
├── bootstrap.php
├── composer.json
├── DATABASE_SETUP.sql
├── check-deployment.php
├── api/
│   ├── dashboard/
│   │   └── data.php
│   └── clients/
│       └── index.php
├── app/
│   ├── controllers/
│   ├── models/
│   └── utils/
├── config/
├── public/
└── database/
```

### 📂 **正しいディレクトリ構造**
```
/public_html/app/ads_reports/
├── index.php
├── clients.php
├── .env
├── .htaccess
├── api/
├── app/
├── config/
└── public/
```

## 🗄️ **STEP 2: データベースセットアップ**

### 2-1. MariaDBアクセス
Xbizコントロールパネルから以下を確認：
- **ホスト名**: localhost
- **データベース名**: kanho_adsmanager
- **ユーザー名**: kanho_adsmanager  
- **パスワード**: Kanho20200701

### 2-2. SQLファイル実行
`DATABASE_SETUP.sql` をMariaDBで実行：

```sql
-- XbizのphpMyAdminまたはSSHから実行
mysql -u kanho_adsmanager -p kanho_adsmanager < DATABASE_SETUP.sql
```

または、phpMyAdminの「インポート」機能で `DATABASE_SETUP.sql` をアップロード実行。

## 🌐 **STEP 3: アクセス確認**

### 3-1. 動作確認URL
1. **動作確認ツール**: https://app.kanho.co.jp/ads_reports/check-deployment.php
2. **メインダッシュボード**: https://app.kanho.co.jp/ads_reports/
3. **クライアント管理**: https://app.kanho.co.jp/ads_reports/clients
4. **API確認**: https://app.kanho.co.jp/ads_reports/api/dashboard/data

### 3-2. 設定確認項目
- [ ] DB接続OK表示の確認
- [ ] ダッシュボード統計データ表示
- [ ] ナビゲーションリンク動作確認
- [ ] API レスポンス確認
- [ ] クライアント管理機能確認

## ⚙️ **STEP 4: 設定ファイル確認**

### 4-1. .env設定確認
アップロードされた `.env` ファイルの内容：

```env
# ads_reports 専用設定
DB_HOST=localhost
DB_NAME=kanho_adsmanager  
DB_USER=kanho_adsmanager
DB_PASS=Kanho20200701
APP_URL=https://app.kanho.co.jp/ads_reports
DOCUMENT_ROOT=/home/app@kanho.co.jp/kanho.co.jp/public_html/app/ads_reports/
```

### 4-2. URL構造
```
ベースURL: https://app.kanho.co.jp/ads_reports/

ページURL:
├── / (ダッシュボード)
├── /dashboard (ダッシュボード)
├── /clients (クライアント管理)
├── /ad-accounts (広告アカウント)
└── /invoices (請求管理)

API URL:
├── /api/dashboard/data (ダッシュボードデータ)
├── /api/clients (クライアント一覧)
├── /api/clients/123 (個別クライアント)
└── /api/invoices (請求データ)
```

## 🔧 **トラブルシューティング**

### ❌ 404 Not Found エラー
**症状**: ページや API で 404 エラー
**解決策**:
1. `.htaccess` ファイルが正常にアップロードされているか確認
2. Apache mod_rewrite が有効化されているか確認
3. ファイルパスが `/ads_reports/` 以下に正しく配置されているか確認

### ❌ データベース接続エラー
**症状**: "DB接続エラー" 表示
**解決策**:
1. `.env` ファイルのDB設定確認
2. MariaDBユーザー権限確認
3. データベース名 `kanho_adsmanager` の存在確認

### ❌ Internal Server Error (500)
**症状**: 画面が真っ白またはサーバーエラー
**解決策**:
1. `logs/php_errors.log` でエラー内容確認
2. ファイル権限確認（755/644推奨）
3. PHP version確認（8.3推奨）

### ❌ ナビゲーションリンクエラー  
**症状**: メニューリンクで404エラー
**解決策**:
1. すべてのリンクが `/ads_reports/` で始まっているか確認
2. `.htaccess` のURL rewriteルール確認

## 📊 **デモデータ**

システムには以下のデモデータが含まれています：

### クライアント（3社）
1. **株式会社サンプル商事**
   - Email: tanaka@sample-corp.co.jp
   - 広告アカウント: 3個
   - 月間広告費: ¥7,791,199

2. **テクノロジー株式会社**
   - Email: sato@technology-inc.co.jp  
   - 広告アカウント: 2個
   - 月間広告費: ¥4,952,348

3. **マーケティング合同会社**
   - Email: suzuki@marketing-llc.co.jp
   - 広告アカウント: 2個
   - 月間広告費: ¥4,952,199

### パフォーマンスデータ
- **過去30日分**の広告データ
- **総広告費**: ¥17,695,746
- **総インプレッション**: 2,248,714
- **総クリック**: 80,992
- **総コンバージョン**: 3,743

## 🔑 **管理者アカウント**

### デモ管理者ログイン
- **ユーザー名**: admin
- **Email**: admin@kanho.co.jp
- **パスワード**: password
- **権限**: 管理者

*注：本番環境では必ずパスワードを変更してください*

## 🚀 **次のステップ**

### 1. セキュリティ設定
- `check-deployment.php` ファイルの削除（確認完了後）
- 管理者パスワード変更
- ファイル権限最適化

### 2. API認証設定
`.env` ファイルで以下を設定：
- Google Ads API credentials
- Yahoo広告 API credentials

### 3. 実データ移行
- デモデータを削除
- 実際のクライアントデータ投入
- 実際の広告アカウント連携

## 📞 **サポート確認事項**

### デプロイ完了チェックリスト
- [ ] FTPアップロード完了（/ads_reports/ 以下）
- [ ] DATABASE_SETUP.sql実行完了
- [ ] https://app.kanho.co.jp/ads_reports/ アクセス確認
- [ ] https://app.kanho.co.jp/ads_reports/check-deployment.php で全項目OK
- [ ] ダッシュボードデータ表示確認
- [ ] クライアント管理機能確認
- [ ] API レスポンス確認
- [ ] ナビゲーション動作確認

### エラー発生時の情報
エラー発生時は以下の情報をお知らせください：
- エラーメッセージ全文
- 発生したURL（/ads_reports/以下の）
- 実行していた操作
- check-deployment.php の結果

---

## ✅ **ads_reports デプロイメント完了確認**

### 必須URL確認
- [ ] **メイン**: https://app.kanho.co.jp/ads_reports/
- [ ] **チェックツール**: https://app.kanho.co.jp/ads_reports/check-deployment.php
- [ ] **クライアント**: https://app.kanho.co.jp/ads_reports/clients
- [ ] **API**: https://app.kanho.co.jp/ads_reports/api/dashboard/data

---

**🎊 デプロイメント完了後、https://app.kanho.co.jp/ads_reports/ で フル機能の広告管理システムが利用可能になります！**