<?php
/**
 * ダッシュボードデータAPI - PHP版
 * Node.js Express.js APIからの変換
 */

// CORS設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// プリフライトリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// エラー報告設定（本番環境では無効化）
error_reporting(E_ALL);
ini_set('display_errors', 0); // 本番環境では0

// オートロード
require_once '../../bootstrap.php';

try {
    // データベース接続
    $connection = Connection::getInstance();
    
    // モデル初期化
    $clientModel = new Client();
    $adAccountModel = new AdAccount();
    $dailyAdDataModel = new DailyAdData();
    
    // 基本統計データ取得
    $summary = [
        'active_clients' => 0,
        'active_accounts' => 0,
        'total_cost' => 0,
        'total_reported_cost' => 0,
        'total_impressions' => 0,
        'total_clicks' => 0,
        'total_conversions' => 0,
        'average_ctr' => '0.00',
        'average_cpc' => '0.00',
        'average_cpa' => '0.00',
        'cost_growth' => '0.0',
        'impressions_growth' => '0.0',
        'clicks_growth' => '0.0',
        'conversions_growth' => '0.0'
    ];
    
    // クライアント数
    $clients = $clientModel->getAll(['is_active' => 1]);
    $summary['active_clients'] = count($clients);
    
    // 広告アカウント数
    $adAccounts = $adAccountModel->getAll(['is_active' => 1]);
    $summary['active_accounts'] = count($adAccounts);
    
    // 今月のデータ集計
    $currentMonth = date('Y-m');
    $lastMonth = date('Y-m', strtotime('-1 month'));
    
    // 今月の集計データ
    $currentData = $dailyAdDataModel->getMonthlyAggregation($currentMonth);
    if ($currentData) {
        $summary['total_cost'] = (int)$currentData['total_cost'];
        $summary['total_reported_cost'] = (int)($currentData['total_cost'] * 1.04); // 4%マークアップ
        $summary['total_impressions'] = (int)$currentData['total_impressions'];
        $summary['total_clicks'] = (int)$currentData['total_clicks'];
        $summary['total_conversions'] = (int)$currentData['total_conversions'];
        
        // 平均値計算
        if ($summary['total_clicks'] > 0) {
            $summary['average_ctr'] = number_format(($summary['total_clicks'] / $summary['total_impressions']) * 100, 4);
            $summary['average_cpc'] = number_format($summary['total_cost'] / $summary['total_clicks'], 2);
        }
        
        if ($summary['total_conversions'] > 0) {
            $summary['average_cpa'] = number_format($summary['total_cost'] / $summary['total_conversions'], 2);
        }
    }
    
    // 前月比成長率
    $lastData = $dailyAdDataModel->getMonthlyAggregation($lastMonth);
    if ($lastData && $currentData) {
        $summary['cost_growth'] = $lastData['total_cost'] > 0 ? 
            number_format((($currentData['total_cost'] - $lastData['total_cost']) / $lastData['total_cost']) * 100, 1) : '0.0';
        $summary['impressions_growth'] = $lastData['total_impressions'] > 0 ? 
            number_format((($currentData['total_impressions'] - $lastData['total_impressions']) / $lastData['total_impressions']) * 100, 1) : '0.0';
        $summary['clicks_growth'] = $lastData['total_clicks'] > 0 ? 
            number_format((($currentData['total_clicks'] - $lastData['total_clicks']) / $lastData['total_clicks']) * 100, 1) : '0.0';
        $summary['conversions_growth'] = $lastData['total_conversions'] > 0 ? 
            number_format((($currentData['total_conversions'] - $lastData['total_conversions']) / $lastData['total_conversions']) * 100, 1) : '0.0';
    }
    
    // クライアント別パフォーマンス
    $clientPerformance = [];
    foreach ($clients as $client) {
        $clientData = $dailyAdDataModel->getClientMonthlyData($client['id'], $currentMonth);
        
        $performance = [
            'id' => (int)$client['id'],
            'company_name' => $client['company_name'],
            'account_count' => $adAccountModel->getCountByClient($client['id']),
            'total_cost' => (int)($clientData['total_cost'] ?? 0),
            'total_reported_cost' => (int)(($clientData['total_cost'] ?? 0) * 1.04),
            'total_impressions' => (int)($clientData['total_impressions'] ?? 0),
            'total_clicks' => (int)($clientData['total_clicks'] ?? 0),
            'total_conversions' => (int)($clientData['total_conversions'] ?? 0),
            'average_ctr' => '0.0000',
            'average_cpc' => '0.00',
            'average_cpa' => '0.00'
        ];
        
        // 平均値計算
        if ($performance['total_impressions'] > 0 && $performance['total_clicks'] > 0) {
            $performance['average_ctr'] = number_format(($performance['total_clicks'] / $performance['total_impressions']) * 100, 4);
        }
        
        if ($performance['total_clicks'] > 0) {
            $performance['average_cpc'] = number_format($performance['total_cost'] / $performance['total_clicks'], 2);
        }
        
        if ($performance['total_conversions'] > 0) {
            $performance['average_cpa'] = number_format($performance['total_cost'] / $performance['total_conversions'], 2);
        }
        
        $clientPerformance[] = $performance;
    }
    
    // 月次推移データ（過去6ヶ月）
    $monthlyTrends = [];
    for ($i = 5; $i >= 0; $i--) {
        $targetMonth = date('Y-m', strtotime("-{$i} months"));
        $monthData = $dailyAdDataModel->getMonthlyAggregation($targetMonth);
        
        $monthlyTrends[] = [
            'month' => $targetMonth,
            'month_name' => date('n月', strtotime($targetMonth . '-01')),
            'total_cost' => (int)($monthData['total_cost'] ?? 0),
            'total_impressions' => (int)($monthData['total_impressions'] ?? 0),
            'total_clicks' => (int)($monthData['total_clicks'] ?? 0),
            'total_conversions' => (int)($monthData['total_conversions'] ?? 0)
        ];
    }
    
    // レスポンスデータ構築
    $responseData = [
        'success' => true,
        'data' => [
            'summary' => $summary,
            'client_performance' => $clientPerformance,
            'monthly_trends' => $monthlyTrends,
            'last_updated' => date('Y-m-d H:i:s')
        ],
        'meta' => [
            'server_type' => 'xbiz_php',
            'api_version' => '1.0',
            'database' => 'mariadb_10.5'
        ]
    ];
    
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // エラー時のレスポンス
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => 'データ取得エラーが発生しました',
            'details' => $e->getMessage(),
            'code' => $e->getCode()
        ],
        'meta' => [
            'server_type' => 'xbiz_php',
            'error_time' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>