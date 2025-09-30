<?php

/**
 * Kanho Ads Manager v2.0
 * Public Entry Point
 */

// Change to parent directory for all includes
chdir(dirname(__DIR__));

// エラーレポート設定
error_reporting(E_ALL);
ini_set('display_errors', 1);

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// セッション開始
session_start();

// Composer オートローダー
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 環境変数読み込み
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
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
require_once __DIR__ . '/../app/Helpers/functions.php';

// データベース設定読み込み
require_once __DIR__ . '/../config/database.php';

// 基本設定
$config = require __DIR__ . '/../config/app.php';

// デバッグモード設定
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// CSRF保護用トークン生成
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 現在のリクエストURI取得
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// クエリパラメータを除去
$path = parse_url($requestUri, PHP_URL_PATH);

// 静的ファイルの処理（開発サーバー用）
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/i', $path)) {
    $file = __DIR__ . $path;
    if (file_exists($file)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject'
        ];
        
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($file);
        return;
    }
    http_response_code(404);
    return;
}

// ルーティング
$routes = [
    // 認証関連
    'GET /' => function() {
        // ログイン済みの場合はダッシュボードにリダイレクト
        if (isset($_SESSION['user'])) {
            header('Location: /dashboard');
            exit;
        }
        // 未ログインの場合はログインページへ
        header('Location: /login');
        exit;
    },
    
    'GET /login' => function() {
        if (isset($_SESSION['user'])) {
            header('Location: /dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/login.php';
    },
    
    'POST /login' => function() {
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $controller = new \App\Controllers\AuthController();
        $controller->login();
    },
    
    'GET /register' => function() {
        if (isset($_SESSION['user'])) {
            header('Location: /dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/register.php';
    },
    
    'POST /register' => function() {
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $controller = new \App\Controllers\AuthController();
        $controller->register();
    },
    
    'GET /logout' => function() {
        session_destroy();
        header('Location: /login');
        exit;
    },
    
    'GET /forgot-password' => function() {
        if (isset($_SESSION['user'])) {
            header('Location: /dashboard');
            exit;
        }
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $controller = new \App\Controllers\AuthController();
        $controller->showForgotPassword();
    },
    
    'POST /forgot-password' => function() {
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $controller = new \App\Controllers\AuthController();
        $controller->forgotPassword();
    },
    
    // メインアプリケーション
    'GET /dashboard' => function() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        
        // ダッシュボード用の基本データを準備
        $clientCount = 0;
        $activeCampaigns = 0;
        $monthlyAdSpend = 0;
        $unpaidAmount = 0;
        $recentClients = [];
        
        // 未実装モデルのため、仮の値を設定
        require_once __DIR__ . '/../views/dashboard.php';
    },
    
    // プロフィール管理
    'GET /profile' => function() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/profile/profile.php';
    },
    
    'POST /profile' => function() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        // プロフィール更新処理（未実装）
        flash('success', 'プロフィールを更新しました。');
        header('Location: /profile');
        exit;
    },
    
    // キャンペーン管理
    'GET /campaigns' => function() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/campaigns/index.php';
    },
    
    // 請求管理
    'GET /billing' => function() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/billing/index.php';
    },
    
    // クライアント管理
    'GET /clients' => function() {
        require_once __DIR__ . '/../app/Controllers/ClientController.php';
        $controller = new \App\Controllers\ClientController();
        $controller->index();
    },
    
    'GET /clients/create' => function() {
        require_once __DIR__ . '/../app/Controllers/ClientController.php';
        $controller = new \App\Controllers\ClientController();
        $controller->create();
    },
    
    'POST /clients/create' => function() {
        require_once __DIR__ . '/../app/Controllers/ClientController.php';
        $controller = new \App\Controllers\ClientController();
        $controller->create();
    },
    
    // 広告アカウント管理
    'GET /ad-accounts' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->index();
    },
    
    'GET /ad-accounts/create' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->create();
    },
    
    'POST /ad-accounts/create' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->create();
    },
    
    'GET /ad-accounts/sync' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->sync();
    },
    
    // Dynamic routes for ad-accounts (must be after static routes)
    'GET /ad-accounts/{id}' => function($matches) {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->show($matches['id']);
    },
    
    'GET /ad-accounts/{id}/edit' => function($matches) {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->edit($matches['id']);
    },
    
    'POST /ad-accounts/{id}/update' => function($matches) {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->update($matches['id']);
    },
    
    'POST /ad-accounts/{id}/delete' => function($matches) {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->destroy($matches['id']);
    },
    
    // Google Ads API 連携
    'GET /api/google-accounts' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->getGoogleAccounts();
    },
    
    'GET /api/google-test' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->testGoogleConnection();
    },
    
    // Ad Account API エンドポイント
    'GET /api/ad-accounts/google' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->getGoogleAccounts();
    },
    
    'GET /api/ad-accounts/test-connection' => function() {
        require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
        $controller = new \App\Controllers\AdAccountController();
        $controller->testGoogleConnection();
    },
    
    // Dashboard API エンドポイント
    'GET /api/dashboard/performance' => function() {
        require_once __DIR__ . '/../app/Controllers/DashboardController.php';
        $controller = new \App\Controllers\DashboardController();
        $controller->getPerformanceData();
    },
    
    'GET /api/dashboard/platforms' => function() {
        require_once __DIR__ . '/../app/Controllers/DashboardController.php';
        $controller = new \App\Controllers\DashboardController();
        $controller->getPlatformData();
    },
    
    // 404 ハンドラー
    '404' => function() {
        http_response_code(404);
        require_once __DIR__ . '/../views/errors/404.php';
    }
];

