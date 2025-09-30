# 🚀 Kanho Ads Manager v2.0 - 要件定義書（改訂版）

## 📋 プロジェクト概要

### プロジェクト名
**Kanho Ads Manager v2.0** - 実用的広告管理・請求システム

### 開発目的
Google Ads・Yahoo Ads APIとの統合による効率的な広告管理と、透明性の高い請求処理を実現する実用的なWebアプリケーションを開発する。

---

## 🎯 機能要件定義

### 🔥 **Core Features（必須機能）**

#### 1. 認証・ユーザー管理
```
📋 機能概要:
- ユーザー登録・ログイン・ログアウト
- パスワードリセット・変更
- プロフィール管理
- 二要素認証（2FA）

🎯 要求仕様:
- セキュアなパスワード管理（bcrypt）
- セッション管理（Redis推奨）
- OAuth2.0対応（Google/Yahoo連携用）
- RBAC（Role-Based Access Control）

📊 画面:
- ログイン画面
- ユーザー登録画面
- プロフィール編集画面
- 2FA設定画面
```

#### 2. クライアント管理システム
```
📋 機能概要:
- クライアント CRUD操作（作成・読取・更新・削除）
- クライアント詳細情報管理
- 契約情報・請求設定
- アクティブ/非アクティブ状態管理

🎯 要求仕様:
- 階層化されたクライアント管理
- カスタムフィールド対応
- 一括操作機能
- 検索・フィルタリング

📊 データ項目:
- 基本情報（社名、担当者、連絡先）
- 契約情報（契約日、契約期間、契約タイプ）
- 請求設定（支払条件、請求サイクル、税率）
- メモ・タグ機能
```

#### 3. 広告アカウント管理
```
📋 機能概要:
- Google Ads / Yahoo Ads アカウント登録
- API接続設定・認証
- アカウント情報同期
- 権限管理

🎯 要求仕様:
- 複数プラットフォーム対応
- APIクレデンシャル暗号化保存
- 自動接続テスト
- バルク操作対応

📊 データ項目:
- プラットフォーム情報（Google/Yahoo）
- アカウントID・名称
- API接続情報
- 同期設定・頻度
- 最終同期日時
```

#### 4. データ同期エンジン
```
📋 機能概要:
- Google Ads API / Yahoo Ads API からのデータ取得
- 日次・時間単位の自動同期
- データ整合性チェック
- エラーハンドリング・リトライ機能

🎯 要求仕様:
- 大量データの効率的処理
- 差分同期対応
- 同期状況のリアルタイム表示
- 手動同期トリガー

📊 取得データ:
- キャンペーン情報
- 広告グループ情報
- キーワード情報
- 日次パフォーマンスデータ
- コンバージョンデータ
```

#### 5. ダッシュボード・分析機能
```
📋 機能概要:
- リアルタイム KPI 表示
- カスタマイズ可能なダッシュボード
- 期間比較・トレンド分析
- アラート・通知機能

🎯 要求仕様:
- インタラクティブなグラフ・チャート
- ドリルダウン機能
- データエクスポート（CSV, Excel, PDF）
- カスタムメトリクス設定

📊 表示項目:
- 売上・コスト・利益サマリー
- CPA・ROAS・CTRトレンド
- 予算消化状況
- パフォーマンス比較
- 基本的なアラート機能
```

#### 6. 請求管理システム
```
📋 機能概要:
- 自動請求書生成
- コスト上乗せ・フィー計算
- 請求書送付・管理
- 支払い状況追跡

🎯 要求仕様:
- 複雑なフィー体系対応
- 税制対応（消費税、インボイス制度）
- 多通貨対応
- 自動督促機能

📊 機能詳細:
- 請求書テンプレート管理
- 承認ワークフロー
- 入金管理・消込機能
- 売上レポート生成
```

### 🎨 **Advanced Features（高度な機能）**

#### 7. レポーティングシステム
```
📋 機能概要:
- 標準レポート作成
- 定期レポート自動生成・配信
- クライアント向けレポート
- 社内向け管理レポート

🎯 要求仕様:
- 事前定義されたレポートテンプレート
- スケジュール配信機能
- 多様な出力フォーマット（PDF, Excel, CSV）
- ブランディング対応

📊 レポート種類:
- 日次・週次・月次レポート
- パフォーマンスサマリー
- コスト分析レポート
- ROI・収益性レポート
```

