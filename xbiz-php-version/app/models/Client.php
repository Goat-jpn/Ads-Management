<?php

namespace App\Models;

class Client extends BaseModel
{
    protected string $table = 'clients';
    protected array $fillable = [
        'company_name',
        'contact_name', 
        'email',
        'phone',
        'address',
        'contract_start_date',
        'contract_end_date',
        'billing_day',
        'payment_terms',
        'is_active',
        'notes'
    ];

    protected array $casts = [
        'billing_day' => 'int',
        'payment_terms' => 'int',
        'is_active' => 'bool',
        'contract_start_date' => 'datetime',
        'contract_end_date' => 'datetime'
    ];

    /**
     * クライアントのアクティブな一覧を取得
     */
    public function getActiveClients(): array
    {
        return $this->findAllBy(['is_active' => true], ['company_name' => 'ASC']);
    }

    /**
     * クライアントの広告アカウントを取得
     */
    public function getAdAccounts(int $clientId): array
    {
        $adAccountModel = new AdAccount();
        return $adAccountModel->findAllBy(['client_id' => $clientId, 'is_active' => true]);
    }

    /**
     * クライアントの手数料設定を取得
     */
    public function getFeeSettings(int $clientId): array
    {
        $feeSettingModel = new FeeSetting();
        return $feeSettingModel->getActiveSettingsForClient($clientId);
    }

    /**
     * クライアントの月次集計データを取得
     */
    public function getMonthlySummaries(int $clientId, string $yearMonth = null): array
    {
        $monthlySummaryModel = new MonthlySummary();
        
        $conditions = ['client_id' => $clientId];
        if ($yearMonth) {
            $conditions['year_month'] = $yearMonth;
        }

        return $monthlySummaryModel->findAllBy($conditions, ['year_month' => 'DESC']);
    }

    /**
     * クライアントの請求書一覧を取得
     */
    public function getInvoices(int $clientId, int $limit = null): array
    {
        $invoiceModel = new Invoice();
        return $invoiceModel->findAllBy(['client_id' => $clientId], ['created_at' => 'DESC'], $limit);
    }

    /**
     * クライアントの現在の月次パフォーマンスを取得
     */
    public function getCurrentMonthPerformance(int $clientId): array
    {
        $currentMonth = date('Y-m');
        
        $sql = "SELECT 
                    SUM(ms.total_cost) as total_cost,
                    SUM(ms.total_reported_cost) as total_reported_cost,
                    SUM(ms.total_impressions) as total_impressions,
                    SUM(ms.total_clicks) as total_clicks,
                    SUM(ms.total_conversions) as total_conversions,
                    AVG(ms.average_ctr) as average_ctr,
                    AVG(ms.average_cpc) as average_cpc,
                    AVG(ms.average_cpa) as average_cpa,
                    SUM(ms.calculated_fee) as total_fee
                FROM monthly_summaries ms
                WHERE ms.client_id = ? AND ms.year_month = ?";
        
        $result = $this->query($sql, [$clientId, $currentMonth]);
        
        return $result[0] ?? [
            'total_cost' => 0,
            'total_reported_cost' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'average_ctr' => 0,
            'average_cpc' => 0,
            'average_cpa' => 0,
            'total_fee' => 0
        ];
    }

    /**
     * クライアントの日次データを取得（期間指定）
     */
    public function getDailyDataForPeriod(int $clientId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    dad.date_value,
                    aa.platform,
                    SUM(dad.impressions) as impressions,
                    SUM(dad.clicks) as clicks,
                    SUM(dad.conversions) as conversions,
                    SUM(dad.cost) as cost,
                    SUM(dad.reported_cost) as reported_cost,
                    AVG(dad.ctr) as ctr,
                    AVG(dad.cpc) as cpc,
                    AVG(dad.cpa) as cpa
                FROM daily_ad_data dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                WHERE aa.client_id = ? 
                    AND dad.date_value BETWEEN ? AND ?
                    AND aa.is_active = 1
                GROUP BY dad.date_value, aa.platform
                ORDER BY dad.date_value DESC, aa.platform";
        
        return $this->query($sql, [$clientId, $startDate, $endDate]);
    }

    /**
     * クライアントのプラットフォーム別パフォーマンスを取得
     */
    public function getPlatformPerformance(int $clientId, string $yearMonth = null): array
    {
        $yearMonth = $yearMonth ?: date('Y-m');
        
        $sql = "SELECT 
                    aa.platform,
                    SUM(ms.total_cost) as total_cost,
                    SUM(ms.total_reported_cost) as total_reported_cost,
                    SUM(ms.total_impressions) as total_impressions,
                    SUM(ms.total_clicks) as total_clicks,
                    SUM(ms.total_conversions) as total_conversions,
                    AVG(ms.average_ctr) as average_ctr,
                    AVG(ms.average_cpc) as average_cpc,
                    AVG(ms.average_cpa) as average_cpa,
                    SUM(ms.calculated_fee) as calculated_fee
                FROM monthly_summaries ms
                JOIN ad_accounts aa ON ms.ad_account_id = aa.id
                WHERE ms.client_id = ? AND ms.year_month = ?
                GROUP BY aa.platform
                ORDER BY total_cost DESC";
        
        return $this->query($sql, [$clientId, $yearMonth]);
    }

    /**
     * 契約期間のバリデーション
     */
    public function validateContractPeriod(string $startDate, string $endDate = null): bool
    {
        $start = new \DateTime($startDate);
        
        if ($endDate) {
            $end = new \DateTime($endDate);
            return $end > $start;
        }
        
        return true;
    }

    /**
     * 請求日の妥当性チェック
     */
    public function validateBillingDay(int $billingDay): bool
    {
        return $billingDay >= 1 && $billingDay <= 31;
    }

    /**
     * 支払い条件の妥当性チェック
     */
    public function validatePaymentTerms(int $paymentTerms): bool
    {
        return $paymentTerms >= 1 && $paymentTerms <= 365;
    }

    /**
     * クライアントのステータス更新
     */
    public function updateStatus(int $clientId, bool $isActive): ?array
    {
        return $this->update($clientId, ['is_active' => $isActive]);
    }

    /**
     * 契約終了間近のクライアントを取得
     */
    public function getExpiringContracts(int $daysAhead = 30): array
    {
        $targetDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE contract_end_date IS NOT NULL 
                    AND contract_end_date <= ? 
                    AND is_active = 1
                ORDER BY contract_end_date ASC";
        
        return $this->processResults($this->query($sql, [$targetDate]));
    }

    /**
     * 今月の請求対象クライアントを取得
     */
    public function getBillingClients(): array
    {
        $today = date('j'); // 今日の日付
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE billing_day = ? 
                    AND is_active = 1
                ORDER BY company_name ASC";
        
        return $this->processResults($this->query($sql, [$today]));
    }
}