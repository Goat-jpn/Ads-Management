# Kanho Ads Manager - クリーンビルド版

## 🎯 概要

このパッケージは、500エラーの問題を解決するために一からシンプルに構築されたKanho Ads Managerです。

## 📁 ファイル構成

```
clean-build/
├── index.php              # メインページ
├── clients.php            # クライアント管理
├── dashboard.php          # ダッシュボード
├── test-minimal.php       # 最小限テスト
├── test-info.php          # PHP情報
├── test-env.php           # 環境変数テスト
├── test-db.php            # データベーステスト
├── setup-database.php     # データベースセットアップ
├── database-setup.sql     # テーブル作成SQL
├── .env                   # 環境設定
├── app/
│   └── utils/
│       └── Environment.php # 環境変数クラス
└── config/
    └── database/
        └── Connection.php  # データベース接続クラス
```

## 🚀 デプロイ手順

### STEP 1: アップロード
1. パッケージを `/public_html/app/ads_reports/` に展開
2. ファイル権限を設定（644 for files, 755 for directories）

### STEP 2: 段階的テスト
以下の順序で必ずテストしてください：

1. **最小限テスト**
   ```
   https://app.kanho.co.jp/ads_reports/test-minimal.php
   ```
   期待結果: "PHP Works!"

2. **PHP情報確認**
   ```
   https://app.kanho.co.jp/ads_reports/test-info.php
   ```
   期待結果: PHP設定情報とextension一覧

3. **環境変数テスト**
   ```
   https://app.kanho.co.jp/ads_reports/test-env.php
   ```
   期待結果: .env ファイルからの設定値表示

4. **データベーステスト**
   ```
   https://app.kanho.co.jp/ads_reports/test-db.php
   ```
   期待結果: MariaDB接続成功とデータベース情報

### STEP 3: データベースセットアップ
```
https://app.kanho.co.jp/ads_reports/setup-database.php
```
このページでテーブル作成とサンプルデータ挿入を実行

### STEP 4: アプリケーション確認
1. **メインページ**
   ```
   https://app.kanho.co.jp/ads_reports/index.php
   ```

2. **クライアント管理**
   ```
   https://app.kanho.co.jp/ads_reports/clients.php
   ```

3. **ダッシュボード**
   ```
   https://app.kanho.co.jp/ads_reports/dashboard.php
   ```

## 🔧 技術仕様

- **PHP要件**: PHP 7.4+ 対応
- **データベース**: MariaDB 10.5
- **依存関係**: PHP標準機能のみ（外部ライブラリなし）
- **文字エンコーディング**: UTF-8

## 🌟 主な機能

### ✅ 実装済み機能
- ✅ 環境変数管理
- ✅ MariaDBデータベース接続
- ✅ クライアント管理（CRUD）
- ✅ 基本ダッシュボード
- ✅ レスポンシブデザイン
- ✅ エラーハンドリング

### 🔄 将来の拡張予定
- Google Ads API 連携
- Yahoo Ads API 連携
- 詳細レポート機能
- 請求書生成
- ユーザー認証

## 📊 データベース構造

### テーブル一覧
- `clients` - クライアント情報
- `ad_accounts` - 広告アカウント
- `daily_ad_data` - 日別広告データ
- `cost_markups` - コストマークアップ設定
- `fee_settings` - 手数料設定
- `tiered_fees` - 階層手数料
- `invoices` - 請求書
- `invoice_items` - 請求明細

## 🚨 トラブルシューティング

### 500エラーが発生する場合
1. まず `test-minimal.php` で基本PHP動作を確認
2. ファイル権限を確認（644/755）
3. `.env` ファイルが正しく配置されているか確認
4. エラーログを確認

### データベース接続エラー
1. `.env` ファイルの接続情報を確認
2. `test-db.php` でデータベース接続をテスト
3. MariaDBサーバーの状態を確認

## 📞 サポート

問題が発生した場合は、以下の情報を提供してください：
1. どのテストページで問題が発生したか
2. 具体的なエラーメッセージ
3. ブラウザの開発者ツールのConsoleエラー

---
**作成日**: 2025-09-22  
**バージョン**: 1.0.0 Clean Build  
**対象環境**: Xbiz PHP 7.4.33, MariaDB 10.5