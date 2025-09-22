<?php
/**
 * クライアント管理API - PHP版
 */

// CORS設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// プリフライトリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// エラー報告設定
error_reporting(E_ALL);
ini_set('display_errors', 0);

// オートロード
require_once '../../bootstrap.php';

try {
    $clientModel = new Client();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($clientModel);
            break;
            
        case 'POST':
            handlePostRequest($clientModel);
            break;
            
        case 'PUT':
            handlePutRequest($clientModel);
            break;
            
        case 'DELETE':
            handleDeleteRequest($clientModel);
            break;
            
        default:
            throw new Exception('サポートされていないHTTPメソッドです');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => 'APIエラーが発生しました',
            'details' => $e->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * GET リクエスト処理
 */
function handleGetRequest($clientModel) {
    // 個別クライアント取得
    if (isset($_GET['id'])) {
        $clientId = (int)$_GET['id'];
        $client = $clientModel->find($clientId);
        
        if (!$client) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => ['message' => 'クライアントが見つかりません']
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // パフォーマンスデータも取得
        $dailyAdDataModel = new DailyAdData();
        $currentMonth = date('Y-m');
        $performance = $dailyAdDataModel->getClientMonthlyData($clientId, $currentMonth);
        
        $client['performance'] = [
            'total_cost' => (int)($performance['total_cost'] ?? 0),
            'total_impressions' => (int)($performance['total_impressions'] ?? 0),
            'total_clicks' => (int)($performance['total_clicks'] ?? 0),
            'total_conversions' => (int)($performance['total_conversions'] ?? 0)
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $client
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // クライアント一覧取得
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $filters = [];
    if ($search) {
        $filters['search'] = $search;
    }
    
    if (isset($_GET['active'])) {
        $filters['is_active'] = $_GET['active'] === '1' ? 1 : 0;
    }
    
    $result = $clientModel->paginate($page, $limit, $filters);
    
    // 各クライアントのパフォーマンスデータを追加
    $dailyAdDataModel = new DailyAdData();
    $currentMonth = date('Y-m');
    
    foreach ($result['data'] as &$client) {
        $performance = $dailyAdDataModel->getClientMonthlyData($client['id'], $currentMonth);
        $client['performance'] = [
            'total_cost' => (int)($performance['total_cost'] ?? 0),
            'total_impressions' => (int)($performance['total_impressions'] ?? 0),
            'total_clicks' => (int)($performance['total_clicks'] ?? 0),
            'total_conversions' => (int)($performance['total_conversions'] ?? 0)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result['data'],
        'pagination' => [
            'current_page' => $result['current_page'],
            'per_page' => $result['per_page'],
            'total' => $result['total'],
            'last_page' => $result['last_page']
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * POST リクエスト処理（新規作成）
 */
function handlePostRequest($clientModel) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => ['message' => '無効なJSONデータです']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // バリデーション
    $errors = validateClientData($input);
    if ($errors) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => 'バリデーションエラー',
                'details' => $errors
            ]
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 新規作成
    $clientId = $clientModel->create($input);
    $newClient = $clientModel->find($clientId);
    
    echo json_encode([
        'success' => true,
        'message' => 'クライアントが正常に作成されました',
        'data' => $newClient
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * PUT リクエスト処理（更新）
 */
function handlePutRequest($clientModel) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => ['message' => 'クライアントIDが必要です']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $clientId = (int)$_GET['id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => ['message' => '無効なJSONデータです']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 存在確認
    $existingClient = $clientModel->find($clientId);
    if (!$existingClient) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => ['message' => 'クライアントが見つかりません']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // バリデーション
    $errors = validateClientData($input, $clientId);
    if ($errors) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => 'バリデーションエラー',
                'details' => $errors
            ]
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 更新
    $clientModel->update($clientId, $input);
    $updatedClient = $clientModel->find($clientId);
    
    echo json_encode([
        'success' => true,
        'message' => 'クライアント情報が正常に更新されました',
        'data' => $updatedClient
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * DELETE リクエスト処理
 */
function handleDeleteRequest($clientModel) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => ['message' => 'クライアントIDが必要です']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $clientId = (int)$_GET['id'];
    
    // 存在確認
    $existingClient = $clientModel->find($clientId);
    if (!$existingClient) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => ['message' => 'クライアントが見つかりません']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 論理削除（is_active = 0）
    $clientModel->update($clientId, ['is_active' => 0]);
    
    echo json_encode([
        'success' => true,
        'message' => 'クライアントが正常に削除されました'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * クライアントデータバリデーション
 */
function validateClientData($data, $clientId = null) {
    $errors = [];
    
    // 会社名
    if (empty($data['company_name'])) {
        $errors[] = '会社名は必須です';
    } elseif (strlen($data['company_name']) > 255) {
        $errors[] = '会社名は255文字以内で入力してください';
    }
    
    // 担当者名
    if (empty($data['contact_name'])) {
        $errors[] = '担当者名は必須です';
    } elseif (strlen($data['contact_name']) > 100) {
        $errors[] = '担当者名は100文字以内で入力してください';
    }
    
    // メールアドレス
    if (empty($data['email'])) {
        $errors[] = 'メールアドレスは必須です';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = '有効なメールアドレスを入力してください';
    }
    
    // 電話番号
    if (!empty($data['phone']) && strlen($data['phone']) > 20) {
        $errors[] = '電話番号は20文字以内で入力してください';
    }
    
    // 請求日
    if (isset($data['billing_day'])) {
        $billingDay = (int)$data['billing_day'];
        if ($billingDay < 1 || $billingDay > 31) {
            $errors[] = '請求日は1-31の範囲で入力してください';
        }
    }
    
    // 支払条件
    if (isset($data['payment_terms'])) {
        $paymentTerms = (int)$data['payment_terms'];
        if ($paymentTerms < 0 || $paymentTerms > 365) {
            $errors[] = '支払条件は0-365日の範囲で入力してください';
        }
    }
    
    return $errors;
}
?>