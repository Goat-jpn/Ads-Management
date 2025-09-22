<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\AdAccount;
use App\Models\FeeSetting;

class ClientController extends BaseController
{
    private Client $clientModel;
    private AdAccount $adAccountModel;
    private FeeSetting $feeSettingModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
        $this->adAccountModel = new AdAccount();
        $this->feeSettingModel = new FeeSetting();
    }

    /**
     * クライアント一覧表示
     */
    public function index(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            
            if ($this->isAjaxRequest()) {
                $this->getClientsApi();
            } else {
                $this->render('admin/clients/index.php', [
                    'title' => 'クライアント管理'
                ]);
            }
        });
    }

    /**
     * クライアント一覧API
     */
    public function getClientsApi(): void
    {
        $pagination = $this->getPaginationParams();
        $params = $this->getQueryParams();
        
        // 検索条件
        $conditions = [];
        if (!empty($params['search'])) {
            $searchTerm = '%' . $params['search'] . '%';
            $sql = "SELECT * FROM clients 
                    WHERE (company_name LIKE ? OR contact_name LIKE ? OR email LIKE ?)
                    ORDER BY company_name ASC
                    LIMIT ? OFFSET ?";
            
            $offset = ($pagination['page'] - 1) * $pagination['per_page'];
            $clients = $this->clientModel->query($sql, [
                $searchTerm, $searchTerm, $searchTerm,
                $pagination['per_page'], $offset
            ]);
            
            $totalSql = "SELECT COUNT(*) FROM clients 
                        WHERE (company_name LIKE ? OR contact_name LIKE ? OR email LIKE ?)";
            $totalResult = $this->clientModel->query($totalSql, [$searchTerm, $searchTerm, $searchTerm]);
            $total = $totalResult[0]['COUNT(*)'] ?? 0;
        } else {
            $result = $this->clientModel->paginate($pagination['page'], $pagination['per_page']);
            $clients = $result['data'];
            $total = $result['pagination']['total'];
        }

        // 各クライアントに追加情報を付与
        foreach ($clients as &$client) {
            $client['ad_accounts_count'] = $this->adAccountModel->count(['client_id' => $client['id']]);
            $client['current_month_performance'] = $this->clientModel->getCurrentMonthPerformance($client['id']);
        }

        $this->successResponse([
            'clients' => $clients,
            'pagination' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'total' => $total,
                'total_pages' => ceil($total / $pagination['per_page'])
            ]
        ]);
    }

    /**
     * クライアント詳細表示
     */
    public function show(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            $params = $this->getQueryParams();
            $clientId = (int)($params['id'] ?? 0);

            if (!$clientId) {
                $this->errorResponse('クライアントIDが指定されていません', 400);
            }

            $client = $this->clientModel->find($clientId);
            if (!$client) {
                $this->errorResponse('クライアントが見つかりません', 404);
            }

            if ($this->isAjaxRequest()) {
                $this->getClientDetailApi($clientId);
            } else {
                $this->render('admin/clients/show.php', [
                    'title' => 'クライアント詳細',
                    'client' => $client
                ]);
            }
        });
    }

    /**
     * クライアント詳細API
     */
    public function getClientDetailApi(int $clientId): void
    {
        $client = $this->clientModel->find($clientId);
        $adAccounts = $this->clientModel->getAdAccounts($clientId);
        $feeSettings = $this->clientModel->getFeeSettings($clientId);
        $currentPerformance = $this->clientModel->getCurrentMonthPerformance($clientId);
        
        // 日付範囲パラメータ
        $dateRange = $this->getDateRangeParams();
        $dailyData = $this->clientModel->getDailyDataForPeriod(
            $clientId, 
            $dateRange['start_date'], 
            $dateRange['end_date']
        );

        $this->successResponse([
            'client' => $client,
            'ad_accounts' => $adAccounts,
            'fee_settings' => $feeSettings,
            'current_performance' => $currentPerformance,
            'daily_data' => $dailyData
        ]);
    }

    /**
     * 新規クライアント作成フォーム
     */
    public function create(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            
            $this->render('admin/clients/create.php', [
                'title' => '新規クライアント作成'
            ]);
        });
    }

    /**
     * クライアント作成処理
     */
    public function store(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            $this->verifyCsrfToken();

            $data = $this->requireParams([
                'company_name', 'contact_name', 'email', 'contract_start_date'
            ]);

            // バリデーション
            $errors = $this->validateClientData($data);
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
            }

            // 契約期間のバリデーション
            if (!empty($data['contract_end_date'])) {
                if (!$this->clientModel->validateContractPeriod($data['contract_start_date'], $data['contract_end_date'])) {
                    $errors[] = '契約終了日は開始日より後の日付を指定してください';
                }
            }

            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
            }

            // デフォルト値の設定
            $data['billing_day'] = $data['billing_day'] ?? 25;
            $data['payment_terms'] = $data['payment_terms'] ?? 30;
            $data['is_active'] = true;

            $client = $this->clientModel->create($data);
            
            $this->logActivity('client_created', ['client_id' => $client['id']]);
            $this->successResponse($client, 'クライアントを作成しました');
        });
    }

    /**
     * クライアント編集フォーム
     */
    public function edit(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            $params = $this->getQueryParams();
            $clientId = (int)($params['id'] ?? 0);

            $client = $this->clientModel->find($clientId);
            if (!$client) {
                $this->errorResponse('クライアントが見つかりません', 404);
            }

            $this->render('admin/clients/edit.php', [
                'title' => 'クライアント編集',
                'client' => $client
            ]);
        });
    }

    /**
     * クライアント更新処理
     */
    public function update(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            $this->verifyCsrfToken();

            $params = $this->getRequestParams();
            $clientId = (int)($params['id'] ?? 0);

            $client = $this->clientModel->find($clientId);
            if (!$client) {
                $this->errorResponse('クライアントが見つかりません', 404);
            }

            // バリデーション
            $errors = $this->validateClientData($params, $clientId);
            if (!empty($errors)) {
                $this->validationErrorResponse($errors);
            }

            // 更新データから不要なキーを除去
            unset($params['id'], $params['csrf_token']);

            $updatedClient = $this->clientModel->update($clientId, $params);
            
            $this->logActivity('client_updated', ['client_id' => $clientId]);
            $this->successResponse($updatedClient, 'クライアントを更新しました');
        });
    }

    /**
     * クライアント削除処理
     */
    public function destroy(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            $this->verifyCsrfToken();

            $params = $this->getRequestParams();
            $clientId = (int)($params['id'] ?? 0);

            $client = $this->clientModel->find($clientId);
            if (!$client) {
                $this->errorResponse('クライアントが見つかりません', 404);
            }

            // アクティブな広告アカウントがある場合は削除を拒否
            $activeAccounts = $this->adAccountModel->count(['client_id' => $clientId, 'is_active' => true]);
            if ($activeAccounts > 0) {
                $this->errorResponse(
                    'アクティブな広告アカウントが存在するため削除できません', 
                    400
                );
            }

            // 論理削除を実行
            $this->clientModel->softDelete($clientId);
            
            $this->logActivity('client_deleted', ['client_id' => $clientId]);
            $this->successResponse(null, 'クライアントを削除しました');
        });
    }

    /**
     * クライアントデータのバリデーション
     */
    private function validateClientData(array $data, int $excludeId = null): array
    {
        $errors = [];

        // 必須フィールドチェック
        if (empty($data['company_name'])) {
            $errors[] = '会社名は必須です';
        }

        if (empty($data['contact_name'])) {
            $errors[] = '担当者名は必須です';
        }

        // メールアドレスの形式チェック
        if (empty($data['email'])) {
            $errors[] = 'メールアドレスは必須です';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'メールアドレスの形式が正しくありません';
        } else {
            // メールアドレスの重複チェック
            $existing = $this->clientModel->findBy(['email' => $data['email']]);
            if ($existing && (!$excludeId || $existing['id'] != $excludeId)) {
                $errors[] = 'このメールアドレスは既に使用されています';
            }
        }

        // 契約開始日のチェック
        if (empty($data['contract_start_date'])) {
            $errors[] = '契約開始日は必須です';
        } elseif (!strtotime($data['contract_start_date'])) {
            $errors[] = '契約開始日の形式が正しくありません';
        }

        // 契約終了日のチェック
        if (!empty($data['contract_end_date']) && !strtotime($data['contract_end_date'])) {
            $errors[] = '契約終了日の形式が正しくありません';
        }

        // 請求日のチェック
        if (isset($data['billing_day']) && !$this->clientModel->validateBillingDay((int)$data['billing_day'])) {
            $errors[] = '請求日は1〜31の範囲で指定してください';
        }

        // 支払い条件のチェック
        if (isset($data['payment_terms']) && !$this->clientModel->validatePaymentTerms((int)$data['payment_terms'])) {
            $errors[] = '支払い条件は1〜365の範囲で指定してください';
        }

        return $errors;
    }

    /**
     * クライアントのステータス変更
     */
    public function toggleStatus(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            $this->verifyCsrfToken();

            $params = $this->getRequestParams();
            $clientId = (int)($params['id'] ?? 0);
            $isActive = filter_var($params['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $client = $this->clientModel->find($clientId);
            if (!$client) {
                $this->errorResponse('クライアントが見つかりません', 404);
            }

            $updatedClient = $this->clientModel->updateStatus($clientId, $isActive);
            
            $action = $isActive ? 'activated' : 'deactivated';
            $this->logActivity("client_{$action}", ['client_id' => $clientId]);
            
            $message = $isActive ? 'クライアントを有効にしました' : 'クライアントを無効にしました';
            $this->successResponse($updatedClient, $message);
        });
    }

    /**
     * 契約終了間近のクライアント一覧
     */
    public function getExpiringContracts(): void
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            
            $params = $this->getQueryParams();
            $daysAhead = (int)($params['days_ahead'] ?? 30);

            $expiringClients = $this->clientModel->getExpiringContracts($daysAhead);
            
            $this->successResponse([
                'clients' => $expiringClients,
                'days_ahead' => $daysAhead
            ]);
        });
    }
}