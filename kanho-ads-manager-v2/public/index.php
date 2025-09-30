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
        require_once __DIR__ . '/../views/dashboard.php';
    },
    
    // クライアント管理
    'GET /clients' => function() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/clients/index.php';
    },
    
    // 404 ハンドラー
    '404' => function() {
        http_response_code(404);
        require_once __DIR__ . '/../views/errors/404.php';
    }
];

// リクエストマッチング
$route = $requestMethod . ' ' . $path;

if (isset($routes[$route])) {
    try {
        $routes[$route]();
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
} else {
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
    
    if (!$matched) {
        $routes['404']();
    }
}