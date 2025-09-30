<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\Campaign;
use App\Models\AdAccount;
use App\Models\DailyStat;
use App\Models\BillingRecord;

class DashboardController
{
    private $clientModel;
    private $campaignModel;
    private $adAccountModel;
    private $dailyStatModel;
    private $billingModel;
    
    public function __construct()
    {
        $this->clientModel = new Client();
        $this->campaignModel = new Campaign();
        $this->adAccountModel = new AdAccount();
        $this->dailyStatModel = new DailyStat();
        $this->billingModel = new BillingRecord();
    }
    
    public function index()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        // Get recent clients
        $recentClients = $this->clientModel->paginate(1, 5, 'created_at', 'DESC')['data'];
        
        // Pass data to view
        extract($stats);
        
        require_once __DIR__ . '/../../views/dashboard.php';
    }
    
    private function getDashboardStats()
    {
        // Get basic counts
        $clientCount = $this->clientModel->count();
        $activeCampaigns = $this->campaignModel->count('*', "status = 'active'");
        
        // Get monthly ad spend (current month)
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        
        $monthlyStats = $this->dailyStatModel->getSummaryStats(
            null, null, $startOfMonth, $endOfMonth
        );
        $monthlyAdSpend = $monthlyStats['total_cost'] ?? 0;
        
        // Get unpaid billing amount
        $unpaidBillings = $this->billingModel->getByStatus('pending');
        $unpaidAmount = array_sum(array_column($unpaidBillings, 'total_amount'));
        
        return [
            'clientCount' => $clientCount,
            'activeCampaigns' => $activeCampaigns,
            'monthlyAdSpend' => $monthlyAdSpend,
            'unpaidAmount' => $unpaidAmount,
            'recentClients' => $this->getRecentClients()
        ];
    }
    
    private function getRecentClients($limit = 5)
    {
        return $this->clientModel->paginate(1, $limit, 'created_at', 'DESC')['data'];
    }
    
    public function getPerformanceData()
    {
        // API endpoint for dashboard charts
        header('Content-Type: application/json');
        
        $days = $_GET['days'] ?? 30;
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');
        
        $performanceData = $this->dailyStatModel->getAggregatedStats(
            null, null, $startDate, $endDate
        );
        
        $platformStats = $this->adAccountModel->getAccountStats();
        
        echo json_encode([
            'performance' => $performanceData,
            'platforms' => $platformStats
        ]);
    }
    
    public function getPlatformData()
    {
        // API endpoint for platform distribution chart
        header('Content-Type: application/json');
        
        $platformStats = $this->adAccountModel->getAccountStats();
        
        // Group by platform and sum active accounts
        $platforms = [];
        foreach ($platformStats as $stat) {
            if ($stat['status'] === 'active') {
                if (!isset($platforms[$stat['platform']])) {
                    $platforms[$stat['platform']] = 0;
                }
                $platforms[$stat['platform']] += $stat['count'];
            }
        }
        
        echo json_encode($platforms);
    }
    
    public function getAlerts()
    {
        // Get system alerts and notifications
        $alerts = [];
        
        // Check for expiring contracts
        $expiringClients = $this->clientModel->getClientsWithUpcomingContracts(30);
        if (count($expiringClients) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => '契約期限切れ間近',
                'message' => count($expiringClients) . '件の契約が30日以内に期限切れです',
                'action' => '/clients?filter=expiring'
            ];
        }
        
        // Check for accounts needing sync
        $needsSyncAccounts = $this->adAccountModel->getAccountsNeedingSync(24);
        if (count($needsSyncAccounts) > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => '同期が必要',
                'message' => count($needsSyncAccounts) . '件のアカウントの同期が必要です',
                'action' => '/sync/accounts'
            ];
        }
        
        // Check for overdue billings
        $overdueBillings = $this->billingModel->getOverdueBillings();
        if (count($overdueBillings) > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => '支払い期限超過',
                'message' => count($overdueBillings) . '件の請求が支払い期限を超過しています',
                'action' => '/billing?filter=overdue'
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($alerts);
    }
}