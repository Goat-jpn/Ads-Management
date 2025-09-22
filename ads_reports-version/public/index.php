<?php

/**
 * メインエントリーポイント
 * 全てのリクエストはこのファイルを経由して処理される
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\DashboardController;
use App\Controllers\ClientController;
use App\Utils\Logger;

// ルーティング処理
function route(): void
{
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    // クエリパラメータを除去
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = rtrim($path, '/') ?: '/';

    logger()->info("Request: {$requestMethod} {$path}");

    try {
        switch ($path) {
            // ダッシュボード
            case '/':
            case '/dashboard':
                $controller = new DashboardController();
                $controller->index();
                break;

            case '/api/dashboard/data':
                $controller = new DashboardController();
                $controller->getDashboardData();
                break;

            case '/api/dashboard/quick-stats':
                $controller = new DashboardController();
                $controller->getQuickStats();
                break;

            // クライアント管理
            case '/clients':
                $controller = new ClientController();
                $controller->index();
                break;

            case '/api/clients':
                $controller = new ClientController();
                if ($requestMethod === 'GET') {
                    $controller->getClientsApi();
                } elseif ($requestMethod === 'POST') {
                    $controller->store();
                }
                break;

            case '/clients/create':
                $controller = new ClientController();
                $controller->create();
                break;

            case (preg_match('/^\/clients\/(\d+)$/', $path, $matches) ? true : false):
                $controller = new ClientController();
                $_GET['id'] = $matches[1];
                if ($requestMethod === 'GET') {
                    $controller->show();
                } elseif ($requestMethod === 'PUT' || $requestMethod === 'POST') {
                    $controller->update();
                } elseif ($requestMethod === 'DELETE') {
                    $controller->destroy();
                }
                break;

            case (preg_match('/^\/clients\/(\d+)\/edit$/', $path, $matches) ? true : false):
                $controller = new ClientController();
                $_GET['id'] = $matches[1];
                $controller->edit();
                break;

            case (preg_match('/^\/api\/clients\/(\d+)$/', $path, $matches) ? true : false):
                $controller = new ClientController();
                $_GET['id'] = $matches[1];
                $controller->getClientDetailApi((int)$matches[1]);
                break;

            case '/api/clients/toggle-status':
                $controller = new ClientController();
                $controller->toggleStatus();
                break;

            case '/api/clients/expiring-contracts':
                $controller = new ClientController();
                $controller->getExpiringContracts();
                break;

            // 静的ファイル処理
            case (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $path) ? true : false):
                handleStaticFile($path);
                break;

            // 404 Not Found
            default:
                http_response_code(404);
                if (isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Not Found']);
                } else {
                    include __DIR__ . '/admin/404.php';
                }
                break;
        }
    } catch (Exception $e) {
        logger()->error('Routing Error: ' . $e->getMessage(), [
            'path' => $path,
            'method' => $requestMethod,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        http_response_code(500);
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal Server Error']);
        } else {
            include __DIR__ . '/admin/error.php';
        }
    }
}

/**
 * 静的ファイルの処理
 */
function handleStaticFile(string $path): void
{
    $filePath = __DIR__ . $path;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        return;
    }

    $mimeType = getMimeType($filePath);
    $lastModified = filemtime($filePath);
    
    // キャッシュヘッダー設定
    header("Content-Type: {$mimeType}");
    header("Last-Modified: " . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    header("Cache-Control: public, max-age=86400"); // 24時間キャッシュ
    
    // If-Modified-Since チェック
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        if ($ifModifiedSince >= $lastModified) {
            http_response_code(304);
            return;
        }
    }
    
    readfile($filePath);
}

/**
 * MIMEタイプの取得
 */
function getMimeType(string $filePath): string
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    return match ($extension) {
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
        default => 'application/octet-stream'
    };
}

/**
 * Ajaxリクエストの判定
 */
function isAjaxRequest(): bool
{
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (isset($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
}

// ルーティング実行
route();