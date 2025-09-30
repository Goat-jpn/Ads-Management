<?php

namespace App\Services;

/**
 * Google Ads API Service
 * 
 * Google Ads APIとの連携を処理するサービスクラス
 * アカウント情報取得、キャンペーンデータ取得などを実装
 */
class GoogleAdsService
{
    private $config;
    private $accessToken;
    private $lastError;
    
    public function __construct()
    {
        // 環境変数を直接読み込む（.envファイル経由）
        $this->loadEnvironmentVariables();
        
        $this->config = require __DIR__ . '/../../config/google_ads.php';
        $this->accessToken = $this->getAccessToken();
    }
    
    /**
     * 環境変数を読み込む
     */
    private function loadEnvironmentVariables()
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue; // コメント行をスキップ
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv(sprintf('%s=%s', $name, $value));
                }
            }
        }
    }
    
    /**
     * リフレッシュトークンからアクセストークンを取得
     */
    private function getAccessToken()
    {
        try {
            $tokenData = [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'refresh_token' => $this->config['refresh_token'],
                'grant_type' => 'refresh_token'
            ];
            
            $this->log('DEBUG', 'Attempting to get access token with client_id: ' . substr($this->config['client_id'], 0, 20) . '...');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->config['oauth2']['tokenCredentialUri']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['request_settings']['timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 開発環境用
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $this->lastError = "CURL Error in getAccessToken: " . $curlError;
                $this->log('ERROR', $this->lastError);
                return null;
            }
            
            $this->log('DEBUG', "Token request response: HTTP {$httpCode} - " . substr($response, 0, 200) . '...');
            
            if ($httpCode === 200) {
                $tokenResponse = json_decode($response, true);
                if ($tokenResponse && isset($tokenResponse['access_token'])) {
                    $this->log('INFO', 'Access token obtained successfully');
                    return $tokenResponse['access_token'];
                } else {
                    $this->lastError = "Invalid token response format: " . $response;
                    $this->log('ERROR', $this->lastError);
                    return null;
                }
            }
            
            // 401 Unauthorized の場合は特別な処理
            if ($httpCode === 401) {
                $errorData = json_decode($response, true);
                if (isset($errorData['error']) && $errorData['error'] === 'invalid_client') {
                    $this->lastError = "OAuth設定エラー: クライアントIDまたはクライアントシークレットが無効です。Google Cloud Consoleで設定を確認してください。";
                } else {
                    $this->lastError = "認証エラー: リフレッシュトークンが無効または期限切れです。新しいトークンを取得してください。";
                }
            } else {
                $this->lastError = "Failed to get access token: HTTP {$httpCode} - {$response}";
            }
            
            $this->log('ERROR', $this->lastError);
            return null;
            
        } catch (Exception $e) {
            $this->lastError = "Exception in getAccessToken: " . $e->getMessage();
            $this->log('ERROR', $this->lastError);
            return null;
        }
    }
    
    /**
     * アクセス可能な顧客アカウント一覧を取得
     */
    public function getAccessibleCustomers()
    {
        if (!$this->accessToken) {
            $this->lastError = "No valid access token available";
            return false;
        }
        
        try {
            $url = $this->config['endpoints']['base_url'] . $this->config['endpoints']['accounts'];
            
            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'developer-token: ' . $this->config['developer_token'],
                'Content-Type: application/json'
            ];
            
            // Manager Account IDが設定されている場合
            if (!empty($this->config['login_customer_id'])) {
                $headers[] = 'login-customer-id: ' . $this->config['login_customer_id'];
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['request_settings']['timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 開発環境用
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $this->lastError = "CURL Error in getAccessibleCustomers: " . $curlError;
                $this->log('ERROR', $this->lastError);
                return false;
            }
            
            if ($httpCode !== 200) {
                $this->lastError = "API Error in getAccessibleCustomers: HTTP {$httpCode} - {$response}";
                $this->log('ERROR', $this->lastError);
                return false;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['resourceNames'])) {
                $this->lastError = "Invalid response format in getAccessibleCustomers: " . $response;
                $this->log('ERROR', $this->lastError);
                return false;
            }
            
            // 顧客IDを抽出
            $customerIds = [];
            foreach ($data['resourceNames'] as $resourceName) {
                // "customers/1234567890" から "1234567890" を抽出
                if (preg_match('/customers\/(\d+)/', $resourceName, $matches)) {
                    $customerIds[] = $matches[1];
                }
            }
            
            $this->log('INFO', 'Retrieved ' . count($customerIds) . ' accessible customers');
            return $customerIds;
            
        } catch (Exception $e) {
            $this->lastError = "Exception in getAccessibleCustomers: " . $e->getMessage();
            $this->log('ERROR', $this->lastError);
            return false;
        }
    }
    
    /**
     * 特定の顧客の詳細情報を取得
     */
    public function getCustomerInfo($customerId)
    {
        if (!$this->accessToken) {
            $this->lastError = "No valid access token available";
            return false;
        }
        
        try {
            $url = $this->config['endpoints']['base_url'] . "/v16/customers/{$customerId}/googleAds:search";
            
            // GAQL (Google Ads Query Language) でアカウント情報を取得
            $query = "SELECT customer.id, customer.descriptive_name, customer.currency_code, customer.time_zone, customer.status FROM customer WHERE customer.id = {$customerId}";
            
            $requestData = [
                'query' => $query
            ];
            
            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'developer-token: ' . $this->config['developer_token'],
                'Content-Type: application/json'
            ];
            
            if (!empty($this->config['login_customer_id'])) {
                $headers[] = 'login-customer-id: ' . $this->config['login_customer_id'];
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['request_settings']['timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 開発環境用
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $this->lastError = "CURL Error in getCustomerInfo: " . $curlError;
                return false;
            }
            
            if ($httpCode !== 200) {
                $this->lastError = "API Error in getCustomerInfo: HTTP {$httpCode} - {$response}";
                return false;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['results']) || empty($data['results'])) {
                $this->lastError = "No customer data found for ID: {$customerId}";
                return false;
            }
            
            $customerData = $data['results'][0]['customer'];
            
            return [
                'id' => $customerData['id'] ?? $customerId,
                'name' => $customerData['descriptiveName'] ?? "Account {$customerId}",
                'currency' => $customerData['currencyCode'] ?? 'JPY',
                'timezone' => $customerData['timeZone'] ?? 'Asia/Tokyo',
                'status' => $customerData['status'] ?? 'UNKNOWN'
            ];
            
        } catch (Exception $e) {
            $this->lastError = "Exception in getCustomerInfo: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * アクセス可能なすべての顧客の詳細情報を取得
     */
    public function getAllCustomersInfo()
    {
        // デモモードチェック
        $demoMode = $_ENV['GOOGLE_ADS_DEMO_MODE'] ?? 'false';
        
        if ($demoMode === 'true') {
            $this->log('INFO', 'Returning demo customers data');
            return $this->getDemoCustomersInfo();
        }
        
        $customerIds = $this->getAccessibleCustomers();
        
        if (!$customerIds) {
            return false;
        }
        
        $customersInfo = [];
        
        foreach ($customerIds as $customerId) {
            // API Rate Limitを避けるための遅延
            if (count($customersInfo) > 0) {
                usleep($this->config['request_settings']['rate_limit_delay'] * 1000);
            }
            
            $customerInfo = $this->getCustomerInfo($customerId);
            
            if ($customerInfo) {
                $customersInfo[] = $customerInfo;
            }
        }
        
        $this->log('INFO', 'Retrieved info for ' . count($customersInfo) . ' customers');
        return $customersInfo;
    }
    
    /**
     * 接続テスト
     */
    public function testConnection()
    {
        try {
            // デモモード: 認証に問題がある場合はダミーデータで動作確認を可能にする
            $demoMode = $_ENV['GOOGLE_ADS_DEMO_MODE'] ?? 'false';
            
            if ($demoMode === 'true') {
                $this->log('INFO', 'Running in demo mode');
                return [
                    'success' => true,
                    'message' => 'Google Ads API接続成功 (デモモード)',
                    'accounts_found' => 3,
                    'sample_accounts' => ['123456789', '987654321', '555666777'],
                    'demo_mode' => true
                ];
            }
            
            if (!$this->accessToken) {
                // 詳細なエラー情報を含める
                $tokenError = $this->getLastError();
                
                // 認証問題の場合は詳しい説明を追加
                if (strpos($tokenError, 'invalid_client') !== false) {
                    $suggestion = "\n\n解決方法:\n1. Google Cloud Consoleでクライアント設定を確認\n2. クライアントIDとシークレットが正しいか確認\n3. リダイレクトURIの設定を確認";
                } elseif (strpos($tokenError, '401') !== false) {
                    $suggestion = "\n\n解決方法:\n1. リフレッシュトークンの再生成\n2. OAuth同意画面の再設定\n3. APIスコープの確認";
                } else {
                    $suggestion = "\n\n解決方法:\n1. ネットワーク接続を確認\n2. API設定を見直し\n3. ログファイルで詳細を確認";
                }
                
                return [
                    'success' => false,
                    'message' => 'アクセストークンの取得に失敗しました: ' . $tokenError . $suggestion,
                    'accounts_found' => 0,
                    'error_type' => 'authentication'
                ];
            }
            
            $customers = $this->getAccessibleCustomers();
            
            if ($customers === false) {
                return [
                    'success' => false,
                    'message' => $this->getLastError(),
                    'accounts_found' => 0,
                    'error_type' => 'api_call'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Google Ads API接続成功',
                'accounts_found' => count($customers),
                'sample_accounts' => array_slice($customers, 0, 3) // 最初の3アカウントのIDを表示
            ];
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage(),
                'accounts_found' => 0,
                'error_type' => 'exception'
            ];
        }
    }
    
    /**
     * ダミーデータを返す (デモモード用)
     */
    public function getDemoCustomersInfo()
    {
        return [
            [
                'id' => '123456789',
                'name' => 'デモアカウント1 - 検索広告',
                'currency' => 'JPY',
                'timezone' => 'Asia/Tokyo',
                'status' => 'ENABLED'
            ],
            [
                'id' => '987654321',
                'name' => 'デモアカウント2 - ディスプレイ広告',
                'currency' => 'JPY',
                'timezone' => 'Asia/Tokyo', 
                'status' => 'ENABLED'
            ],
            [
                'id' => '555666777',
                'name' => 'デモアカウント3 - 動画広告',
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'status' => 'PAUSED'
            ]
        ];
    }
    
    /**
     * 最後のエラーメッセージを取得
     */
    public function getLastError()
    {
        return $this->lastError ?? 'Unknown error';
    }
    
    /**
     * ログ出力
     */
    private function log($level, $message)
    {
        if (!$this->config['logging']['enabled']) {
            return;
        }
        
        try {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
            
            $logFile = $this->config['logging']['file_path'];
            $logDir = dirname($logFile);
            
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // ログ出力でエラーが発生した場合は無視（無限ループを防ぐ）
            error_log("Failed to write to log file: " . $e->getMessage());
        }
    }
}