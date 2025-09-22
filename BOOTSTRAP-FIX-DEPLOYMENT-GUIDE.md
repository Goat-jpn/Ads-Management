# Bootstrap.php 修復済み - 本番デプロイメントガイド

## 🔧 修復内容

### 問題の特定
- **根本原因**: `bootstrap.php` ファイルが本番環境にアップロードされていませんでした
- **影響**: すべての依存PHPファイル（index.php、clients.php、emergency-check.php等）が500エラーを返していました
- **診断結果**: bootstrap-test.php により `bootstrap.php` ファイルの不在が確認されました

### 修復済みの変更点

1. **PHP 7.4互換性の修復**
   ```php
   // 修復前（PHP 8.0+ match式）
   $errorType = match($severity) {
       E_ERROR, E_CORE_ERROR => 'error',
       E_WARNING, E_CORE_WARNING => 'warning',
       default => 'debug'
   };
   
   // 修復後（PHP 7.4互換 if-elseif）
   if ($severity === E_ERROR || $severity === E_CORE_ERROR) {
       $errorType = 'error';
   } elseif ($severity === E_WARNING || $severity === E_CORE_WARNING) {
       $errorType = 'warning';
   } else {
       $errorType = 'debug';
   }
   ```

2. **型宣言の削除**
   ```php
   // 修復前（PHP 8.0+ 型宣言）
   function config(string $key, $default = null)
   
   // 修復後（PHP 7.4互換）
   function config($key, $default = null)
   ```

## 📦 デプロイメント手順

### STEP 1: パッケージのダウンロード
新しいパッケージをダウンロードしてください：
```
kanho-ads-manager-ads_reports-FINAL-WITH-BOOTSTRAP.tar.gz
```

### STEP 2: FTPアップロード
1. **展開場所**: `/public_html/app/ads_reports/`
2. **重要**: 既存ファイルを上書きしてください
3. **必須ファイル**: `bootstrap.php` が含まれていることを確認

### STEP 3: 初期検証
アップロード後、以下のテストファイルで検証してください：

1. **基本PHP動作確認**
   ```
   https://app.kanho.co.jp/ads_reports/test.php
   ```
   ✅ 期待結果: "PHP Works!" と PHP バージョン表示

2. **Bootstrap検証**
   ```
   https://app.kanho.co.jp/ads_reports/bootstrap-verify.php
   ```
   ✅ 期待結果: すべてのテスト項目が緑色チェックマーク

### STEP 4: アプリケーション動作確認
Bootstrap検証が成功したら、以下のページを順次確認：

1. **緊急診断**
   ```
   https://app.kanho.co.jp/ads_reports/emergency-check.php
   ```

2. **メインページ**
   ```
   https://app.kanho.co.jp/ads_reports/index.php
   ```

3. **クライアント管理**
   ```
   https://app.kanho.co.jp/ads_reports/clients.php
   ```

## 🔍 トラブルシューティング

### 500エラーが続く場合

1. **ファイル存在確認**
   ```bash
   # FTP または cPanel ファイルマネージャーで確認
   /public_html/app/ads_reports/bootstrap.php
   ```

2. **権限確認**
   - ファイル権限: `644` または `664`
   - ディレクトリ権限: `755` または `775`

3. **構文エラー確認**
   ```
   https://app.kanho.co.jp/ads_reports/bootstrap-verify.php
   ```

### よくある問題と解決法

#### 問題 1: bootstrap.php not found
**解決法**: パッケージを再ダウンロードして完全アップロード

#### 問題 2: PHP Fatal Error
**解決法**: bootstrap-verify.php で詳細エラーを確認

#### 問題 3: Database connection error
**解決法**: `.env` ファイルのMariaDB設定を確認

## 📊 含まれているファイル

### 主要コンポーネント
- ✅ `bootstrap.php` - **修復済み PHP 7.4互換**
- ✅ `bootstrap-verify.php` - 新規追加検証ツール
- ✅ `.env` - MariaDB本番設定済み
- ✅ すべてのアプリケーションファイル

### データベース設定
```env
DB_CONNECTION=mysql
DB_HOST=sv301.xbiz.ne.jp
DB_PORT=3306
DB_DATABASE=kanho_adsmanager
DB_USERNAME=kanho_adsmanager
DB_PASSWORD=Kanho20200701
```

## 🎯 次のステップ

Bootstrap修復が完了したら：

1. **データベーステーブル作成**
   - `MARIADB_SETUP.md` の手順に従ってテーブルを作成

2. **API設定**
   - Google Ads API設定
   - Yahoo Ads API設定

3. **動作テスト**
   - 全機能のエンドツーエンドテスト

## 📞 サポート

問題が発生した場合：
1. 必ず `bootstrap-verify.php` の結果を確認
2. エラーメッセージの詳細を記録
3. どのステップで問題が発生したかを明確に

---
**更新日時**: 2025-09-22 06:34 JST  
**パッケージ**: kanho-ads-manager-ads_reports-FINAL-WITH-BOOTSTRAP.tar.gz  
**修復対象**: Bootstrap.php PHP 7.4 互換性問題