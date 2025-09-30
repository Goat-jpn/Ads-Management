<?php

namespace App\Controllers;

use App\Models\AdAccount;
use App\Models\Client;
use App\Services\GoogleAdsService;

class AdAccountController
{
    private $adAccountModel;
    private $clientModel;
    private $googleAdsService;
    
    public function __construct()
    {
        $this->adAccountModel = new AdAccount();
        $this->clientModel = new Client();
        $this->googleAdsService = new GoogleAdsService();
    }
    
    /**
     * 広告アカウント一覧表示
     */
    public function index()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        // 検索・フィルタリング
        $search = $_GET['search'] ?? '';
        $platform = $_GET['platform'] ?? '';
        $clientId = $_GET['client_id'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        
        // 広告アカウント一覧取得
        if (!empty($search)) {
            $accounts = $this->adAccountModel->searchAccounts($search, $clientId);
        } else {
            $result = $this->adAccountModel->paginate($page, $perPage, 'created_at', 'DESC');
            $accounts = $result['data'];
        }
        
        // クライアント一覧（フィルタ用）
        $clients = $this->clientModel->getActiveClients();
        
        // プラットフォーム統計
        $platformStats = $this->adAccountModel->countByPlatform();
        
        require_once __DIR__ . '/../../views/ad_accounts/index.php';
    }
    
    /**
     * 広告アカウント詳細表示
     */
    public function show($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $account = $this->adAccountModel->find($id);
        
        if (!$account) {
            flash('error', '広告アカウントが見つかりませんでした。');
            redirect('/ad-accounts');
            return;
        }
        
        // クライアント情報を取得
        $client = $this->clientModel->find($account['client_id']);
        
        // 同期履歴やパフォーマンスデータ（今後実装）
        $syncHistory = [];
        $performanceData = [];
        
        require_once __DIR__ . '/../../views/ad_accounts/show.php';
    }
    
    /**
     * 新規広告アカウント登録フォーム
     */
    public function create()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        // クライアント一覧
        $clients = $this->clientModel->getActiveClients();
        
