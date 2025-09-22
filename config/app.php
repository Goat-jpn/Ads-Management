<?php

// アプリケーション設定ファイル

return [
    // アプリケーション基本設定
    'app' => [
        'name' => '広告費・手数料管理システム',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'secret' => $_ENV['APP_SECRET'] ?? 'your-secret-key-here',
        'timezone' => 'Asia/Tokyo',
    ],

    // データベース設定
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_NAME'] ?? 'kanho_adsmanager',
        'username' => $_ENV['DB_USER'] ?? 'kanho_adsmanager',
        'password' => $_ENV['DB_PASS'] ?? 'Kanho20200701',
    ],

    // セッション設定
    'session' => [
        'name' => 'ADS_MGMT_SESSION',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200), // 2時間
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ],

    // ログ設定
    'logging' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'path' => $_ENV['LOG_PATH'] ?? 'logs/',
        'max_files' => 30,
        'max_size' => '10MB',
    ],

    // Google Ads API設定
    'google_ads' => [
        'developer_token' => $_ENV['GOOGLE_ADS_DEVELOPER_TOKEN'] ?? '',
        'client_id' => $_ENV['GOOGLE_ADS_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_ADS_CLIENT_SECRET'] ?? '',
        'refresh_token' => $_ENV['GOOGLE_ADS_REFRESH_TOKEN'] ?? '',
        'login_customer_id' => $_ENV['GOOGLE_ADS_LOGIN_CUSTOMER_ID'] ?? '',
    ],

    // Yahoo広告API設定
    'yahoo_ads' => [
        // Display Ads API
        'display' => [
            'app_id' => $_ENV['YAHOO_DISPLAY_APP_ID'] ?? '',
            'secret' => $_ENV['YAHOO_DISPLAY_SECRET'] ?? '',
            'refresh_token' => $_ENV['YAHOO_DISPLAY_REFRESH_TOKEN'] ?? '',
            'account_id' => $_ENV['YAHOO_DISPLAY_ACCOUNT_ID'] ?? '',
        ],
        
        // Search Ads API
        'search' => [
            'license_id' => $_ENV['YAHOO_SEARCH_LICENSE_ID'] ?? '',
            'api_account_id' => $_ENV['YAHOO_SEARCH_API_ACCOUNT_ID'] ?? '',
            'api_account_password' => $_ENV['YAHOO_SEARCH_API_ACCOUNT_PASSWORD'] ?? '',
            'onbehalf_of_account_id' => $_ENV['YAHOO_SEARCH_ONBEHALF_OF_ACCOUNT_ID'] ?? '',
            'onbehalf_of_password' => $_ENV['YAHOO_SEARCH_ONBEHALF_OF_PASSWORD'] ?? '',
        ],
    ],

    // メール設定
    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.example.com',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USER'] ?? '',
        'password' => $_ENV['MAIL_PASS'] ?? '',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? '広告管理システム',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
        'encryption' => 'tls',
    ],

    // API設定
    'api' => [
        'rate_limit' => [
            'google_ads' => [
                'requests_per_minute' => 100,
                'requests_per_day' => 10000,
            ],
            'yahoo_display' => [
                'requests_per_minute' => 60,
                'requests_per_day' => 5000,
            ],
            'yahoo_search' => [
                'requests_per_minute' => 120,
                'requests_per_day' => 8000,
            ],
        ],
        'timeout' => 30, // 秒
        'retry_attempts' => 3,
        'retry_delay' => 1, // 秒
    ],

    // ファイル設定
    'files' => [
        'upload_path' => 'storage/uploads/',
        'temp_path' => 'storage/temp/',
        'max_upload_size' => '10MB',
        'allowed_extensions' => ['xlsx', 'xls', 'csv', 'pdf'],
    ],

    // レポート設定
    'reports' => [
        'default_format' => 'xlsx',
        'date_format' => 'Y-m-d',
        'currency_format' => 'JPY',
        'timezone' => 'Asia/Tokyo',
        'cache_ttl' => 3600, // 1時間
    ],

    // セキュリティ設定
    'security' => [
        'password_min_length' => 8,
        'login_attempts_limit' => 5,
        'login_lockout_duration' => 900, // 15分
        'csrf_token_lifetime' => 3600, // 1時間
        'password_hash_algo' => PASSWORD_DEFAULT,
    ],

    // 請求設定
    'billing' => [
        'default_tax_rate' => 0.10,
        'default_payment_terms' => 30, // 日数
        'invoice_number_format' => 'INV-%Y%m%d-%04d',
        'late_payment_fee_rate' => 0.05, // 遅延損害金率
    ],
];