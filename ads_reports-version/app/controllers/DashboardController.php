<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\AdAccount;
use App\Models\DailyAdData;
use App\Models\MonthlySummary;
use App\Models\Invoice;
use App\Models\SyncLog;

class DashboardController extends BaseController
{
    private Client $clientModel;
    private AdAccount $adAccountModel;
    private DailyAdData $dailyAdDataModel;
    private MonthlySummary $monthlySummaryModel;
    private Invoice $invoiceModel;
    private SyncLog $syncLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
        $this->adAccountModel = new AdAccount();
        $this->dailyAdDataModel = new DailyAdData();
        $this->monthlySummaryModel = new MonthlySummary();
        $this->invoiceModel = new Invoice();
        $this->syncLogModel = new SyncLog();
    }

    /**
     * ダッシュボードメイン画面
     */
    public function index()
    {
        $this->handleRequest(function() {
            $this->requireAuth();
            
            if ($this->isAjaxRequest()) {
                $this->getDashboardData();
            } else {
                $this->render('admin/dashboard/index.php', [
                    'title' => 'ダッシュボード'
                ]);
            }
        });
    }

    /**
     * ダッシュボードデータAPI
     */
    public function getDashboardData()
    {
        $dateRange = $this->getDateRangeParams();
        
        // サマリー統計
        $summaryStats = $this->getSummaryStats($dateRange);
        
        // クライアント別パフォーマンス
        $clientPerformance = $this->getClientPerformance($dateRange);
        
        // プラットフォーム別統計
        $platformStats = $this->getPlatformStats($dateRange);
        
        // 日別トレンド
        $dailyTrend = $this->getDailyTrend($dateRange);
        
        // 請求関連統計
        $billingStats = $this->getBillingStats();
        
        // 同期ステータス
        $syncStatus = $this->getSyncStatus();
        
        // アラート情報
        $alerts = $this->getAlerts();

        $this->successResponse([
            'summary' => $summaryStats,
            'client_performance' => $clientPerformance,
            'platform_stats' => $platformStats,
            'daily_trend' => $dailyTrend,
            'billing_stats' => $billingStats,
            'sync_status' => $syncStatus,
            'alerts' => $alerts,
            'date_range' => $dateRange
        ]);
    }

    /**
     * サマリー統計の取得
     */
    private function getSummaryStats(array $dateRange)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT aa.client_id) as active_clients,
                    COUNT(DISTINCT aa.id) as active_accounts,
                    SUM(dad.cost) as total_cost,
                    SUM(dad.reported_cost) as total_reported_cost,
                    SUM(dad.impressions) as total_impressions,
                    SUM(dad.clicks) as total_clicks,
                    SUM(dad.conversions) as total_conversions,
                    AVG(dad.ctr) as average_ctr,
                    AVG(dad.cpc) as average_cpc,
                    AVG(dad.cpa) as average_cpa
                FROM daily_ad_data dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                JOIN clients c ON aa.client_id = c.id
                WHERE dad.date_value BETWEEN ? AND ?
                    AND dad.sync_status = 'synced'
                    AND aa.is_active = 1
                    AND c.is_active = 1";

        $result = $this->dailyAdDataModel->query($sql, [
            $dateRange['start_date'], 
            $dateRange['end_date']
        ]);

        $stats = $result[0] ?? [];
        
        // 前期間との比較
        $prevStart = date('Y-m-d', strtotime($dateRange['start_date'] . ' -' . $this->getDateDiff($dateRange) . ' days'));
        $prevEnd = date('Y-m-d', strtotime($dateRange['end_date'] . ' -' . $this->getDateDiff($dateRange) . ' days'));
        
        $prevResult = $this->dailyAdDataModel->query($sql, [$prevStart, $prevEnd]);
        $prevStats = $prevResult[0] ?? [];

        // 成長率計算
        $stats['cost_growth'] = $this->calculateGrowthRate($stats['total_cost'] ?? 0, $prevStats['total_cost'] ?? 0);
        $stats['impressions_growth'] = $this->calculateGrowthRate($stats['total_impressions'] ?? 0, $prevStats['total_impressions'] ?? 0);
        $stats['clicks_growth'] = $this->calculateGrowthRate($stats['total_clicks'] ?? 0, $prevStats['total_clicks'] ?? 0);
        $stats['conversions_growth'] = $this->calculateGrowthRate($stats['total_conversions'] ?? 0, $prevStats['total_conversions'] ?? 0);

        return $stats;
    }

    /**
     * クライアント別パフォーマンスの取得
     */
    private function getClientPerformance(array $dateRange)
    {
        $sql = "SELECT 
                    c.id,
                    c.company_name,
                    SUM(dad.cost) as total_cost,
                    SUM(dad.reported_cost) as total_reported_cost,
                    SUM(dad.impressions) as total_impressions,
                    SUM(dad.clicks) as total_clicks,
                    SUM(dad.conversions) as total_conversions,
                    AVG(dad.ctr) as average_ctr,
                    AVG(dad.cpc) as average_cpc,
                    AVG(dad.cpa) as average_cpa,
                    COUNT(DISTINCT aa.id) as account_count
                FROM clients c
                JOIN ad_accounts aa ON c.id = aa.client_id
                JOIN daily_ad_data dad ON aa.id = dad.ad_account_id
                WHERE dad.date_value BETWEEN ? AND ?
                    AND dad.sync_status = 'synced'
                    AND aa.is_active = 1
                    AND c.is_active = 1
                GROUP BY c.id, c.company_name
                ORDER BY total_cost DESC
                LIMIT 10";

        return $this->dailyAdDataModel->query($sql, [
            $dateRange['start_date'], 
            $dateRange['end_date']
        ]);
    }

    /**
     * プラットフォーム別統計の取得
     */
    private function getPlatformStats(array $dateRange)
    {
        $sql = "SELECT 
                    aa.platform,
                    COUNT(DISTINCT aa.id) as account_count,
                    SUM(dad.cost) as total_cost,
                    SUM(dad.reported_cost) as total_reported_cost,
                    SUM(dad.impressions) as total_impressions,
                    SUM(dad.clicks) as total_clicks,
                    SUM(dad.conversions) as total_conversions,
                    AVG(dad.ctr) as average_ctr,
                    AVG(dad.cpc) as average_cpc,
                    AVG(dad.cpa) as average_cpa
                FROM ad_accounts aa
                JOIN daily_ad_data dad ON aa.id = dad.ad_account_id
                WHERE dad.date_value BETWEEN ? AND ?
                    AND dad.sync_status = 'synced'
                    AND aa.is_active = 1
                GROUP BY aa.platform
                ORDER BY total_cost DESC";

        return $this->dailyAdDataModel->query($sql, [
            $dateRange['start_date'], 
            $dateRange['end_date']
        ]);
    }

    /**
     * 日別トレンドの取得
     */
    private function getDailyTrend(array $dateRange)
    {
        $sql = "SELECT 
                    dad.date_value,
                    SUM(dad.cost) as daily_cost,
                    SUM(dad.reported_cost) as daily_reported_cost,
                    SUM(dad.impressions) as daily_impressions,
                    SUM(dad.clicks) as daily_clicks,
                    SUM(dad.conversions) as daily_conversions,
                    AVG(dad.ctr) as daily_ctr,
                    AVG(dad.cpc) as daily_cpc,
                    AVG(dad.cpa) as daily_cpa
                FROM daily_ad_data dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                JOIN clients c ON aa.client_id = c.id
                WHERE dad.date_value BETWEEN ? AND ?
                    AND dad.sync_status = 'synced'
                    AND aa.is_active = 1
                    AND c.is_active = 1
                GROUP BY dad.date_value
                ORDER BY dad.date_value ASC";

        return $this->dailyAdDataModel->query($sql, [
            $dateRange['start_date'], 
            $dateRange['end_date']
        ]);
    }

    /**
     * 請求関連統計の取得
     */
    private function getBillingStats()
    {
        $currentMonth = date('Y-m');
        
        // 今月の請求統計
        $monthlyStats = $this->invoiceModel->getMonthlyInvoiceStats($currentMonth);
        
        // 延滞請求書
        $overdueInvoices = $this->invoiceModel->getOverdueInvoices();
        
        // 今月の手数料収入
        $sql = "SELECT SUM(calculated_fee) as total_fees
                FROM monthly_summaries
                WHERE year_month = ? AND is_invoiced = 0";
        
        $feeResult = $this->monthlySummaryModel->query($sql, [$currentMonth]);
        $pendingFees = $feeResult[0]['total_fees'] ?? 0;

        return [
            'monthly_stats' => $monthlyStats,
            'overdue_count' => count($overdueInvoices),
            'overdue_amount' => array_sum(array_column($overdueInvoices, 'total_amount')),
            'pending_fees' => $pendingFees
        ];
    }

    /**
     * 同期ステータスの取得
     */
    private function getSyncStatus()
    {
        // 最近の同期ログ
        $recentLogs = $this->syncLogModel->getRecentLogs(5);
        
        // 同期が必要なアカウント
        $needsSync = $this->adAccountModel->getAccountsNeedingSync(24);
        
        // 同期統計
        $syncStats = $this->syncLogModel->getSyncStats();

        return [
            'recent_logs' => $recentLogs,
            'accounts_needing_sync' => count($needsSync),
            'sync_stats' => $syncStats
        ];
    }

    /**
     * アラート情報の取得
     */
    private function getAlerts()
    {
        $alerts = [];

        // 契約終了間近のクライアント
        $expiringClients = $this->clientModel->getExpiringContracts(30);
        if (!empty($expiringClients)) {
            $alerts[] = [
                'type' => 'warning',
                'title' => '契約終了間近のクライアント',
                'message' => count($expiringClients) . '件のクライアントの契約が30日以内に終了します',
                'count' => count($expiringClients)
            ];
        }

        // 延滞請求書
        $overdueInvoices = $this->invoiceModel->getOverdueInvoices();
        if (!empty($overdueInvoices)) {
            $alerts[] = [
                'type' => 'error',
                'title' => '延滞請求書',
                'message' => count($overdueInvoices) . '件の請求書が支払い期限を過ぎています',
                'count' => count($overdueInvoices)
            ];
        }

        // 同期エラー
        $failedSyncs = $this->syncLogModel->getFailedLogs(1);
        if (!empty($failedSyncs)) {
            $alerts[] = [
                'type' => 'error',
                'title' => '同期エラー',
                'message' => '最近の同期でエラーが発生しています',
                'count' => count($failedSyncs)
            ];
        }

        // 今月請求予定
        $billingClients = $this->clientModel->getBillingClients();
        if (!empty($billingClients)) {
            $alerts[] = [
                'type' => 'info',
                'title' => '今月請求予定',
                'message' => count($billingClients) . '件のクライアントが今月の請求対象です',
                'count' => count($billingClients)
            ];
        }

        return $alerts;
    }

    /**
     * 日付差分を計算
     */
    private function getDateDiff(array $dateRange)
    {
        $start = new \DateTime($dateRange['start_date']);
        $end = new \DateTime($dateRange['end_date']);
        return $start->diff($end)->days + 1;
    }

    /**
     * 成長率を計算
     */
    private function calculateGrowthRate(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * クイック統計API（軽量版）
     */
    public function getQuickStats()
    {
        $this->handleRequest(function() {
            $this->requireAuth();

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            // 昨日の数値
            $sql = "SELECT 
                        SUM(cost) as daily_cost,
                        SUM(impressions) as daily_impressions,
                        SUM(clicks) as daily_clicks,
                        SUM(conversions) as daily_conversions
                    FROM daily_ad_data dad
                    JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                    WHERE dad.date_value = ? AND dad.sync_status = 'synced' AND aa.is_active = 1";

            $result = $this->dailyAdDataModel->query($sql, [$yesterday]);
            $yesterdayStats = $result[0] ?? [];

            // アクティブクライアント数
            $activeClients = $this->clientModel->count(['is_active' => true]);
            
            // アクティブアカウント数
            $activeAccounts = $this->adAccountModel->count(['is_active' => true]);

            $this->successResponse([
                'active_clients' => $activeClients,
                'active_accounts' => $activeAccounts,
                'yesterday_cost' => $yesterdayStats['daily_cost'] ?? 0,
                'yesterday_impressions' => $yesterdayStats['daily_impressions'] ?? 0,
                'yesterday_clicks' => $yesterdayStats['daily_clicks'] ?? 0,
                'yesterday_conversions' => $yesterdayStats['daily_conversions'] ?? 0
            ]);
        });
    }
}