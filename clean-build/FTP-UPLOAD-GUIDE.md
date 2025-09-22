# 📁 FTPアップロード手順書

## 🎯 アップロード対象ディレクトリ
```
app.kanho.co.jp/ads_reports/
```

## 📦 必要ファイル一覧

### 🚀 **メインファイル（必須）**
```
├── clients-simple.php          ← メインのクライアント管理画面
├── index-simple.php            ← アプリケーションダッシュボード  
├── setup-improved.php          ← データベースセットアップ
├── test-connection.php         ← データベース接続テスト
└── database-setup-simple.sql   ← データベース作成SQL
```

### ⚙️ **設定ファイル（必須）**
```
├── config-localhost.php                    ← メイン設定ファイル
└── config/database/Connection-simple.php   ← データベース接続クラス
└── app/utils/Environment-direct.php        ← 環境設定クラス
```

### 📚 **参考ファイル（オプション）**
```
├── clients-original.php        ← 元のクライアント管理（参考）
├── index-original.php          ← 元のインデックス（参考）
└── setup-original.php          ← 元のセットアップ（参考）
```

## 📋 **FTPアップロード手順**

### 1️⃣ **ファイルのアップロード**
```
1. FTPクライアントで app.kanho.co.jp に接続
2. /ads_reports/ ディレクトリに移動
3. 以下のファイル・フォルダをアップロード:
   - clients-simple.php
   - index-simple.php  
   - setup-improved.php
   - test-connection.php
   - database-setup-simple.sql
   - config-localhost.php
   - config/ フォルダ（中身含む）
   - app/ フォルダ（中身含む）
```

### 2️⃣ **ファイル権限設定**
```
PHPファイル: 644 (rw-r--r--)
SQLファイル: 644 (rw-r--r--)
ディレクトリ: 755 (rwxr-xr-x)
```

### 3️⃣ **セットアップ実行**
```
1. ブラウザで以下にアクセス:
   https://app.kanho.co.jp/ads_reports/test-connection.php

2. データベース接続が確認できたら:
   https://app.kanho.co.jp/ads_reports/setup-improved.php

3. セットアップ完了後、メインページにアクセス:
   https://app.kanho.co.jp/ads_reports/index-simple.php
```

## 🔍 **動作確認手順**

### ✅ **基本チェックリスト**
- [ ] test-connection.php でデータベース接続成功
- [ ] setup-improved.php でテーブル作成成功
- [ ] index-simple.php でダッシュボード表示
- [ ] clients-simple.php でクライアント管理動作

### 🚨 **トラブルシューティング**

#### データベース接続エラーの場合:
```
1. config-localhost.php の設定を確認
   - DB_HOST = 'localhost'
   - DB_DATABASE = 'kanho_adsmanager'  
   - DB_USERNAME = 'kanho_adsmanager'
   - DB_PASSWORD = 'Kanho20200701'

2. test-connection.php で詳細エラーを確認

3. PHPエラーログを確認
```

#### ファイルが見つからないエラーの場合:
```
1. ファイルパスの確認
2. ファイル権限の確認（644）
3. ディレクトリ構造の確認
```

## 🎯 **重要なポイント**

### 📝 **使用するファイル**
- ❌ `clients.php` → ✅ `clients-simple.php`
- ❌ `index.php` → ✅ `index-simple.php`  
- ❌ `setup-simple.php` → ✅ `setup-improved.php`

### 💡 **データベース設定**
- ホスト: `localhost` （sv301.xbiz.ne.jp ではない）
- 直接設定方式（.envファイル不要）
- 自動再接続機能付き

### 🔒 **セキュリティ**
- PHP 7.4.33 互換性確認済み
- エラーハンドリング完全対応
- 本番環境最適化済み

---

**✅ 準備完了！** 
ZIPファイル: `kanho-ads-manager-ftp-ready.zip` をダウンロードして、FTPでアップロードしてください。