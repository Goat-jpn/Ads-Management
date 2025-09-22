<?php

namespace App\Controllers;

use App\Utils\Logger;

abstract class BaseController
{
    protected Logger $logger;
    protected array $data = [];

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    /**
     * JSONレスポンスを返す
     */
    protected function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * 成功レスポンス
     */
    protected function successResponse($data = null, string $message = 'Success'): void
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->jsonResponse($response);
    }

    /**
     * エラーレスポンス
     */
    protected function errorResponse(string $message, int $status = 400, array $errors = []): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $this->jsonResponse($response, $status);
    }

    /**
     * バリデーションエラーレスポンス
     */
    protected function validationErrorResponse(array $errors): void
    {
        $this->errorResponse('バリデーションエラーが発生しました', 422, $errors);
    }

    /**
     * テンプレートを読み込んで表示
     */
    protected function render(string $template, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);
        
        $templatePath = APP_ROOT . '/public/' . ltrim($template, '/');
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found: {$template}");
        }

        // テンプレート用の変数を展開
        extract($this->data);
        
        // バッファリング開始
        ob_start();
        include $templatePath;
        $content = ob_get_clean();
        
        echo $content;
    }

    /**
     * リダイレクト
     */
    protected function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /**
     * リクエストメソッドの取得
     */
    protected function getRequestMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * POSTデータの取得
     */
    protected function getPostData(): array
    {
        if ($this->getRequestMethod() !== 'POST') {
            return [];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }

        return $_POST;
    }

    /**
     * GETパラメータの取得
     */
    protected function getQueryParams(): array
    {
        return $_GET;
    }

    /**
     * リクエストパラメータの取得（POST優先）
     */
    protected function getRequestParams(): array
    {
        return array_merge($this->getQueryParams(), $this->getPostData());
    }

    /**
     * 必須パラメータのチェック
     */
    protected function requireParams(array $requiredParams): array
    {
        $params = $this->getRequestParams();
        $missing = [];

        foreach ($requiredParams as $param) {
            if (!isset($params[$param]) || $params[$param] === '') {
                $missing[] = $param;
            }
        }

        if (!empty($missing)) {
            $this->errorResponse(
                '必須パラメータが不足しています: ' . implode(', ', $missing),
                400,
                ['missing_params' => $missing]
            );
        }

        return $params;
    }

    /**
     * CSRFトークンの検証
     */
    protected function verifyCsrfToken(): void
    {
        $params = $this->getRequestParams();
        $token = $params['csrf_token'] ?? '';

        if (!verify_csrf_token($token)) {
            $this->errorResponse('不正なリクエストです', 403);
        }
    }

    /**
     * 認証チェック
     */
    protected function requireAuth(): array
    {
        if (!isset($_SESSION['admin_id'])) {
            if ($this->isAjaxRequest()) {
                $this->errorResponse('認証が必要です', 401);
            } else {
                $this->redirect('/login');
            }
        }

        return $_SESSION;
    }

    /**
     * Ajaxリクエストかどうかの判定
     */
    protected function isAjaxRequest(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['CONTENT_TYPE']) && 
                strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    }

    /**
     * ページネーション用パラメータの取得
     */
    protected function getPaginationParams(): array
    {
        $params = $this->getQueryParams();
        
        return [
            'page' => max(1, (int)($params['page'] ?? 1)),
            'per_page' => min(100, max(10, (int)($params['per_page'] ?? 20)))
        ];
    }

    /**
     * 日付範囲パラメータの取得
     */
    protected function getDateRangeParams(): array
    {
        $params = $this->getQueryParams();
        
        $startDate = $params['start_date'] ?? date('Y-m-01'); // 月初
        $endDate = $params['end_date'] ?? date('Y-m-d'); // 今日
        
        // 日付の妥当性チェック
        if (!strtotime($startDate) || !strtotime($endDate)) {
            $this->errorResponse('不正な日付形式です', 400);
        }
        
        if (strtotime($startDate) > strtotime($endDate)) {
            $this->errorResponse('開始日は終了日より前の日付を指定してください', 400);
        }
        
        return [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    /**
     * ファイルアップロードの処理
     */
    protected function handleFileUpload(string $fieldName, array $allowedTypes = []): ?string
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fieldName];
        $fileName = $file['name'];
        $fileType = $file['type'];
        $fileSize = $file['size'];
        $tempPath = $file['tmp_name'];

        // ファイルタイプチェック
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes)) {
                $this->errorResponse(
                    '許可されていないファイル形式です。許可形式: ' . implode(', ', $allowedTypes),
                    400
                );
            }
        }

        // ファイルサイズチェック（10MB制限）
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($fileSize > $maxSize) {
            $this->errorResponse('ファイルサイズが大きすぎます（最大10MB）', 400);
        }

        // 保存先ディレクトリの作成
        $uploadDir = STORAGE_ROOT . '/uploads/' . date('Y/m/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 一意なファイル名を生成
        $newFileName = uniqid() . '_' . basename($fileName);
        $savePath = $uploadDir . $newFileName;

        // ファイル移動
        if (!move_uploaded_file($tempPath, $savePath)) {
            $this->errorResponse('ファイルの保存に失敗しました', 500);
        }

        return $savePath;
    }

    /**
     * ログイン履歴の記録
     */
    protected function logActivity(string $action, array $details = []): void
    {
        $adminId = $_SESSION['admin_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $this->logger->info("Admin Activity: {$action}", [
            'admin_id' => $adminId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'details' => $details
        ]);
    }

    /**
     * エラー処理用のtry-catchヘルパー
     */
    protected function handleRequest(callable $callback): void
    {
        try {
            $callback();
        } catch (\Exception $e) {
            $this->logger->error('Controller Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->isAjaxRequest()) {
                $this->errorResponse('エラーが発生しました', 500);
            } else {
                $this->render('admin/error.php', [
                    'error_message' => '予期しないエラーが発生しました。'
                ]);
            }
        }
    }
}