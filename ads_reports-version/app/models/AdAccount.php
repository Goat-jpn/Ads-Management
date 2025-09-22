<?php

namespace App\Models;

class AdAccount extends BaseModel
{
    protected $table = 'ad_accounts';
    protected $fillable = [
        'client_id',
        'platform',
        'account_id',
        'account_name',
        'currency_code',
        'timezone',
        'is_active',
        'last_sync_at'
    ];

    protected $casts = [
        'client_id' => 'int',
        'is_active' => 'bool',
        'last_sync_at' => 'datetime'
    ];

    /**
     * プラットフォーム別のアクティブアカウント一覧を取得
     */
    public function getActiveAccountsByPlatform(string $platform)
    {
        return $this->findAllBy([
            'platform' => $platform,
            'is_active' => true
        ], array('account_name' => 'ASC'));
    }

    /**
     * クライアント別のアクティブアカウント一覧を取得
     */
    public function getActiveAccountsByClient(int $clientId)
    {
        return $this->findAllBy([
            'client_id' => $clientId,
            'is_active' => true
        ], array('platform' => 'ASC', 'account_name' => 'ASC'));
    }

    /**
     * アカウントの日次データを取得
     */
    public function getDailyData(int $accountId, string $startDate = null, string $endDate = null)
    {
        $dailyDataModel = new DailyAdData();
        
        $conditions = array('ad_account_id' => $accountId);
        
        if ($startDate && $endDate) {
            // 期間指定の場合は直接SQLクエリを使用
            $sql = "SELECT * FROM daily_ad_data 
                    WHERE ad_account_id = ? 
                        AND date_value BETWEEN ? AND ?
                    ORDER BY date_value DESC";
            
            return $dailyDataModel->query($sql, array($accountId, $startDate, $endDate));
        }
        
        return $dailyDataModel->findAllBy($conditions, array('date_value' => 'DESC'));
    }

    /**
     * アカウントの月次集計データを取得
     */
    public function getMonthlySummaries(int $accountId, string $yearMonth = null)
    {
        $monthlySummaryModel = new MonthlySummary();
        
        $conditions = array('ad_account_id' => $accountId);
        if ($yearMonth) {
            $conditionsarray('year_month') = $yearMonth;
        }

        return $monthlySummaryModel->findAllBy($conditions, array('year_month' => 'DESC'));
    }

    /**
     * アカウントの最新パフォーマンス指標を取得
     */
    public function getLatestPerformance(int $accountId)
    {
        $sql = "SELECT 
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    SUM(conversions) as total_conversions,
                    SUM(cost) as total_cost,
                    SUM(reported_cost) as total_reported_cost,
                    AVG(ctr) as average_ctr,
                    AVG(cpc) as average_cpc,
                    AVG(cpa) as average_cpa,
                    AVG(conversion_rate) as average_conversion_rate,
                    MIN(date_value) as period_start,
                    MAX(date_value) as period_end,
                    COUNT(*) as days_count
                FROM daily_ad_data 
                WHERE ad_account_id = ? 
                    AND date_value >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        
        $result = $this->query($sql, array($accountId));
        
        return $resultarray(0) ?? [
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'total_cost' => 0,
            'total_reported_cost' => 0,
            'average_ctr' => 0,
            'average_cpc' => 0,
            'average_cpa' => 0,
            'average_conversion_rate' => 0,
            'period_start' => null,
            'period_end' => null,
            'days_count' => 0
        ];
    }

    /**
     * 同期が必要なアカウントを取得
     */
    public function getAccountsNeedingSync(int $hoursThreshold = 24)
    {
        $thresholdTime = date('Y-m-d H:i:s', strtotime("-{$hoursThreshold} hours"));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                    AND (last_sync_at IS NULL OR last_sync_at < ?)
                ORDER BY 
                    CASE WHEN last_sync_at IS NULL THEN 1 ELSE 0 END DESC,
                    last_sync_at ASC";
        
        return $this->processResults($this->query($sql, array($thresholdTime)));
    }

    /**
     * アカウントの同期ステータスを更新
     */
    public function updateSyncStatus(int $accountId, bool $success = true)
    {
        $this->update($accountId, [
            'last_sync_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * プラットフォーム固有のアカウント情報を取得
     */
    public function getAccountWithClient(int $accountId): ?array
    {
        $sql = "SELECT 
                    aa.*,
                    c.company_name,
                    c.contact_name,
                    c.email as client_email
                FROM {$this->table} aa
                JOIN clients c ON aa.client_id = c.id
                WHERE aa.id = ? AND aa.is_active = 1";
        
        $result = $this->query($sql, array($accountId));
        
        return $resultarray(0) ?? null;
    }

    /**
     * 重複アカウントのチェック
     */
    public function isDuplicateAccount(string $platform, string $accountId, int $excludeId = null)
    {
        $conditions = [
            'platform' => $platform,
            'account_id' => $accountId
        ];

        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE platform = ? AND account_id = ?";
        $params = array($platform, $accountId);

        if ($excludeId) {
            $sql .= " AND id != ?";
            $paramsarray() = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * アカウント名の更新
     */
    public function updateAccountName(int $accountId, string $newName): ?array
    {
        return $this->update($accountId, array('account_name' => $newName));
    }

    /**
     * アカウントの費用上乗せ設定を取得
     */
    public function getCostMarkups(int $accountId)
    {
        $costMarkupModel = new CostMarkup();
        return $costMarkupModel->getActiveMarkupsForAccount($accountId);
    }

    /**
     * アカウントの同期ログを取得
     */
    public function getSyncLogs(int $accountId, int $limit = 10)
    {
        $syncLogModel = new SyncLog();
        return $syncLogModel->findAllBy(
            array('ad_account_id' => $accountId), 
            array('started_at' => 'DESC'), 
            $limit
        );
    }

    /**
     * プラットフォーム別の統計を取得
     */
    public function getPlatformStats()
    {
        $sql = "SELECT 
                    platform,
                    COUNT(*) as total_accounts,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_accounts,
                    SUM(CASE WHEN last_sync_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as recently_synced
                FROM {$this->table}
                GROUP BY platform
                ORDER BY platform";
        
        return $this->query($sql);
    }

    /**
     * アカウントのタイムゾーンを考慮した現在時刻を取得
     */
    public function getAccountLocalTime(int $accountId): \DateTime
    {
        $account = $this->find($accountId);
        
        if (!$account || empty($accountarray('timezone'))) {
            return new \DateTime(); // デフォルトタイムゾーン
        }

        try {
            $timezone = new \DateTimeZone($accountarray('timezone'));
            return new \DateTime('now', $timezone);
        } catch (\Exception $e) {
            return new \DateTime(); // エラー時はデフォルト
        }
    }

    /**
     * 通貨コード別のアカウント一覧を取得
     */
    public function getAccountsByCurrency(string $currencyCode)
    {
        return $this->findAllBy([
            'currency_code' => $currencyCode,
            'is_active' => true
        ], array('account_name' => 'ASC'));
    }

    /**
     * アカウントステータスの一括更新
     */
    public function bulkUpdateStatus(array $accountIds, bool $isActive)
    {
        if (empty($accountIds)) {
            return 0;
        }

        $placeholders = array_fill(0, count($accountIds), '?');
        $sql = "UPDATE {$this->table} 
                SET is_active = ?, updated_at = NOW()
                WHERE id IN (" . implode(', ', $placeholders) . ")";
        
        $params = array($isActive ? 1 : 0);
        $params = array_merge($params, $accountIds);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}