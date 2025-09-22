-- 初期データ投入スクリプト

USE ads_management;

-- システム設定の初期データ
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('default_tax_rate', '0.10', 'デフォルト税率（消費税）'),
('invoice_number_prefix', 'INV-', '請求書番号プレフィックス'),
('default_payment_terms', '30', 'デフォルト支払い条件（日数）'),
('sync_batch_size', '1000', 'API同期バッチサイズ'),
('max_sync_retry', '3', '同期リトライ最大回数'),
('currency_default', 'JPY', 'デフォルト通貨'),
('timezone_default', 'Asia/Tokyo', 'デフォルトタイムゾーン'),
('report_retention_days', '730', 'レポートデータ保持期間（日数）'),
('notification_email_from', 'system@ads-management.local', '通知メール送信者'),
('backup_enabled', '1', 'バックアップ有効化フラグ');

-- 管理者アカウントの初期データ（パスワードは 'admin123' をハッシュ化）
INSERT INTO admins (name, email, password, role) VALUES
('システム管理者', 'admin@ads-management.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin'),
('運用担当者', 'operator@ads-management.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator');

-- サンプルクライアントデータ
INSERT INTO clients (company_name, contact_name, email, phone, address, contract_start_date, contract_end_date, billing_day, payment_terms) VALUES
('株式会社サンプル商事', '田中太郎', 'tanaka@sample-corp.co.jp', '03-1234-5678', '東京都港区サンプル1-2-3', '2024-01-01', '2024-12-31', 25, 30),
('有限会社テスト工業', '佐藤花子', 'sato@test-industry.co.jp', '06-9876-5432', '大阪府大阪市テスト区4-5-6', '2024-02-01', NULL, 20, 30),
('エクサンプル株式会社', '鈴木次郎', 'suzuki@example-inc.co.jp', '052-1111-2222', '愛知県名古屋市エクサンプル区7-8-9', '2024-03-01', '2025-02-28', 25, 45);

-- サンプル手数料設定
INSERT INTO fee_settings (client_id, platform, fee_type, base_percentage, minimum_fee, effective_from) VALUES
(1, 'google_ads', 'percentage', 20.00, 50000, '2024-01-01'),
(1, 'yahoo_display', 'percentage', 20.00, 50000, '2024-01-01'),
(1, 'yahoo_search', 'percentage', 20.00, 50000, '2024-01-01'),
(2, 'google_ads', 'percentage', 15.00, 30000, '2024-02-01'),
(2, 'yahoo_display', 'percentage', 15.00, 30000, '2024-02-01'),
(3, 'google_ads', 'tiered', NULL, 20000, '2024-03-01'),
(3, 'yahoo_display', 'percentage', 18.00, 40000, '2024-03-01');

-- 段階手数料の設定例（クライアント3のGoogle Ads用）
INSERT INTO tiered_fees (fee_setting_id, min_amount, max_amount, percentage) VALUES
(6, 0, 500000, 20.00),
(6, 500001, 1000000, 18.00),
(6, 1000001, 2000000, 16.00),
(6, 2000001, NULL, 15.00);

-- サンプル広告アカウント
INSERT INTO ad_accounts (client_id, platform, account_id, account_name, currency_code, timezone) VALUES
(1, 'google_ads', '123-456-7890', 'サンプル商事 Google広告', 'JPY', 'Asia/Tokyo'),
(1, 'yahoo_display', 'YDN-1234567890', 'サンプル商事 Yahoo!ディスプレイ広告', 'JPY', 'Asia/Tokyo'),
(1, 'yahoo_search', 'YSS-1234567890', 'サンプル商事 Yahoo!検索広告', 'JPY', 'Asia/Tokyo'),
(2, 'google_ads', '987-654-3210', 'テスト工業 Google広告', 'JPY', 'Asia/Tokyo'),
(2, 'yahoo_display', 'YDN-0987654321', 'テスト工業 Yahoo!ディスプレイ広告', 'JPY', 'Asia/Tokyo'),
(3, 'google_ads', '555-666-7777', 'エクサンプル株式会社 Google広告', 'JPY', 'Asia/Tokyo'),
(3, 'yahoo_display', 'YDN-5556667777', 'エクサンプル株式会社 Yahoo!ディスプレイ広告', 'JPY', 'Asia/Tokyo');

-- サンプル費用上乗せ設定
INSERT INTO cost_markups (client_id, ad_account_id, markup_type, markup_value, description, effective_from) VALUES
(1, 1, 'percentage', 5.0000, 'Google広告運用手数料として5%上乗せ', '2024-01-01'),
(1, 2, 'percentage', 3.0000, 'Yahoo!ディスプレイ広告運用手数料として3%上乗せ', '2024-01-01'),
(2, NULL, 'fixed', 10000.0000, 'テスト工業全アカウントに月額1万円固定上乗せ', '2024-02-01');

-- サンプル日次データ（直近1週間分）
INSERT INTO daily_ad_data (ad_account_id, date_value, impressions, clicks, conversions, cost, reported_cost, ctr, cpc, cpa) VALUES
-- サンプル商事 Google広告
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 10000, 350, 15, 75000, 78750, 3.50, 214.29, 5000),
(1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 12000, 420, 18, 89000, 93450, 3.50, 211.90, 4944),
(1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 8500, 280, 12, 65000, 68250, 3.29, 232.14, 5417),
(1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 11500, 380, 16, 82000, 86100, 3.30, 215.79, 5125),
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 9800, 340, 14, 71000, 74550, 3.47, 208.82, 5071),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 13200, 450, 20, 95000, 99750, 3.41, 211.11, 4750),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 10800, 360, 17, 78000, 81900, 3.33, 216.67, 4588),

-- サンプル商事 Yahoo!ディスプレイ広告  
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 15000, 300, 8, 45000, 46350, 2.00, 150.00, 5625),
(2, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 18000, 360, 10, 54000, 55620, 2.00, 150.00, 5400),
(2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 12000, 240, 6, 36000, 37080, 2.00, 150.00, 6000),
(2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 16500, 330, 9, 49500, 50985, 2.00, 150.00, 5500),
(2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 14200, 284, 7, 42600, 43878, 2.00, 150.00, 6086),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 19800, 396, 11, 59400, 61182, 2.00, 150.00, 5400),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 17100, 342, 9, 51300, 52839, 2.00, 150.00, 5700);

-- 初期月次集計データ（当月分）
INSERT INTO monthly_summaries (client_id, ad_account_id, year_month, total_cost, total_reported_cost, total_impressions, total_clicks, total_conversions, calculated_fee) VALUES
(1, 1, DATE_FORMAT(CURDATE(), '%Y-%m'), 555000, 582750, 75800, 2580, 112, 110000),
(1, 2, DATE_FORMAT(CURDATE(), '%Y-%m'), 337800, 347694, 112600, 2252, 60, 67539),
(2, 4, DATE_FORMAT(CURDATE(), '%Y-%m'), 245000, 245000, 89500, 1790, 45, 36750),
(2, 5, DATE_FORMAT(CURDATE(), '%Y-%m'), 189000, 189000, 67200, 1344, 35, 28350);