<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */
    'name' => $_ENV['APP_NAME'] ?? 'Kanho Ads Manager',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
    'csrf_token_name' => $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token',
    'session_name' => $_ENV['SESSION_NAME'] ?? 'kanho_session',
    'session_lifetime' => 120, // minutes
    
    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    */
    'database' => [
        'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'kanho_ads_manager_v2',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Mail Settings
    |--------------------------------------------------------------------------
    */
    'mail' => [
        'mailer' => $_ENV['MAIL_MAILER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@kanho-ads.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Kanho Ads Manager'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'log' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'error',
        'path' => $_ENV['LOG_PATH'] ?? 'storage/logs/app.log'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'path' => $_ENV['CACHE_PATH'] ?? 'storage/cache'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */
    'apis' => [
        'google_ads' => [
            'client_id' => $_ENV['GOOGLE_ADS_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['GOOGLE_ADS_CLIENT_SECRET'] ?? '',
            'developer_token' => $_ENV['GOOGLE_ADS_DEVELOPER_TOKEN'] ?? '',
            'version' => 'v14',
            'endpoint' => 'https://googleads.googleapis.com'
        ],
        'yahoo_ads' => [
            'client_id' => $_ENV['YAHOO_ADS_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['YAHOO_ADS_CLIENT_SECRET'] ?? '',
            'version' => 'v3',
            'endpoint' => 'https://ads-search.yahooapis.jp'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => 'Asia/Tokyo',
    
    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    */
    'locale' => 'ja',
    'fallback_locale' => 'en'
];