#### 8. データ分析・比較機能
```
📋 機能概要:
- 期間比較分析
- クライアント間比較
- キャンペーン効果測定
- 基本的なトレンド分析

🎯 要求仕様:
- 統計的基本分析
- データの可視化
- 異常値の基本検知
- 手動での分析・比較機能

📊 分析機能:
- 前年同期比較
- 季節性の基本分析
- パフォーマンス変化の検知
- 手動でのデータ深掘り
```

---

## 🛠️ 技術要件定義

### 💻 **システムアーキテクチャ**

#### アーキテクチャパターン
```
🏗️ モノリシック → マイクロサービス移行型
┌─────────────────────────────────────────┐
│              Frontend Layer             │
│    React.js + TypeScript + Tailwind    │
└─────────────────────────────────────────┘
                    │ REST API
┌─────────────────────────────────────────┐
│             API Gateway                 │
│         (Express.js + JWT)              │
└─────────────────────────────────────────┘
          │              │              │
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│   User      │ │   Client    │ │   Ads       │
│  Service    │ │  Service    │ │  Service    │
└─────────────┘ └─────────────┘ └─────────────┘
          │              │              │
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│  Billing    │ │   Report    │ │   Sync      │
│  Service    │ │  Service    │ │  Service    │
└─────────────┘ └─────────────┘ └─────────────┘
                    │
┌─────────────────────────────────────────┐
│            Data Layer                   │
│  PostgreSQL + Redis + MinIO(File)       │
└─────────────────────────────────────────┘
```

### 🔧 **技術スタック詳細**

#### Frontend
```javascript
🖥️ メインフレームワーク:
- React 18+ (Functional Components + Hooks)
- TypeScript 5+ (型安全性)
- Vite (高速ビルド)

🎨 UI/スタイリング:
- Tailwind CSS 3+ (ユーティリティファースト)
- Headless UI (アクセシブルコンポーネント)
- React Hook Form (フォーム管理)
- Zod (バリデーション)

📊 データ視覚化:
- Chart.js / Recharts (グラフ)
- React Table (テーブル)
- React Query (サーバー状態管理)

🔧 開発ツール:
- ESLint + Prettier (コード品質)
- Jest + Testing Library (テスト)
- Storybook (コンポーネント開発)
```

#### Backend
```javascript
🚀 メインフレームワーク:
- Node.js 20+ (LTS)
- Express.js 4+ (Webフレームワーク)
- TypeScript 5+ (型安全性)

🔐 認証・セキュリティ:
- JWT (JSON Web Tokens)
- bcrypt (パスワードハッシュ)
- helmet (セキュリティヘッダー)
- rate-limiter (レート制限)

📡 API・通信:
- Axios (HTTP クライアント)
- Socket.io (リアルタイム通信)
- Bull Queue (ジョブキュー)
- node-cron (スケジューラー)

🧪 テスト・品質:
- Jest (単体テスト)
- Supertest (統合テスト)
- ESLint + Prettier
```

#### Database & Infrastructure
```sql
🗄️ メインデータベース:
- PostgreSQL 15+ (メインDB)
- Redis 7+ (キャッシュ・セッション)
- MinIO (ファイルストレージ)

☁️ インフラ・デプロイ:
- Docker + Docker Compose
- Nginx (リバースプロキシ)
- PM2 (プロセス管理)
- GitHub Actions (CI/CD)

📊 監視・ログ:
- Winston (ログライブラリ)
- Prometheus + Grafana (監視)
- Sentry (エラー追跡)
```

### 🔌 **外部API統合**

#### Google Ads API
```javascript
📋 統合要件:
- Google Ads API v14+
- OAuth 2.0 認証
- リフレッシュトークン管理
- レート制限対応

🎯 取得データ:
- Campaign データ
- AdGroup データ
- Keyword データ
- Performance データ
- Conversion データ

📊 実装仕様:
const googleAdsClient = {
  version: 'v14',
  authentication: 'oauth2',
  rateLimit: '10000 operations/day',
  retryPolicy: 'exponential backoff',
  dataRefresh: 'every 1 hour'
}
```

#### Yahoo Ads API
```javascript
📋 統合要件:
- Yahoo Ads API v3+
- OAuth 2.0 認証
- アクセストークン管理
- エラーハンドリング

🎯 取得データ:
- Campaign データ
- AdGroup データ
- Keyword データ
- Statistics データ
- Conversion データ

📊 実装仕様:
const yahooAdsClient = {
  version: 'v3',
  authentication: 'oauth2',
  rateLimit: '100 requests/minute',
  retryPolicy: 'linear backoff',
  dataRefresh: 'every 1 hour'
}
```

---

