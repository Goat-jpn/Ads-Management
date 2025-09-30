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
        // TODO: 実際のAPI連携実装
        // 現在はダミーの同期処理
        
        try {
            switch ($account['platform']) {
                case AdAccount::PLATFORM_GOOGLE:
                    $this->syncGoogleAdsAccount($account);
                    break;
                    
                case AdAccount::PLATFORM_YAHOO:
                    $this->syncYahooAdsAccount($account);
                    break;
                    
                default:
                    throw new \Exception('サポートされていないプラットフォームです。');
            }
            
            $this->adAccountModel->updateSyncStatus($account['id'], 'success');
            
        } catch (\Exception $e) {
            $this->adAccountModel->updateSyncStatus($account['id'], 'error', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Google Ads API同期（ダミー実装）
     */
    private function syncGoogleAdsAccount($account)
    {
        // TODO: Google Ads API実装
        // 現在はダミー処理
        sleep(1); // 同期処理をシミュレート
        
        // 実装例:
        // 1. アクセストークンを取得
        // 2. Google Ads APIでアカウント情報を取得
        // 3. キャンペーン、広告グループ、キーワードデータを取得
        // 4. データベースに保存
        
        return true;
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
            
        } catch (Exception $e) {
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
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '接続テストに失敗しました: ' . $e->getMessage()
            ]);
        }
    }
}