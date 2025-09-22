# 広告管理システム MariaDB セットアップ完了

## 🎯 システム概要

広告管理システムが MariaDB データベースとの統合で正常に動作しています。

### データベース接続情報
- **データベースサーバー**: MariaDB 10.5
- **データベース名**: kanho_adsmanager  
- **ユーザー**: kanho_adsmanager
- **パスワード**: Kanho20200701
- **文字セット**: UTF-8

## 🚀 アクセス情報

### Web アプリケーション
**メインURL**: https://8080-iuw1rzlgvr4jfgkzvsvpj-6532622b.e2b.dev

#### 主要エンドポイント
- **ダッシュボード**: https://8080-iuw1rzlgvr4jfgkzvsvpj-6532622b.e2b.dev/dashboard
- **クライアント管理**: https://8080-iuw1rzlgvr4jfgkzvsvpj-6532622b.e2b.dev/clients  
- **広告アカウント**: https://8080-iuw1rzlgvr4jfgkzvsvpj-6532622b.e2b.dev/ad-accounts
- **請求管理**: https://8080-iuw1rzlgvr4jfgkzvsvpj-6532622b.e2b.dev/invoices

#### API エンドポイント
- **ダッシュボードデータ**: `/api/dashboard/data`
- **クライアント一覧**: `/api/clients` 
- **広告データ**: `/api/ad-data`
- **請求データ**: `/api/invoices`
- **費用マークアップ**: `/api/cost-markups`

## 📊 システム機能

### ✅ 実装済み機能

#### 1. クライアント管理
- クライアント情報の登録・編集・削除
- 契約情報管理（開始日、終了日、請求日）
- 支払い条件設定

#### 2. 広告アカウント管理  
- Google Ads, Yahoo広告 アカウント統合
- 複数プラットフォーム対応
- API連携準備済み

#### 3. パフォーマンスダッシュボード
- リアルタイムデータ表示
- KPI分析（CPA, CPC, CTR等）
- グラフィカルな可視化

#### 4. 費用管理・マークアップ
- 実際の広告費用管理
- レポート用費用マークアップ
- 手数料計算（固定・割合・段階制）

#### 5. 請求・インボイス管理
- 自動請求書生成
- 月次サマリー
- 支払い追跡

### 📈 デモデータ

システムには以下のデモデータが含まれています：

#### クライアント (3社)
1. **株式会社サンプル商事** - 3アカウント
2. **テクノロジー株式会社** - 2アカウント  
3. **マーケティング合同会社** - 2アカウント

#### パフォーマンスデータ (過去30日間)
- **総広告費**: ¥17,695,746
- **レポート費用**: ¥18,405,788
- **インプレッション**: 2,248,714
- **クリック**: 80,992  
- **コンバージョン**: 3,743

## 🔧 技術仕様

### サーバー環境
- **Node.js**: v18+ 
- **PM2**: プロセス管理
- **Express**: Webサーバー
- **MySQL2**: データベース接続

### データベーススキーマ
```sql
-- 主要テーブル
- admins (管理者)
- clients (クライアント)
- fee_settings (手数料設定)
- ad_accounts (広告アカウント)
- daily_ad_data (日次広告データ)
- monthly_summaries (月次サマリー)
- cost_markups (費用マークアップ)
- invoices (請求書)
```

## 🔑 API 統合

### Google Ads API
```env
GOOGLE_ADS_DEVELOPER_TOKEN=your-developer-token-here
GOOGLE_ADS_CLIENT_ID=your-client-id-here
GOOGLE_ADS_CLIENT_SECRET=your-client-secret-here
GOOGLE_ADS_REFRESH_TOKEN=your-refresh-token-here
GOOGLE_ADS_LOGIN_CUSTOMER_ID=your-login-customer-id-here
```

### Yahoo広告 API
```env
# Display Ads
YAHOO_DISPLAY_APP_ID=your-app-id-here
YAHOO_DISPLAY_SECRET=your-secret-here
YAHOO_DISPLAY_REFRESH_TOKEN=your-refresh-token-here

# Search Ads  
YAHOO_SEARCH_LICENSE_ID=your-license-id-here
YAHOO_SEARCH_API_ACCOUNT_ID=your-api-account-id-here
```

## 🛠️ 運用コマンド

### サーバー管理
```bash
# ステータス確認
npx pm2 status

# ログ確認  
npx pm2 logs kanho-ads-manager --nostream

# 再起動
npx pm2 restart kanho-ads-manager

# 停止
npx pm2 stop kanho-ads-manager
```

### データベース管理
```bash
# MariaDBセットアップ（PHP環境で実行）
php setup_mariadb.php

# バックアップ
mysqldump -u kanho_adsmanager -p kanho_adsmanager > backup.sql
```

## 📞 サポート

システムに関する質問やカスタマイズ要望は、開発チームまでお問い合わせください。

---

## 🎊 セットアップ完了！

**広告管理システムは正常に動作しています。**  
上記のURLからアクセスして、システムの機能をご確認ください。

**次のステップ:**
1. Google Ads / Yahoo広告のAPI認証情報を設定
2. 実際のクライアントデータを登録  
3. 本番環境への移行準備