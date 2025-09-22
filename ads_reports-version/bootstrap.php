<?php

/**
 * アプリケーションブートストラップファイル
 * 基本的な設定とオートローダーの初期化を行う
 */

// エラーレポートの設定
error_reporting(E_ALL);
ini_set('display_errors', '1');

// タイムゾーンの設定
date_default_timezone_set('Asia/Tokyo');

// メモリ制限の設定
ini_set('memory_limit', '256M');

// 実行時間制限の設定
set_time_limit(300); // 5分

// セッション設定
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // HTTPS環境では1に設定
ini_set('session.cookie_samesite', 'Strict');

// 基本パスの定義
define('APP_ROOT', __DIR__);
define('PUBLIC_ROOT', APP_ROOT . '/public');
define('CONFIG_ROOT', APP_ROOT . '/config');
define('STORAGE_ROOT', APP_ROOT . '/storage');
define('LOG_ROOT', APP_ROOT . '/logs');

// ディレクトリの作成（存在しない場合）
$directories = [STORAGE_ROOT, LOG_ROOT, STORAGE_ROOT . '/uploads', STORAGE_ROOT . '/temp', STORAGE_ROOT . '/cache'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Composerオートローダーの読み込み
$autoloadPath = APP_ROOT . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // 開発環境でComposerがインストールされていない場合の簡易オートローダー
    spl_autoload_register(function ($className) {
        $prefix = 'App\\';
        $baseDir = APP_ROOT . '/app/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) !== 0) {
            return;
        }
        
        $relativeClass = substr($className, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });

    // Config名前空間用のオートローダー
    spl_autoload_register(function ($className) {
        $prefix = 'Config\\';
        $baseDir = CONFIG_ROOT . '/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) !== 0) {
            return;
        }
        
        $relativeClass = substr($className, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });
}

// 環境変数の読み込み
use App\Utils\Environment;
Environment::load();

// アプリケーション設定の読み込み
$config = require CONFIG_ROOT . '/app.php';

// データベース接続の初期化
use Config\Database\Connection;
Connection::initialize($config['database']);

// ログシステムの初期化
use App\Utils\Logger;
Logger::getInstance($config['logging']['path'], $config['logging']['level']);

// セッションの開始
if (!session_id()) {
    session_name($config['session']['name']);
    session_start();
}

// エラーハンドラーの設定
set_error_handler(function($severity, $message, $file, $line) {
    $logger = Logger::getInstance();
    
    // PHP 7.4 compatible version of match() - convert to if-elseif
    if ($severity === E_ERROR || $severity === E_CORE_ERROR || $severity === E_COMPILE_ERROR || $severity === E_USER_ERROR) {
        $errorType = 'error';
    } elseif ($severity === E_WARNING || $severity === E_CORE_WARNING || $severity === E_COMPILE_WARNING || $severity === E_USER_WARNING) {
        $errorType = 'warning';
    } elseif ($severity === E_NOTICE || $severity === E_USER_NOTICE) {
        $errorType = 'info';
    } else {
        $errorType = 'debug';
    }
    
    $logger->log($errorType, "PHP Error: {$message}", [
        'file' => $file,
        'line' => $line,
        'severity' => $severity
    ]);
    
    // 開発環境では画面にも表示
    if (Environment::isDevelopment()) {
        return false; // PHPのデフォルトエラーハンドラーも実行
    }
    
    return true;
});

// 例外ハンドラーの設定
set_exception_handler(function($exception) {
    $logger = Logger::getInstance();
    
    $logger->error('Uncaught Exception: ' . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    // 開発環境では詳細表示
    if (Environment::isDevelopment()) {
        echo "<h1>Uncaught Exception</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        // 本番環境では汎用エラーページ
        http_response_code(500);
        echo "<h1>Internal Server Error</h1>";
        echo "<p>An error occurred. Please try again later.</p>";
    }
});

// シャットダウンハンドラーの設定
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        $logger = Logger::getInstance();
        $logger->critical('Fatal Error: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// ヘルパー関数の定義
if (!function_exists('config')) {
    /**
     * 設定値を取得
     */
    function config($key, $default = null) {
        static $config = null;
        if ($config === null) {
            $config = require CONFIG_ROOT . '/app.php';
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('env')) {
    /**
     * 環境変数を取得
     */
    function env($key, $default = null) {
        return Environment::get($key, $default);
    }
}

if (!function_exists('logger')) {
    /**
     * ログgerインスタンスを取得
     */
    function logger() {
        return Logger::getInstance();
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * 通貨フォーマット
     */
    function formatCurrency($amount, $currency = 'JPY') {
        // PHP 7.4 compatible version of match() - convert to if-elseif
        if ($currency === 'JPY') {
            return '¥' . number_format($amount);
        } elseif ($currency === 'USD') {
            return '$' . number_format($amount, 2);
        } elseif ($currency === 'EUR') {
            return '€' . number_format($amount, 2);
        } else {
            return number_format($amount, 2) . ' ' . $currency;
        }
    }
}

if (!function_exists('formatPercentage')) {
    /**
     * パーセンテージフォーマット
     */
    function formatPercentage($value, $decimals = 2) {
        return number_format($value, $decimals) . '%';
    }
}

if (!function_exists('sanitizeInput')) {
    /**
     * 入力値のサニタイズ
     */
    function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map('sanitizeInput', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * CSRFトークンを生成・取得
     */
    function csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * CSRFトークンを検証
     */
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// アプリケーション初期化完了ログ
logger()->info('Application bootstrapped successfully', [
    'environment' => env('APP_ENV', 'production'),
    'debug_mode' => env('APP_DEBUG', false),
    'timezone' => date_default_timezone_get(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
]);