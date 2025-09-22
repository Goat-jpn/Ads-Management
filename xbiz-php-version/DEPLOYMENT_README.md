# 広告管理システム - Xbizサーバーデプロイメント手順

## 🎯 システム概要

PHP版広告管理システムのXbizサーバーへのデプロイメント完全ガイドです。

### 📋 システム仕様
- **PHP**: 8.3推奨（Xbizサーバー対応）
- **データベース**: MariaDB 10.5
- **Webサーバー**: Apache + mod_rewrite
- **文字セット**: UTF-8

## 🔧 **STEP 1: FTPアップロード**

### FTP接続情報
```
FTPサーバー(ホスト)名: sv301.xbiz.ne.jp
ユーザー名: app@kanho.co.jp
パスワード: Kanho20200701
対象ドメイン: app.kanho.co.jp
```

### アップロード対象ファイル
以下のファイル・フォルダをすべて `public_html/app/` ディレクトリにアップロードしてください：

```
xbiz-php-version/ の全ファイル
├── .env
├── .htaccess
├── index.php
├── clients.php
├── bootstrap.php
├── composer.json
├── DATABASE_SETUP.sql
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

## ⚙️ **STEP 3: 設定確認**

### 3-1. .env設定確認
アップロードされた `.env` ファイルの内容を確認：

```env
# Xbizサーバー設定
DB_HOST=localhost
DB_NAME=kanho_adsmanager  
DB_USER=kanho_adsmanager
DB_PASS=Kanho20200701
APP_URL=https://app.kanho.co.jp
```

### 3-2. ファイル権限設定
必要に応じてディレクトリ権限を設定：

```bash
# ログディレクトリ権限
chmod 755 logs/
chmod 666 logs/*.log

# 設定ファイル権限  
chmod 600 .env
chmod 644 .htaccess
```

## 🌐 **STEP 4: 動作確認**

### 4-1. 基本動作確認
1. **メインページ**: https://app.kanho.co.jp
2. **ダッシュボード**: https://app.kanho.co.jp/dashboard  
3. **API確認**: https://app.kanho.co.jp/api/dashboard/data

### 4-2. データベース接続確認
ページ右上の「DB接続OK」表示を確認してください。

### 4-3. 機能確認チェックリスト
- [ ] ダッシュボードの統計データ表示
- [ ] クライアント一覧表示
- [ ] 新規クライアント登録  
- [ ] APIデータ取得
- [ ] グラフ表示動作

## 🔧 **トラブルシューティング**

### ❌ データベース接続エラー
**症状**: "DB接続エラー" 表示
**解決策**:
1. `.env` ファイルのDB設定確認
2. MariaDBユーザー権限確認
3. `config/database/Connection.php` 確認

### ❌ 404エラー（ページが見つからない）
**症状**: API呼び出しで404エラー
**解決策**:
1. `.htaccess` が正常にアップロードされているか確認
2. Apache mod_rewrite有効化確認
3. URL rewriteルール確認

### ❌ 権限エラー
**症状**: ファイル書き込みエラー
**解決策**:
1. `logs/` ディレクトリの権限確認（755推奨）
2. ログファイルの権限確認（666推奨）

### ❌ PHP エラー
**症状**: 画面が真っ白またはPHPエラー
**解決策**:
1. PHP version確認（8.3推奨）
2. `bootstrap.php` の存在確認
3. `composer.json` autoload確認

## 📊 **デモデータ**

システムには以下のデモデータが含まれています：

### クライアント
1. **株式会社サンプル商事**
   - Email: tanaka@sample-corp.co.jp
   - 広告アカウント: 3個

2. **テクノロジー株式会社**
   - Email: sato@technology-inc.co.jp  
   - 広告アカウント: 2個

3. **マーケティング合同会社**
   - Email: suzuki@marketing-llc.co.jp
   - 広告アカウント: 2個

### パフォーマンスデータ
- **過去30日分**の広告データ
- **7つの広告アカウント**からのデータ
- **Google Ads**, **Yahoo広告**のサンプルデータ

## 🔑 **管理者アカウント**

### デモ管理者ログイン
- **ユーザー名**: admin
- **パスワード**: password
- **権限**: 管理者

*注：本番環境では必ずパスワードを変更してください*

## 🚀 **次のステップ**

### 1. API認証設定
`.env` ファイルで以下を設定：
- Google Ads API credentials
- Yahoo広告 API credentials

### 2. 実データ移行
- デモデータを削除
- 実際のクライアントデータ投入
- 実際の広告アカウント連携

### 3. セキュリティ強化
- 管理者パスワード変更
- SSL証明書設定確認
- ファイル権限最適化

## 📞 **サポート**

### 動作確認事項
デプロイ完了後、以下をお知らせください：
1. ✅ FTPアップロード完了
2. ✅ データベースセットアップ完了
3. ✅ https://app.kanho.co.jp アクセス確認
4. ✅ ダッシュボードデータ表示確認
5. ✅ APIレスポンス確認

### エラー発生時
エラー発生時は以下の情報をお知らせください：
- エラーメッセージ全文
- 発生したURL
- 実行していた操作
- ブラウザの開発者ツールのエラーログ

---

## ✅ **デプロイメント完了チェック**

### 必須確認項目
- [ ] FTPアップロード完了
- [ ] DATABASE_SETUP.sql実行完了
- [ ] https://app.kanho.co.jp アクセス可能
- [ ] DB接続OK表示
- [ ] ダッシュボード統計データ表示
- [ ] クライアント一覧表示
- [ ] APIレスポンス正常

### 追加設定項目（オプション）
- [ ] Google Ads API設定
- [ ] Yahoo広告API設定  
- [ ] メール設定
- [ ] バックアップ設定
- [ ] 監視設定

---

**🎊 デプロイメント完了後、フル機能の広告管理システムが利用可能になります！**