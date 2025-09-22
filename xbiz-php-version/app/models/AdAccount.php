<?php

namespace App\Models;

class AdAccount extends BaseModel
{
    protected string $table = 'ad_accounts';
    protected array $fillable = [
        'client_id',
        'platform',
        'account_id',
        'account_name',
        'currency_code',
        'timezone',
        'is_active',
        'last_sync_at'
    ];

    protected array $casts = [
        'client_id' => 'int',
        'is_active' => 'bool',
        'last_sync_at' => 'datetime'
    ];

    /**
     * プラットフォーム別のアクティブアカウント一覧を取得
     */
    public function getActiveAccountsByPlatform(string $platform): array
    {
        return $this->findAllBy([
            'platform' => $platform,
            'is_active' => true
        ], ['account_name' => 'ASC']);
    }

    /**
     * クライアント別のアクティブアカウント一覧を取得
     */
    public function getActiveAccountsByClient(int $clientId): array
    {
        return $this->findAllBy([
            'client_id' => $clientId,
            'is_active' => true
        ], ['platform' => 'ASC', 'account_name' => 'ASC']);
    }

    /**
     * アカウントの日次データを取得
     */
    public function getDailyData(int $accountId, string $startDate = null, string $endDate = null): array
    {
        $dailyDataModel = new DailyAdData();
        
        $conditions = ['ad_account_id' => $accountId];
        
        if ($startDate && $endDate) {
            // 期間指定の場合は直接SQLクエリを使用
            $sql = "SELECT * FROM daily_ad_data 
                    WHERE ad_account_id = ? 
                        AND date_value BETWEEN ? AND ?
                    ORDER BY date_value DESC";
            
            return $dailyDataModel->query($sql, [$accountId, $startDate, $endDate]);
        }
        
        return $dailyDataModel->findAllBy($conditions, ['date_value' => 'DESC']);
    }

    /**
     * アカウントの月次集計データを取得
     */
    public function getMonthlySummaries(int $accountId, string $yearMonth = null): array
    {
        $monthlySummaryModel = new MonthlySummary();
        
        $conditions = ['ad_account_id' => $accountId];
        if ($yearMonth) {
            $conditions['year_month'] = $yearMonth;
        }

        return $monthlySummaryModel->findAllBy($conditions, ['year_month' => 'DESC']);
    }

    /**
     * アカウントの最新パフォーマンス指標を取得
     */
    public function getLatestPerformance(int $accountId): array
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
        
        $result = $this->query($sql, [$accountId]);
        
        return $result[0] ?? [
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
    public function getAccountsNeedingSync(int $hoursThreshold = 24): array
    {
        $thresholdTime = date('Y-m-d H:i:s', strtotime("-{$hoursThreshold} hours"));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                    AND (last_sync_at IS NULL OR last_sync_at < ?)
                ORDER BY 
                    CASE WHEN last_sync_at IS NULL THEN 1 ELSE 0 END DESC,
                    last_sync_at ASC";
        
        return $this->processResults($this->query($sql, [$thresholdTime]));
    }

    /**
     * アカウントの同期ステータスを更新
     */
    public function updateSyncStatus(int $accountId, bool $success = true): void
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
        
        $result = $this->query($sql, [$accountId]);
        
        return $result[0] ?? null;
    }

    /**
     * 重複アカウントのチェック
     */
    public function isDuplicateAccount(string $platform, string $accountId, int $excludeId = null): bool
    {
        $conditions = [
            'platform' => $platform,
            'account_id' => $accountId
        ];

        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE platform = ? AND account_id = ?";
        $params = [$platform, $accountId];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
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
        return $this->update($accountId, ['account_name' => $newName]);
    }

    /**
     * アカウントの費用上乗せ設定を取得
     */
    public function getCostMarkups(int $accountId): array
    {
        $costMarkupModel = new CostMarkup();
        return $costMarkupModel->getActiveMarkupsForAccount($accountId);
    }

    /**
     * アカウントの同期ログを取得
     */
    public function getSyncLogs(int $accountId, int $limit = 10): array
    {
        $syncLogModel = new SyncLog();
        return $syncLogModel->findAllBy(
            ['ad_account_id' => $accountId], 
            ['started_at' => 'DESC'], 
            $limit
        );
    }

    /**
     * プラットフォーム別の統計を取得
     */
    public function getPlatformStats(): array
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
        
        if (!$account || empty($account['timezone'])) {
            return new \DateTime(); // デフォルトタイムゾーン
        }

        try {
            $timezone = new \DateTimeZone($account['timezone']);
            return new \DateTime('now', $timezone);
        } catch (\Exception $e) {
            return new \DateTime(); // エラー時はデフォルト
        }
    }

    /**
     * 通貨コード別のアカウント一覧を取得
     */
    public function getAccountsByCurrency(string $currencyCode): array
    {
        return $this->findAllBy([
            'currency_code' => $currencyCode,
            'is_active' => true
        ], ['account_name' => 'ASC']);
    }

    /**
     * アカウントステータスの一括更新
     */
    public function bulkUpdateStatus(array $accountIds, bool $isActive): int
    {
        if (empty($accountIds)) {
            return 0;
        }

        $placeholders = array_fill(0, count($accountIds), '?');
        $sql = "UPDATE {$this->table} 
                SET is_active = ?, updated_at = NOW()
                WHERE id IN (" . implode(', ', $placeholders) . ")";
        
        $params = [$isActive ? 1 : 0];
        $params = array_merge($params, $accountIds);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}