// リクエストマッチング
$route = $requestMethod . ' ' . $path;
$matched = false;

// 完全一致のルートを最初にチェック
if (isset($routes[$route])) {
    try {
        $routes[$route]();
        $matched = true;
    } catch (Exception $e) {
        error_log("Route handler error: " . $e->getMessage());
        http_response_code(500);
        if ($config['debug']) {
            echo "<h1>500 Internal Server Error</h1>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            echo "<h1>500 Internal Server Error</h1>";
        }
    }
}

// 動的ルートをチェック（完全一致がなかった場合）
if (!$matched) {
    foreach ($routes as $routePattern => $handler) {
        // {id} パラメータを含むルートパターンを正規表現に変換
        $pattern = str_replace('{id}', '([0-9]+)', $routePattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $route, $matches)) {
            try {
                // マッチしたパラメータを抽出
                $params = [];
                if (isset($matches[1])) {
                    $params['id'] = $matches[1];
                }
                $handler($params);
                $matched = true;
                break;
            } catch (Exception $e) {
                error_log("Route handler error: " . $e->getMessage());
                http_response_code(500);
                if ($config['debug']) {
                    echo "<h1>500 Internal Server Error</h1>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                } else {
                    echo "<h1>500 Internal Server Error</h1>";
                }
            }
        }
    }
}

// どのルートにもマッチしなかった場合は動的ルートをチェック
if (!$matched) {
    // パターンマッチング（動的ルート用）
    $matched = false;
    
    foreach ($routes as $pattern => $handler) {
        if ($pattern === '404') continue;
        
        $parts = explode(' ', $pattern, 2);
        if (count($parts) !== 2) continue;
        
        list($method, $routePath) = $parts;
        
        if ($method !== $requestMethod) continue;
        
        // 動的パラメータを含むルートのマッチング
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';
        
        if (preg_match($routePattern, $path, $matches)) {
            array_shift($matches); // 最初の完全マッチを除去
            
            try {
                call_user_func_array($handler, $matches);
                $matched = true;
                break;
            } catch (Exception $e) {
                error_log("Dynamic route handler error: " . $e->getMessage());
                http_response_code(500);
                if ($config['debug']) {
                    echo "<h1>500 Internal Server Error</h1>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                } else {
                    echo "<h1>500 Internal Server Error</h1>";
                }
                $matched = true;
                break;
            }
        }
    }
    
    // 特別なクライアント動的ルート処理
    if (!$matched) {
        // /clients/{id} パターン
        if (preg_match('#^/clients/(\d+)$#', $path, $matches)) {
            require_once __DIR__ . '/../app/Controllers/ClientController.php';
            $controller = new \App\Controllers\ClientController();
            $controller->show($matches[1]);
            $matched = true;
        }
        // /clients/{id}/edit パターン  
        elseif (preg_match('#^/clients/(\d+)/edit$#', $path, $matches)) {
            require_once __DIR__ . '/../app/Controllers/ClientController.php';
            $controller = new \App\Controllers\ClientController();
            if ($requestMethod === 'POST') {
                $controller->update($matches[1]);
            } else {
                $controller->edit($matches[1]);
            }
            $matched = true;
        }
        // /clients/{id}/delete パターン
        elseif (preg_match('#^/clients/(\d+)/delete$#', $path, $matches) && $requestMethod === 'POST') {
            require_once __DIR__ . '/../app/Controllers/ClientController.php';
            $controller = new \App\Controllers\ClientController();
            $controller->destroy($matches[1]);
            $matched = true;
        }
        // /ad-accounts/{id} パターン
        elseif (preg_match('#^/ad-accounts/(\d+)$#', $path, $matches)) {
            require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
            $controller = new \App\Controllers\AdAccountController();
            $controller->show($matches[1]);
            $matched = true;
        }
        // /ad-accounts/{id}/edit パターン
        elseif (preg_match('#^/ad-accounts/(\d+)/edit$#', $path, $matches)) {
            require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
            $controller = new \App\Controllers\AdAccountController();
            if ($requestMethod === 'POST') {
                $controller->update($matches[1]);
            } else {
                $controller->edit($matches[1]);
            }
            $matched = true;
        }
        // /ad-accounts/{id}/sync パターン
        elseif (preg_match('#^/ad-accounts/(\d+)/sync$#', $path, $matches)) {
            require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
            $controller = new \App\Controllers\AdAccountController();
            $controller->sync($matches[1]);
            $matched = true;
        }
        // /ad-accounts/{id}/auth パターン
        elseif (preg_match('#^/ad-accounts/(\d+)/auth$#', $path, $matches)) {
            require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
            $controller = new \App\Controllers\AdAccountController();
            $controller->auth($matches[1]);
            $matched = true;
        }
        // /ad-accounts/{id}/delete パターン
        elseif (preg_match('#^/ad-accounts/(\d+)/delete$#', $path, $matches) && $requestMethod === 'POST') {
            require_once __DIR__ . '/../app/Controllers/AdAccountController.php';
            $controller = new \App\Controllers\AdAccountController();
            $controller->destroy($matches[1]);
            $matched = true;
        }
    }
    
    if (!$matched) {
        $routes['404']();
    }
}