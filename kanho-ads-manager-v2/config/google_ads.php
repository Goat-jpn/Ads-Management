<?php

/**
 * Google Ads API Configuration
 * 
 * 共通のGoogle Ads API認証情報設定
 * セキュリティのため、本番環境では環境変数を使用してください
 */

return [
    // Google Ads API 設定 (実際の値は .env ファイルで設定してください)
    'developer_token' => $_ENV['GOOGLE_DEVELOPER_TOKEN'] ?? 'YOUR_DEVELOPER_TOKEN_HERE',
    'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? 'YOUR_CLIENT_ID_HERE',
    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'YOUR_CLIENT_SECRET_HERE',
    'refresh_token' => $_ENV['GOOGLE_REFRESH_TOKEN'] ?? 'YOUR_REFRESH_TOKEN_HERE',
    
    // API設定
    'login_customer_id' => $_ENV['GOOGLE_LOGIN_CUSTOMER_ID'] ?? null, // Manager Account ID (オプション)
    'api_version' => 'v16', // Google Ads API バージョン
    
    // OAuth 2.0 設定
    'oauth2' => [
        'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'redirectUri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost/oauth2callback',
        'tokenCredentialUri' => 'https://www.googleapis.com/oauth2/v4/token',
        'scope' => 'https://www.googleapis.com/auth/adwords',
        'state' => null
    ],
    
    // API エンドポイント
    'endpoints' => [
        'base_url' => 'https://googleads.googleapis.com',
        'accounts' => '/v16/customers:listAccessibleCustomers',
        'customer_info' => '/v16/customers/{customer_id}/googleAdsFields:search',
        'campaigns' => '/v16/customers/{customer_id}/campaigns:search'
    ],
    
    // リクエスト設定
    'request_settings' => [
        'timeout' => 30,
        'retry_attempts' => 3,
        'rate_limit_delay' => 1000, // ミリ秒
    ],
    
    // ログ設定
    'logging' => [
        'enabled' => true,
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'file_path' => __DIR__ . '/../logs/google_ads_api.log'
    ]
];