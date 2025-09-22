<?php
/**
 * localhost設定ファイル - Xbizサーバー用
 * sv301.xbiz.ne.jpで接続できない場合のlocalhost設定
 */

// .envファイルの代わりに直接設定を定義
$_ENV['APP_NAME'] = 'Kanho Ads Manager';
$_ENV['APP_ENV'] = 'production';
$_ENV['APP_DEBUG'] = 'false';
$_ENV['APP_URL'] = 'https://app.kanho.co.jp/ads_reports';

// データベース設定 (localhost版)
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_HOST'] = 'localhost';  // localhostに変更
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_DATABASE'] = 'kanho_adsmanager';
$_ENV['DB_USERNAME'] = 'kanho_adsmanager';
$_ENV['DB_PASSWORD'] = 'Kanho20200701';
$_ENV['DB_CHARSET'] = 'utf8mb4';
$_ENV['DB_COLLATION'] = 'utf8mb4_unicode_ci';

// セッション設定
$_ENV['SESSION_NAME'] = 'kanho_ads_session';
$_ENV['SESSION_LIFETIME'] = '120';

// ログ設定
$_ENV['LOG_LEVEL'] = 'error';
$_ENV['LOG_PATH'] = 'logs/app.log';

// 設定が読み込まれたことを確認
$_ENV['CONFIG_LOADED'] = 'localhost';
?>