        require_once __DIR__ . '/../../views/ad_accounts/create.php';
    }
    
    /**
     * 広告アカウント登録処理
     */
    public function store()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        // バリデーション
        $errors = $this->validateAccountData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect('/ad-accounts/create');
            return;
        }
        
        // 広告アカウント作成
        $accountData = [
            'client_id' => (int)$_POST['client_id'],
            'platform' => $_POST['platform'],
            'account_id' => trim($_POST['account_id']),
            'account_name' => trim($_POST['account_name']),
            'currency' => $_POST['currency'] ?? 'JPY',
            'timezone' => $_POST['timezone'] ?? 'Asia/Tokyo',
            'sync_enabled' => isset($_POST['sync_enabled']) ? 1 : 0,
            'status' => $_POST['status'] ?? AdAccount::STATUS_INACTIVE
        ];
        
        try {
            $adAccountId = $this->adAccountModel->create($accountData);
            
            if ($adAccountId) {
                flash('success', '広告アカウントを登録しました。');
                redirect("/ad-accounts/{$adAccountId}");
            } else {
                throw new \Exception('アカウントの作成に失敗しました。');
            }
        } catch (\Exception $e) {
            flash('error', '広告アカウントの登録に失敗しました: ' . $e->getMessage());
            $_SESSION['old_input'] = $_POST;
            redirect('/ad-accounts/create');
        }
    }
    
    /**
     * 広告アカウント編集フォーム
     */
    public function edit($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $account = $this->adAccountModel->find($id);
        
        if (!$account) {
            flash('error', '広告アカウントが見つかりませんでした。');
            redirect('/ad-accounts');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
        // クライアント一覧
        $clients = $this->clientModel->getActiveClients();
        
        require_once __DIR__ . '/../../views/ad_accounts/edit.php';
    }
    
    /**
     * 広告アカウント更新処理
     */
    public function update($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $account = $this->adAccountModel->find($id);
        
        if (!$account) {
            flash('error', '広告アカウントが見つかりませんでした。');
            redirect('/ad-accounts');
            return;
        }
        
        // バリデーション
        $errors = $this->validateAccountData($_POST, $id);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect("/ad-accounts/{$id}/edit");
            return;
        }
        
        // 広告アカウント更新
        $accountData = [
            'client_id' => (int)$_POST['client_id'],
            'platform' => $_POST['platform'],
            'account_id' => trim($_POST['account_id']),
            'account_name' => trim($_POST['account_name']),
            'currency' => $_POST['currency'] ?? 'JPY',
            'timezone' => $_POST['timezone'] ?? 'Asia/Tokyo',
            'sync_enabled' => isset($_POST['sync_enabled']) ? 1 : 0,
            'status' => $_POST['status'] ?? AdAccount::STATUS_INACTIVE
        ];
        
        try {
            if ($this->adAccountModel->update($id, $accountData)) {
                flash('success', '広告アカウント情報を更新しました。');
                redirect("/ad-accounts/{$id}");
            } else {
                throw new \Exception('アカウントの更新に失敗しました。');
            }
        } catch (\Exception $e) {
            flash('error', '広告アカウントの更新に失敗しました: ' . $e->getMessage());
            $_SESSION['old_input'] = $_POST;
            redirect("/ad-accounts/{$id}/edit");
        }
    }
    
    /**
     * 広告アカウント削除処理
     */
    public function destroy($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $account = $this->adAccountModel->find($id);
        
        if (!$account) {
            flash('error', '広告アカウントが見つかりませんでした。');
            redirect('/ad-accounts');
            return;
        }
        
        try {
            if ($this->adAccountModel->delete($id)) {
                flash('success', '広告アカウントを削除しました。');
            } else {
                throw new \Exception('アカウントの削除に失敗しました。');
            }
        } catch (\Exception $e) {
            flash('error', '広告アカウントの削除に失敗しました: ' . $e->getMessage());
        }
        
        redirect('/ad-accounts');
    }
    
    /**
     * API認証設定
     */
    public function auth($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $account = $this->adAccountModel->find($id);
        
        if (!$account) {
            flash('error', '広告アカウントが見つかりませんでした。');
            redirect('/ad-accounts');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->saveAuthCredentials($id);
        }
        
        require_once __DIR__ . '/../../views/ad_accounts/auth.php';
    }
    
    /**
     * API認証情報保存
     */
    private function saveAuthCredentials($id)
    {
        $accessToken = $_POST['access_token'] ?? '';
        $refreshToken = $_POST['refresh_token'] ?? '';
        
        if (empty($accessToken)) {
            flash('error', 'アクセストークンは必須です。');
            redirect("/ad-accounts/{$id}/auth");
            return;
        }
        
        try {
            $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
            
            $this->adAccountModel->saveEncryptedTokens($id, $accessToken, $refreshToken, $expiresAt);
            $this->adAccountModel->update($id, ['status' => AdAccount::STATUS_ACTIVE]);
            
            flash('success', 'API認証情報を保存しました。');
            redirect("/ad-accounts/{$id}");
        } catch (\Exception $e) {
            flash('error', 'API認証情報の保存に失敗しました: ' . $e->getMessage());
            redirect("/ad-accounts/{$id}/auth");
        }
    }
    
    /**
     * データ同期実行
     */
    public function sync($id = null)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        try {
            if ($id) {
                // 特定のアカウントを同期
                $account = $this->adAccountModel->find($id);
                if (!$account) {
                    throw new \Exception('アカウントが見つかりません。');
                }
                
                $this->syncAccount($account);
                flash('success', 'データ同期を実行しました。');
                redirect("/ad-accounts/{$id}");
            } else {
                // 同期が必要な全アカウントを同期
                $accounts = $this->adAccountModel->getAccountsNeedingSync();
                
                foreach ($accounts as $account) {
                    $this->syncAccount($account);
                }
                
                flash('success', count($accounts) . '件のアカウントのデータ同期を実行しました。');
                redirect('/ad-accounts');
            }
        } catch (\Exception $e) {
            flash('error', 'データ同期に失敗しました: ' . $e->getMessage());
            redirect($id ? "/ad-accounts/{$id}" : '/ad-accounts');
        }
    }
    
    /**
     * 個別アカウントの同期処理
     */
    private function syncAccount($account)
    {
        $startTime = microtime(true);
        $campaignsSynced = 0;
        $adGroupsSynced = 0;
        
        try {
            // 同期開始をログに記録
            $this->adAccountModel->updateSyncStatus($account['id'], 'running', null);
            $this->logSyncHistory($account['id'], 'full', 'running', '同期を開始しました');
            
            switch ($account['platform']) {
                case AdAccount::PLATFORM_GOOGLE:
                    $result = $this->syncGoogleAdsAccount($account);
                    $campaignsSynced = $result['campaigns'] ?? 0;
                    $adGroupsSynced = $result['ad_groups'] ?? 0;
                    break;
                    
                case AdAccount::PLATFORM_YAHOO:
                    $result = $this->syncYahooAdsAccount($account);
                    $campaignsSynced = $result['campaigns'] ?? 0;
                    break;
                    
                default:
                    throw new \Exception('サポートされていないプラットフォームです。');
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000; // ミリ秒
            
            // 同期成功
            $this->adAccountModel->updateSyncStatus($account['id'], 'success', null);
            $this->logSyncHistory(
                $account['id'], 
                'full', 
                'success', 
                "同期が完了しました。キャンペーン: {$campaignsSynced}件、広告グループ: {$adGroupsSynced}件",
                $campaignsSynced,
                $adGroupsSynced,
                $executionTime
            );
            
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000; // ミリ秒
            
            // エラーログを記録
            $this->adAccountModel->updateSyncStatus($account['id'], 'error', $e->getMessage());
            $this->logSyncHistory(
                $account['id'], 
                'full', 
                'error', 
                $e->getMessage(),
                $campaignsSynced,
                $adGroupsSynced,
                $executionTime
            );
            
            throw $e;
        }
    }
    
    /**
     * Google Ads API同期（実装済み）
     */
    private function syncGoogleAdsAccount($account)
    {
        try {
            // Google Ads APIサービスを初期化
            $googleAdsService = new GoogleAdsService();
            
            // 接続テスト
            $connectionResult = $googleAdsService->testConnection();
            if (!$connectionResult['success']) {
                throw new \Exception('Google Ads APIへの接続に失敗しました: ' . $connectionResult['message']);
            }
            
            $customerId = $account['account_id'];
            $campaignsSynced = 0;
            $adGroupsSynced = 0;
            
            // 1. アカウント情報の更新
            $accountInfo = $googleAdsService->getAccountInfo($customerId);
            if ($accountInfo) {
                $this->updateAccountFromApi($account['id'], $accountInfo);
            } else {
                throw new \Exception("アカウントID {$customerId} の情報を取得できませんでした。");
            }
            
            // 2. キャンペーンデータの同期
            $campaigns = $googleAdsService->getCampaigns($customerId);
            $campaignsSynced = $this->saveCampaignsData($account['id'], $campaigns);
            
            // 3. 各キャンペーンの広告グループを同期
            foreach ($campaigns as $campaign) {
                try {
                    $adGroups = $googleAdsService->getAdGroups($customerId, $campaign['campaign_id']);
                    $adGroupsSynced += $this->saveAdGroupsData($campaign['campaign_id'], $adGroups);
                } catch (\Exception $e) {
                    // 個別キャンペーンのエラーは警告として記録し、処理を続行
                    error_log("Failed to sync ad groups for campaign {$campaign['campaign_id']}: " . $e->getMessage());
                }
            }
            
            // 4. パフォーマンスサマリーの取得
            $performanceSummary = $googleAdsService->getPerformanceSummary($customerId, 'LAST_30_DAYS');
            $this->savePerformanceData($account['id'], $performanceSummary);
            
            // 5. 同期時刻の更新
            $this->adAccountModel->update($account['id'], [
                'last_sync_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'campaigns' => $campaignsSynced,
                'ad_groups' => $adGroupsSynced
            ];
            
        } catch (\Exception $e) {
            error_log('Google Ads sync error: ' . $e->getMessage());
            throw new \Exception('Google Ads同期エラー: ' . $e->getMessage());
        }
    }
    
    /**
     * Yahoo Ads API同期（ダミー実装）
     */
    private function syncYahooAdsAccount($account)
    {
        // TODO: Yahoo Ads API実装
        // 現在はダミー処理
        sleep(1); // 同期処理をシミュレート
        
        return true;
    }
    
    /**
     * APIから取得したアカウント情報でアカウントを更新
     */
    private function updateAccountFromApi($accountId, $accountInfo)
    {
        try {
            $updateData = [
                'currency' => $accountInfo['currency'] ?? 'JPY',
                'timezone' => $accountInfo['timezone'] ?? 'Asia/Tokyo',
            ];
            
            // アカウント名が未設定の場合のみ更新
            $currentAccount = $this->adAccountModel->find($accountId);
            if (empty($currentAccount['account_name']) && !empty($accountInfo['name'])) {
                $updateData['account_name'] = $accountInfo['name'];
            }
            
            return $this->adAccountModel->update($accountId, $updateData);
            
        } catch (\Exception $e) {
            error_log('Failed to update account from API: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * キャンペーンデータをデータベースに保存
     */
    private function saveCampaignsData($accountId, $campaigns)
    {
        try {
            $db = getDatabaseConnection();
            
            // 既存のキャンペーンデータを削除（最新データで上書き）
            $stmt = $db->prepare("DELETE FROM campaigns WHERE ad_account_id = ?");
            $stmt->execute([$accountId]);
            
            // 新しいキャンペーンデータを挿入
            $insertStmt = $db->prepare("
                INSERT INTO campaigns (
                    ad_account_id, campaign_id, name, status, channel_type,
                    start_date, end_date, impressions, clicks, ctr,
                    cost_micros, average_cpc, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            foreach ($campaigns as $campaign) {
                $insertStmt->execute([
                    $accountId,
                    $campaign['campaign_id'],
                    $campaign['name'],
                    $campaign['status'],
                    $campaign['channel_type'],
                    $campaign['start_date'],
                    $campaign['end_date'],
                    $campaign['impressions'],
                    $campaign['clicks'],
                    $campaign['ctr'],
                    $campaign['cost_micros'],
                    $campaign['average_cpc']
                ]);
            }
            
            return count($campaigns);
            
        } catch (\Exception $e) {
            error_log('Failed to save campaigns data: ' . $e->getMessage());
            throw new \Exception('キャンペーンデータの保存に失敗しました: ' . $e->getMessage());
        }
    }
    
    /**
     * パフォーマンスデータをデータベースに保存
     */
    private function savePerformanceData($accountId, $performanceData)
    {
        try {
            $db = getDatabaseConnection();
            
            // パフォーマンスサマリーを保存
            $stmt = $db->prepare("
                INSERT INTO performance_summaries (
                    ad_account_id, date_range, impressions, clicks, ctr,
                    cost_micros, cost_yen, average_cpc, conversions,
                    conversion_rate, cost_per_conversion, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    impressions = VALUES(impressions),
                    clicks = VALUES(clicks),
                    ctr = VALUES(ctr),
                    cost_micros = VALUES(cost_micros),
                    cost_yen = VALUES(cost_yen),
                    average_cpc = VALUES(average_cpc),
                    conversions = VALUES(conversions),
                    conversion_rate = VALUES(conversion_rate),
                    cost_per_conversion = VALUES(cost_per_conversion),
                    updated_at = NOW()
            ");
            
            $stmt->execute([
                $accountId,
                $performanceData['date_range'],
                $performanceData['impressions'],
                $performanceData['clicks'],
                $performanceData['ctr'],
                $performanceData['cost_micros'],
                $performanceData['cost_yen'],
                $performanceData['average_cpc'],
                $performanceData['conversions'],
                $performanceData['conversion_rate'],
                $performanceData['cost_per_conversion']
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Failed to save performance data: ' . $e->getMessage());
            throw new \Exception('パフォーマンスデータの保存に失敗しました: ' . $e->getMessage());
        }
    }
    
    /**
     * 広告グループデータをデータベースに保存
     */
    private function saveAdGroupsData($campaignId, $adGroups)
    {
        try {
            $db = getDatabaseConnection();
            
            // campaignIdをデータベースの内部IDに変換
            $stmt = $db->prepare("SELECT id FROM campaigns WHERE campaign_id = ? LIMIT 1");
            $stmt->execute([$campaignId]);
            $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$campaign) {
                throw new \Exception("Campaign ID {$campaignId} not found in database");
            }
            
            $dbCampaignId = $campaign['id'];
            
            // 既存の広告グループデータを削除
            $stmt = $db->prepare("DELETE FROM ad_groups WHERE campaign_id = ?");
            $stmt->execute([$dbCampaignId]);
            
            // 新しい広告グループデータを挿入
            $insertStmt = $db->prepare("
                INSERT INTO ad_groups (
                    campaign_id, ad_group_id, name, status, cpc_bid_micros,
                    impressions, clicks, ctr, cost_micros, average_cpc,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            foreach ($adGroups as $adGroup) {
                $insertStmt->execute([
                    $dbCampaignId,
                    $adGroup['ad_group_id'],
                    $adGroup['name'],
                    $adGroup['status'],
                    $adGroup['cpc_bid_micros'],
                    $adGroup['impressions'],
                    $adGroup['clicks'],
                    $adGroup['ctr'],
                    $adGroup['cost_micros'],
                    $adGroup['average_cpc']
                ]);
            }
            
            return count($adGroups);
            
        } catch (\Exception $e) {
            error_log('Failed to save ad groups data: ' . $e->getMessage());
            throw new \Exception('広告グループデータの保存に失敗しました: ' . $e->getMessage());
        }
    }
    
    /**
     * 同期履歴をログに記録
     */
    private function logSyncHistory($accountId, $syncType, $status, $message, $campaignsSynced = 0, $adGroupsSynced = 0, $executionTime = 0)
    {
        try {
            $db = getDatabaseConnection();
            
            $stmt = $db->prepare("
                INSERT INTO sync_history (
                    ad_account_id, sync_type, status, message,
                    campaigns_synced, ad_groups_synced, execution_time_ms, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $accountId,
                $syncType,
                $status,
                $message,
                $campaignsSynced,
                $adGroupsSynced,
                $executionTime
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Failed to log sync history: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 入力データのバリデーション
     */
    private function validateAccountData($data, $accountId = null)
    {
        $errors = [];
        
        // クライアントID
        if (empty($data['client_id'])) {
            $errors[] = 'クライアントを選択してください。';
        }
        
        // プラットフォーム
        if (empty($data['platform'])) {
            $errors[] = 'プラットフォームを選択してください。';
        } elseif (!in_array($data['platform'], [AdAccount::PLATFORM_GOOGLE, AdAccount::PLATFORM_YAHOO])) {
            $errors[] = '有効なプラットフォームを選択してください。';
        }
        
        // アカウントID
        if (empty($data['account_id'])) {
            $errors[] = 'アカウントIDは必須です。';
        } elseif (strlen($data['account_id']) > 100) {
            $errors[] = 'アカウントIDは100文字以内で入力してください。';
        }
        
        // アカウント名
        if (empty($data['account_name'])) {
            $errors[] = 'アカウント名は必須です。';
        } elseif (strlen($data['account_name']) > 255) {
            $errors[] = 'アカウント名は255文字以内で入力してください。';
        }
        
        return $errors;
    }
    
    /**
     * Google Ads APIからアカウント一覧を取得（AJAX API）
     */
    public function getGoogleAccounts()
    {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            // まず接続テストを実行
            $connectionTest = $this->googleAdsService->testConnection();
            
            if (!$connectionTest['success']) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Google Ads API接続エラー',
                    'details' => $connectionTest['message'],
                    'debug_info' => [
                        'step' => 'connection_test',
                        'config_check' => [
                            'developer_token' => !empty($_ENV['GOOGLE_DEVELOPER_TOKEN']) ? 'set' : 'missing',
                            'client_id' => !empty($_ENV['GOOGLE_CLIENT_ID']) ? 'set' : 'missing',
                            'client_secret' => !empty($_ENV['GOOGLE_CLIENT_SECRET']) ? 'set' : 'missing',
                            'refresh_token' => !empty($_ENV['GOOGLE_REFRESH_TOKEN']) ? 'set' : 'missing'
                        ]
                    ]
                ]);
                return;
            }
            
            $customers = $this->googleAdsService->getAllCustomersInfo();
            
            if ($customers === false) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'アカウント情報の取得に失敗しました',
                    'details' => $this->googleAdsService->getLastError(),
                    'debug_info' => [
                        'step' => 'get_customers',
                        'connection_test_passed' => true
                    ]
                ]);
                return;
            }
            
            // レスポンス用にデータを整形
            $accounts = [];
            foreach ($customers as $customer) {
                $accounts[] = [
                    'id' => $customer['id'],
                    'name' => $customer['name'],
                    'currency' => $customer['currency'],
                    'timezone' => $customer['timezone'],
                    'status' => $customer['status']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'accounts' => $accounts,
                'total' => count($accounts),
                'debug_info' => [
                    'connection_test_passed' => true,
                    'customers_retrieved' => count($customers)
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'システムエラーが発生しました',
                'details' => $e->getMessage(),
                'debug_info' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
    
    /**
     * Google Ads API接続テスト（AJAX API）
     */
    public function testGoogleConnection()
    {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            $result = $this->googleAdsService->testConnection();
            echo json_encode($result);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '接続テストに失敗しました: ' . $e->getMessage()
            ]);
        }
    }
}