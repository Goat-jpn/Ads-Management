<?php

/**
 * ヘルパー関数
 */

if (!function_exists('h')) {
    /**
     * HTMLエスケープ
     */
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    /**
     * 設定値取得
     */
    function config($key, $default = null) {
        static $config = null;
        
        if ($config === null) {
            $config = require __DIR__ . '/../../config/app.php';
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('env')) {
    /**
     * 環境変数取得
     */
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('base_path')) {
    /**
     * ベースパス取得
     */
    function base_path($path = '') {
        return __DIR__ . '/../../' . ltrim($path ?? '', '/');
    }
}

if (!function_exists('public_path')) {
    /**
     * publicパス取得
     */
    function public_path($path = '') {
        return base_path('public/' . ltrim($path ?? '', '/'));
    }
}

if (!function_exists('storage_path')) {
    /**
     * storageパス取得
     */
    function storage_path($path = '') {
        return base_path('storage/' . ltrim($path ?? '', '/'));
    }
}

if (!function_exists('asset')) {
    /**
     * アセットURL生成
     */
    function asset($path) {
        $baseUrl = rtrim(config('app.url') ?? '', '/');
        return $baseUrl . '/' . ltrim($path ?? '', '/');
    }
}

if (!function_exists('url')) {
    /**
     * URL生成
     */
    function url($path = '') {
        $baseUrl = rtrim(config('app.url') ?? '', '/');
        return $baseUrl . '/' . ltrim($path ?? '', '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * リダイレクト
     */
    function redirect($path) {
        $url = url($path);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('old')) {
    /**
     * 古い入力値取得
     */
    function old($key, $default = '') {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * CSRFトークン生成
     */
    function csrf_token() {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * CSRFフィールドHTML生成
     */
    function csrf_field() {
        $token = csrf_token();
        return "<input type=\"hidden\" name=\"_token\" value=\"{$token}\">";
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * CSRFトークン検証
     */
    function verify_csrf($token) {
        return isset($_SESSION['_token']) && hash_equals($_SESSION['_token'], $token);
    }
}

if (!function_exists('flash')) {
    /**
     * フラッシュメッセージ設定
     */
    function flash($key, $message) {
        $_SESSION['_flash'][$key] = $message;
    }
}

if (!function_exists('get_flash')) {
    /**
     * フラッシュメッセージ取得
     */
    function get_flash($key, $default = null) {
        $message = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }
}

if (!function_exists('has_flash')) {
    /**
     * フラッシュメッセージ存在確認
     */
    function has_flash($key) {
        return isset($_SESSION['_flash'][$key]);
    }
}

if (!function_exists('auth')) {
    /**
     * 認証ユーザー取得
     */
    function auth() {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_logged_in')) {
    /**
     * ログイン状態確認
     */
    function is_logged_in() {
        return isset($_SESSION['user_id']) || isset($_SESSION['user']) || isset($_SESSION['is_logged_in']);
    }
}

if (!function_exists('format_currency')) {
    /**
     * 通貨フォーマット
     */
    function format_currency($amount, $currency = 'JPY') {
        switch ($currency) {
            case 'JPY':
                return '¥' . number_format($amount);
            case 'USD':
                return '$' . number_format($amount, 2);
            default:
                return number_format($amount, 2) . ' ' . $currency;
        }
    }
}

if (!function_exists('parse_tags')) {
    /**
     * JSONタグを文字列に変換
     */
    function parse_tags($jsonTags) {
        if (empty($jsonTags)) {
            return '';
        }
        
        $tags = json_decode($jsonTags, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($tags)) {
            return $jsonTags; // JSON解析に失敗した場合はそのまま返す
        }
        
        return implode(', ', $tags);
    }
}

if (!function_exists('format_tags')) {
    /**
     * タグのHTML表示用フォーマット
     */
    function format_tags($jsonTags) {
        if (empty($jsonTags)) {
            return '<span class="text-muted">-</span>';
        }
        
        $tags = json_decode($jsonTags, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($tags)) {
            return '<span class="badge bg-secondary">' . h($jsonTags) . '</span>';
        }
        
        $output = [];
        foreach ($tags as $tag) {
            $output[] = '<span class="badge bg-info me-1">' . h($tag) . '</span>';
        }
        
        return implode('', $output);
    }
}

if (!function_exists('format_date')) {
    /**
     * 日付フォーマット
     */
    function format_date($date, $format = 'Y年m月d日') {
        if (!$date) return '';
        
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        return $date->format($format);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * 日時フォーマット
     */
    function format_datetime($datetime, $format = 'Y年m月d日 H:i') {
        return format_date($datetime, $format);
    }
}

if (!function_exists('logger')) {
    /**
     * ログ出力
     */
    function logger($level, $message, $context = []) {
        $logPath = storage_path(config('log.path'));
        $logDir = dirname($logPath);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents($logPath, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('abort')) {
    /**
     * エラーページ表示
     */
    function abort($code, $message = '') {
        http_response_code($code);
        
        $messages = [
            404 => 'Page Not Found',
            403 => 'Forbidden',
            500 => 'Internal Server Error'
        ];
        
        $title = $messages[$code] ?? 'Error';
        $content = $message ?: $title;
        
        echo "<!DOCTYPE html>
        <html>
        <head><title>{$title}</title></head>
        <body>
            <h1>{$code} - {$title}</h1>
            <p>{$content}</p>
        </body>
        </html>";
        exit;
    }
}