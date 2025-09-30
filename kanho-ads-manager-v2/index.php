<?php

/**
 * Kanho Ads Manager v2.0
 * エントリーポイント
 */

// エラーレポート設定
error_reporting(E_ALL);
ini_set('display_errors', 1);

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// セッション開始
session_start();

// Composer オートローダー
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 環境変数読み込み
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    // 基本的な環境変数設定（.envがない場合）
    $_ENV['APP_NAME'] = 'Kanho Ads Manager';
    $_ENV['APP_ENV'] = 'local';
    $_ENV['APP_DEBUG'] = 'true';
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_DATABASE'] = 'kanho_ads_manager_v2';
    $_ENV['DB_USERNAME'] = 'root';
    $_ENV['DB_PASSWORD'] = '';
}

// ヘルパー関数読み込み
require_once __DIR__ . '/app/Helpers/functions.php';

// データベース設定読み込み
require_once __DIR__ . '/config/database.php';

// 基本設定
$config = require __DIR__ . '/config/app.php';

// デバッグモード設定
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ルーティング処理
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// クエリパラメータを除去
$path = parse_url($requestUri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

// 静的ファイルの処理（開発環境用）
if ($config['env'] === 'local' && preg_match('/\.(css|js|png|jpg|gif|ico)$/', $path)) {
    $filePath = __DIR__ . '/public' . $path;
    if (file_exists($filePath)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon'
        ];
        
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (isset($mimeTypes[$extension])) {
            header('Content-Type: ' . $mimeTypes[$extension]);
        }
        
        readfile($filePath);
        exit;
    }
}

// ルートテーブル
$routes = [
    'GET' => [
        '/' => 'DashboardController@index',
        '/dashboard' => 'DashboardController@index',
        '/login' => 'AuthController@showLogin',
        '/register' => 'AuthController@showRegister',
        '/logout' => 'AuthController@logout',
        '/clients' => 'ClientController@index',
        '/clients/create' => 'ClientController@create',
        '/clients/(\d+)' => 'ClientController@show',
        '/clients/(\d+)/edit' => 'ClientController@edit',
        '/billing' => 'BillingController@index',
        '/billing/create' => 'BillingController@create',
        '/billing/(\d+)' => 'BillingController@show',
        '/test-db' => 'TestController@database',
        // 広告アカウント管理
        '/ad-accounts' => 'AdAccountController@index',
        '/ad-accounts/create' => 'AdAccountController@create',
        '/ad-accounts/(\d+)' => 'AdAccountController@show',
        '/ad-accounts/(\d+)/edit' => 'AdAccountController@edit',
    ],
    'POST' => [
        '/login' => 'AuthController@login',
        '/register' => 'AuthController@register',
        '/clients' => 'ClientController@store',
        '/clients/(\d+)' => 'ClientController@update',
        '/clients/(\d+)/delete' => 'ClientController@destroy',
        '/billing' => 'BillingController@store',
        '/billing/update-status' => 'BillingController@updateStatus',
        // 広告アカウント管理
        '/ad-accounts' => 'AdAccountController@store',
        '/ad-accounts/(\d+)' => 'AdAccountController@update',
        '/ad-accounts/(\d+)/delete' => 'AdAccountController@destroy',
    ]
];

// API ルート（AJAX用）
if (strpos($path, '/api/') === 0) {
    header('Content-Type: application/json');
    
    $apiRoutes = [
        'GET' => [
            '/api/dashboard/summary' => 'ApiController@dashboardSummary',
            '/api/clients' => 'ApiController@clients',
            '/api/billing/summary' => 'ApiController@billingSummary',
            // 広告アカウント管理 API
            '/api/ad-accounts/google' => 'AdAccountController@getGoogleAccounts',
            '/api/ad-accounts/test-connection' => 'AdAccountController@testGoogleConnection',
        ],
        'POST' => [
            '/api/clients' => 'ApiController@storeClient',
            '/api/billing/status' => 'ApiController@updateBillingStatus',
            // 広告アカウント管理 API
            '/api/ad-accounts' => 'AdAccountController@apiStore',
        ]
    ];
    
    $matchedRoute = matchRoute($path, $requestMethod, $apiRoutes);
} else {
    $matchedRoute = matchRoute($path, $requestMethod, $routes);
}

if ($matchedRoute) {
    list($controllerAction, $params) = $matchedRoute;
    list($controllerName, $actionName) = explode('@', $controllerAction);
    
    $controllerClass = "App\\Controllers\\{$controllerName}";
    
    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();
        
        if (method_exists($controller, $actionName)) {
            // 認証が必要なルートのチェック
            $publicRoutes = ['/login', '/register', '/test-db'];
            if (!in_array($path, $publicRoutes) && !is_logged_in()) {
                if (strpos($path, '/api/') === 0) {
                    echo json_encode(['error' => 'Unauthorized']);
                    exit;
                } else {
                    redirect('/login');
                }
            }
            
            call_user_func_array([$controller, $actionName], $params);
        } else {
            abort(404, "Action {$actionName} not found in {$controllerClass}");
        }
    } else {
        abort(404, "Controller {$controllerClass} not found");
    }
} else {
    abort(404, "Route not found: {$requestMethod} {$path}");
}

/**
 * ルートマッチング関数
 */
function matchRoute($path, $method, $routes) {
    if (!isset($routes[$method])) {
        return null;
    }
    
    foreach ($routes[$method] as $routePath => $controller) {
        // Convert route parameters like (\d+) to regex groups
        $pattern = preg_quote($routePath, '/');
        $pattern = str_replace('\(\\\d\+\)', '(\d+)', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $path, $matches)) {
            $params = array_slice($matches, 1);
            return [$controller, $params];
        }
    }
    
    return null;
}