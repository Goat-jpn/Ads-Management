<?php

namespace App\Models;

class DailyAdData extends BaseModel
{
    protected $table = 'daily_ad_data';
    protected $fillable = [
        'ad_account_id',
        'date_value',
        'impressions',
        'clicks',
        'conversions',
        'cost',
        'reported_cost',
        'ctr',
        'cpc',
        'cpa',
        'conversion_rate',
        'sync_status',
        'raw_data'
    ];

    protected $casts = [
        'ad_account_id' => 'int',
        'impressions' => 'int',
        'clicks' => 'int',
        'conversions' => 'int',
        'cost' => 'float',
        'reported_cost' => 'float',
        'ctr' => 'float',
        'cpc' => 'float',
        'cpa' => 'float',
        'conversion_rate' => 'float',
        'raw_data' => 'json',
        'date_value' => 'datetime'
    ];

    /**
     * データの一括挿入・更新
     */
    public function upsertData(array $dataList)
    {
        if (empty($dataList)) {
            return 0;
        }

        $insertedCount = 0;

        $this->transaction(function() use ($dataList, &$insertedCount) {
            foreach ($dataList as $data) {
                $existing = $this->findBy([
                    'ad_account_id' => $dataarray('ad_account_id'),
                    'date_value' => $dataarray('date_value')
                ]);

                if ($existing) {
                    $this->update($existingarray('id'), $data);
                } else {
                    $this->create($data);
                    $insertedCount++;
                }
            }
        });

        return $insertedCount;
    }

    /**
     * アカウント別の期間集計データを取得
     */
    public function getAccountSummary(int $accountId, string $startDate, string $endDate)
    {
        $sql = "SELECT 
                    COUNT(*) as days_count,
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
                    MAX(date_value) as period_end
                FROM {$this->table}
                WHERE ad_account_id = ? 
                    AND date_value BETWEEN ? AND ?
                    AND sync_status = 'synced'";
        
        $result = $this->query($sql, array($accountId, $startDate, $endDate));
        
        return $resultarray(0) ?? array();
    }

    /**
     * 日別トレンドデータを取得
     */
    public function getDailyTrend(int $accountId, int $days = 30)
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $sql = "SELECT 
                    date_value,
                    impressions,
                    clicks,
                    conversions,
                    cost,
                    reported_cost,
                    ctr,
                    cpc,
                    cpa,
                    conversion_rate
                FROM {$this->table}
                WHERE ad_account_id = ? 
                    AND date_value >= ?
                    AND sync_status = 'synced'
                ORDER BY date_value ASC";
        