## 🗄️ データベース設計

### 📊 **ERD（Entity Relationship Diagram）**

```sql
-- Core Tables
Users ──────┐
           │
           ▼
Clients ────┬──── ClientContracts
           │
           ├──── AdAccounts ──── AdAccountCredentials
           │         │
           │         ├──── Campaigns
           │         │       │
           │         │       ├──── AdGroups
           │         │       │       │
           │         │       │       └──── Keywords
           │         │       │
           │         │       └──── CampaignStats
           │         │
           │         └──── DailyStats
           │
           ├──── BillingSettings ──── FeeStructures
           │
           ├──── Invoices ──── InvoiceItems
           │
           └──── Reports ──── ReportSchedules
```

### 🔧 **テーブル定義詳細**

#### Users（ユーザー管理）
```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role VARCHAR(50) DEFAULT 'user',
    two_factor_enabled BOOLEAN DEFAULT false,
    two_factor_secret VARCHAR(255),
    email_verified BOOLEAN DEFAULT false,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
```

#### Clients（クライアント管理）
```sql
CREATE TABLE clients (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    tax_number VARCHAR(100),
    status VARCHAR(20) DEFAULT 'active',
    tags TEXT[],
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_clients_user_id ON clients(user_id);
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_clients_company_name ON clients(company_name);
```

#### AdAccounts（広告アカウント）
```sql
CREATE TABLE ad_accounts (
    id SERIAL PRIMARY KEY,
    client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
    platform VARCHAR(20) NOT NULL, -- 'google' or 'yahoo'
    account_id VARCHAR(100) NOT NULL,
    account_name VARCHAR(255),
    currency VARCHAR(10) DEFAULT 'JPY',
    timezone VARCHAR(50) DEFAULT 'Asia/Tokyo',
    status VARCHAR(20) DEFAULT 'active',
    last_sync TIMESTAMP,
    sync_enabled BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(platform, account_id)
);

CREATE INDEX idx_ad_accounts_client_id ON ad_accounts(client_id);
CREATE INDEX idx_ad_accounts_platform ON ad_accounts(platform);
```

#### Campaigns（キャンペーン）
```sql
CREATE TABLE campaigns (
    id SERIAL PRIMARY KEY,
    ad_account_id INTEGER REFERENCES ad_accounts(id) ON DELETE CASCADE,
    campaign_id VARCHAR(100) NOT NULL,
    campaign_name VARCHAR(255),
    campaign_type VARCHAR(50),
    status VARCHAR(20),
    budget_amount DECIMAL(15,2),
    budget_type VARCHAR(20),
    start_date DATE,
    end_date DATE,
    target_cpa DECIMAL(10,2),
    target_roas DECIMAL(8,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(ad_account_id, campaign_id)
);
```

#### DailyStats（日次統計）
```sql
CREATE TABLE daily_stats (
    id SERIAL PRIMARY KEY,
    ad_account_id INTEGER REFERENCES ad_accounts(id) ON DELETE CASCADE,
    campaign_id VARCHAR(100),
    date DATE NOT NULL,
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    cost DECIMAL(15,2) DEFAULT 0.00,
    conversions DECIMAL(10,2) DEFAULT 0.00,
    conversion_value DECIMAL(15,2) DEFAULT 0.00,
    ctr DECIMAL(8,4),
    cpc DECIMAL(10,2),
    cpa DECIMAL(10,2),
    roas DECIMAL(8,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(ad_account_id, campaign_id, date)
);

CREATE INDEX idx_daily_stats_date ON daily_stats(date);
CREATE INDEX idx_daily_stats_account_date ON daily_stats(ad_account_id, date);
```