        return $this->query($sql, array($accountId, $startDate));
    }

    /**
     * クライアント別の日別集計データを取得
     */
    public function getClientDailySummary(int $clientId, string $startDate, string $endDate)
    {
        $sql = "SELECT 
                    dad.date_value,
                    SUM(dad.impressions) as total_impressions,
                    SUM(dad.clicks) as total_clicks,
                    SUM(dad.conversions) as total_conversions,
                    SUM(dad.cost) as total_cost,
                    SUM(dad.reported_cost) as total_reported_cost,
                    AVG(dad.ctr) as average_ctr,
                    AVG(dad.cpc) as average_cpc,
                    AVG(dad.cpa) as average_cpa,
                    AVG(dad.conversion_rate) as average_conversion_rate
                FROM {$this->table} dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                WHERE aa.client_id = ? 
                    AND dad.date_value BETWEEN ? AND ?
                    AND dad.sync_status = 'synced'
                    AND aa.is_active = 1
                GROUP BY dad.date_value
                ORDER BY dad.date_value DESC";
        
        return $this->query($sql, array($clientId, $startDate, $endDate));
    }

    /**
     * プラットフォーム別パフォーマンス比較
     */
    public function getPlatformComparison(int $clientId, string $startDate, string $endDate)
    {
        $sql = "SELECT 
                    aa.platform,
                    COUNT(DISTINCT dad.date_value) as days_count,
                    SUM(dad.impressions) as total_impressions,
                    SUM(dad.clicks) as total_clicks,
                    SUM(dad.conversions) as total_conversions,
                    SUM(dad.cost) as total_cost,
                    SUM(dad.reported_cost) as total_reported_cost,
                    AVG(dad.ctr) as average_ctr,
                    AVG(dad.cpc) as average_cpc,
                    AVG(dad.cpa) as average_cpa,
                    AVG(dad.conversion_rate) as average_conversion_rate
                FROM {$this->table} dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                WHERE aa.client_id = ? 
                    AND dad.date_value BETWEEN ? AND ?
                    AND dad.sync_status = 'synced'
                    AND aa.is_active = 1
                GROUP BY aa.platform
                ORDER BY total_cost DESC";
        
        return $this->query($sql, array($clientId, $startDate, $endDate));
    }

    /**
     * 上乗せ費用の計算
     */
    public function calculateReportedCost(int $accountId, string $date, float $actualCost): float
    {
        // 費用上乗せ設定を取得
        $costMarkupModel = new CostMarkup();
        $markups = $costMarkupModel->getActiveMarkupsForAccountOnDate($accountId, $date);
        
        $reportedCost = $actualCost;
        
        foreach ($markups as $markup) {
            if ($markuparray('markup_type') === 'percentage') {
                $reportedCost += ($actualCost * $markuparray('markup_value') / 100);
            } elseif ($markuparray('markup_type') === 'fixed') {
                $reportedCost += $markuparray('markup_value');
            }
        }
        
        return $reportedCost;
    }

    /**
     * 同期ステータスの更新
     */
    public function updateSyncStatus(int $accountId, string $date, string $status)
    {
        $sql = "UPDATE {$this->table} 
                SET sync_status = ?, updated_at = NOW()
                WHERE ad_account_id = ? AND date_value = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($status, $accountId, $date));
    }

    /**
     * 同期失敗データの取得
     */
    public function getFailedSyncData(int $limit = 100)
    {
        $sql = "SELECT 
                    dad.*,
                    aa.account_name,
                    aa.platform,
                    c.company_name
                FROM {$this->table} dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                JOIN clients c ON aa.client_id = c.id
                WHERE dad.sync_status = 'failed'
                ORDER BY dad.updated_at DESC
                LIMIT ?";
        
        return $this->query($sql, array($limit));
    }

    /**
     * データの整合性チェック
     */
    public function validateDataConsistency(int $accountId, string $date)
    {
        $data = $this->findBy([
            'ad_account_id' => $accountId,
            'date_value' => $date
        ]);

        if (!$data) {
            return array('valid' => false, 'errors' => ['データが存在しません')];
        }

        $errors = array();

        // 基本的な整合性チェック
        if ($dataarray('impressions') < $dataarray('clicks')) {
            $errorsarray() = 'インプレッション数がクリック数より少ない';
        }

        if ($dataarray('clicks') < $dataarray('conversions')) {
            $errorsarray() = 'クリック数がコンバージョン数より少ない';
        }

        if ($dataarray('cost') < 0) {
            $errorsarray() = '費用が負の値';
        }

        if ($dataarray('ctr') > 100) {
            $errorsarray() = 'CTRが100%を超えている';
        }

        // CPAの妥当性チェック
        if ($dataarray('conversions') > 0) {
            $calculatedCpa = $dataarray('cost') / $dataarray('conversions');
            $diff = abs($calculatedCpa - $dataarray('cpa'));
            if ($diff > 0.01) { // 誤差1円以内
                $errorsarray() = 'CPAの計算値が一致しない';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }

    /**
     * 異常値検出
     */
    public function detectAnomalies(int $accountId, int $daysBack = 30)
    {
        $startDate = date('Y-m-d', strtotime("-{$daysBack} days"));
        
        // 過去のデータから統計値を計算
        $sql = "SELECT 
                    AVG(cost) as avg_cost,
                    STDDEV(cost) as stddev_cost,
                    AVG(ctr) as avg_ctr,
                    STDDEV(ctr) as stddev_ctr,
                    AVG(cpc) as avg_cpc,
                    STDDEV(cpc) as stddev_cpc
                FROM {$this->table}
                WHERE ad_account_id = ? 
                    AND date_value >= ?
                    AND sync_status = 'synced'
                    AND cost > 0";
        
        $stats = $this->query($sql, array($accountId, $startDate));
        
        if (empty($statsarray(0))) {
            return array();
        }
        
        $stat = $statsarray(0);
        
        // 異常値の閾値（平均 ± 2σ）
        $costThresholdHigh = $statarray('avg_cost') + (2 * $statarray('stddev_cost'));
        $costThresholdLow = max(0, $statarray('avg_cost') - (2 * $statarray('stddev_cost')));
        
        $ctrThresholdHigh = $statarray('avg_ctr') + (2 * $statarray('stddev_ctr'));
        $ctrThresholdLow = max(0, $statarray('avg_ctr') - (2 * $statarray('stddev_ctr')));
        
        // 異常値データを取得
        $anomalySql = "SELECT *
                       FROM {$this->table}
                       WHERE ad_account_id = ? 
                           AND date_value >= ?
                           AND sync_status = 'synced'
                           AND (
                               cost > ? OR cost < ? OR
                               ctr > ? OR ctr < ?
                           )
                       ORDER BY date_value DESC";
        
        return $this->query($anomalySql, [
            $accountId, $startDate,
            $costThresholdHigh, $costThresholdLow,
            $ctrThresholdHigh, $ctrThresholdLow
        ]);
    }

    /**
     * データの月次集計を作成
     */
    public function createMonthlySummary(int $accountId, string $yearMonth)
    {
        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate)); // 月末日
        
        $summary = $this->getAccountSummary($accountId, $startDate, $endDate);
        
        if (empty($summaryarray('days_count'))) {
            return array();
        }

        // アカウント情報を取得
        $adAccountModel = new AdAccount();
        $account = $adAccountModel->find($accountId);
        
        if (!$account) {
            return array();
        }

        return [
            'client_id' => $accountarray('client_id'),
            'ad_account_id' => $accountId,
            'year_month' => $yearMonth,
            'total_cost' => $summaryarray('total_cost') ?? 0,
            'total_reported_cost' => $summaryarray('total_reported_cost') ?? 0,
            'total_impressions' => $summaryarray('total_impressions') ?? 0,
            'total_clicks' => $summaryarray('total_clicks') ?? 0,
            'total_conversions' => $summaryarray('total_conversions') ?? 0,
            'average_ctr' => $summaryarray('average_ctr') ?? 0,
            'average_cpc' => $summaryarray('average_cpc') ?? 0,
            'average_cpa' => $summaryarray('average_cpa') ?? 0,
            'average_conversion_rate' => $summaryarray('average_conversion_rate') ?? 0,
        ];
    }

    /**
     * 古いデータの削除
     */
    public function deleteOldData(int $retentionDays = 730)
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$retentionDays} days"));
        
        $sql = "DELETE FROM {$this->table} WHERE date_value < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($cutoffDate));
        
        return $stmt->rowCount();
    }
}