#### BillingSettings（請求設定）
```sql
CREATE TABLE billing_settings (
    id SERIAL PRIMARY KEY,
    client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
    fee_type VARCHAR(20) NOT NULL, -- 'percentage', 'fixed', 'tiered'
    fee_value DECIMAL(8,2),
    minimum_fee DECIMAL(10,2) DEFAULT 0.00,
    markup_percentage DECIMAL(5,2) DEFAULT 0.00,
    billing_cycle VARCHAR(20) DEFAULT 'monthly', -- 'weekly', 'monthly', 'quarterly'
    payment_terms INTEGER DEFAULT 30, -- days
    tax_rate DECIMAL(5,2) DEFAULT 10.00,
    currency VARCHAR(10) DEFAULT 'JPY',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Invoices（請求書）
```sql
CREATE TABLE invoices (
    id SERIAL PRIMARY KEY,
    client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'draft', -- 'draft', 'sent', 'paid', 'overdue'
    issue_date DATE,
    due_date DATE,
    paid_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_invoices_client_id ON invoices(client_id);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoices_due_date ON invoices(due_date);
```

---

## 🔐 セキュリティ要件

### 認証・認可
```javascript
🔒 認証機能:
- JWT ベース認証
- リフレッシュトークン機能
- 二要素認証（TOTP）
- パスワード強度チェック
- アカウントロック機能

🛡️ 認可機能:
- Role-Based Access Control (RBAC)
- Resource-Based Access Control
- API レート制限
- CORS 設定
```

### データ保護
```javascript
🔐 暗号化:
- パスワード: bcrypt (cost 12+)
- APIキー: AES-256暗号化
- 通信: TLS 1.3
- データベース: 保存時暗号化

📊 プライバシー:
- GDPR 準拠
- 個人情報保護法対応
- データ匿名化機能
- 削除権・訂正権対応
```

---

## 📈 パフォーマンス要件

### レスポンス時間
```
🚀 目標値:
- ページロード: < 2秒
- API レスポンス: < 500ms
- データベースクエリ: < 100ms
- ファイルアップロード: < 5秒

📊 スループット:
- 同時ユーザー: 1,000人
- API リクエスト: 10,000/分
- データ処理: 100万レコード/時
```

### 可用性
```
🎯 目標値:
- システム稼働率: 99.9%
- 計画メンテナンス: 月4時間以内
- 障害復旧時間: 30分以内
- データバックアップ: 日次自動
```

---

## 🧪 テスト要件

### テスト種類
```javascript
🔍 単体テスト:
- カバレッジ: 90%以上
- フレームワーク: Jest
- モック: 外部API、データベース

🔗 統合テスト:
- API エンドポイント
- データベース操作
- 外部サービス連携

🖥️ E2Eテスト:
- 主要ユーザーフロー
- フレームワーク: Playwright
- 自動化: CI/CD パイプライン
```

---

## 📋 開発プロセス

### 開発手法
```
🚀 アジャイル開発:
- スクラム（2週間スプリント）
- デイリースタンドアップ
- スプリントレビュー・レトロスペクティブ

📊 品質管理:
- コードレビュー（必須）
- 継続的インテグレーション
- 自動デプロイメント
```

### リリース戦略
```
🎯 段階的リリース:
1. Alpha版（内部テスト）
2. Beta版（限定ユーザー）
3. GA版（一般提供）
4. 段階的ロールアウト

📈 モニタリング:
- パフォーマンス監視
- エラートラッキング
- ユーザー行動分析
```

---

## 🎯 成功指標（KPI）

### 技術KPI
```
⚡ パフォーマンス:
- ページロード時間: < 2秒
- API応答時間: < 500ms
- エラー率: < 0.1%
- 稼働率: > 99.9%

🔐 セキュリティ:
- 脆弱性: ゼロ
- セキュリティ監査: 月1回
- データ漏洩: ゼロ件
```

### ビジネスKPI
```
👥 ユーザー:
- 新規登録: 月100件
- アクティブユーザー: 80%
- 継続率: 85%以上

💰 収益:
- 有料転換率: 15%
- 月間売上: ¥2M（1年目）
- 顧客満足度: 4.5/5.0
```

---

## 📅 開発ロードマップ

### Phase 1: 基盤構築（1-2ヶ月）
```
🔧 技術基盤:
- 開発環境セットアップ
- アーキテクチャ実装
- 基本認証システム
- データベース設計・構築

👥 基本機能:
- ユーザー管理機能
- クライアント管理機能
- 基本ダッシュボード
```

### Phase 2: API統合（2-3ヶ月）
```
🔌 外部API:
- Google Ads API統合
- Yahoo Ads API統合
- データ同期エンジン
- エラーハンドリング

📊 データ処理:
- 日次データ取得
- パフォーマンス計算
- データ可視化
```

### Phase 3: 請求機能（1-2ヶ月）
```
💰 請求管理:
- フィー計算エンジン
- 請求書生成機能
- 支払い管理
- レポート機能
```

### Phase 4: 最適化・本格運用（1ヶ月）
```
🚀 最終調整:
- パフォーマンス最適化
- セキュリティ強化
- ユーザーテスト
- 本格リリース
```

---

**🎯 改訂版要件定義完了！**

AI機能を除外し、実用的で実装可能な機能に絞った要件定義書が完成しました。この要件に基づいて、確実に価値を提供できるKanho Ads Manager v2.0の開発が可能です。