<?php

namespace App\Models;

class DailyAdData extends BaseModel
{
    protected string $table = 'daily_ad_data';
    protected array $fillable = [
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

    protected array $casts = [
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
    public function upsertData(array $dataList): int
    {
        if (empty($dataList)) {
            return 0;
        }

        $insertedCount = 0;

        $this->transaction(function() use ($dataList, &$insertedCount) {
            foreach ($dataList as $data) {
                $existing = $this->findBy([
                    'ad_account_id' => $data['ad_account_id'],
                    'date_value' => $data['date_value']
                ]);

                if ($existing) {
                    $this->update($existing['id'], $data);
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
    public function getAccountSummary(int $accountId, string $startDate, string $endDate): array
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
        
        $result = $this->query($sql, [$accountId, $startDate, $endDate]);
        
        return $result[0] ?? [];
    }

    /**
     * 日別トレンドデータを取得
     */
    public function getDailyTrend(int $accountId, int $days = 30): array
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
        
        return $this->query($sql, [$accountId, $startDate]);
    }

    /**
     * クライアント別の日別集計データを取得
     */
    public function getClientDailySummary(int $clientId, string $startDate, string $endDate): array
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
        
        return $this->query($sql, [$clientId, $startDate, $endDate]);
    }

    /**
     * プラットフォーム別パフォーマンス比較
     */
    public function getPlatformComparison(int $clientId, string $startDate, string $endDate): array
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
        
        return $this->query($sql, [$clientId, $startDate, $endDate]);
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
            if ($markup['markup_type'] === 'percentage') {
                $reportedCost += ($actualCost * $markup['markup_value'] / 100);
            } elseif ($markup['markup_type'] === 'fixed') {
                $reportedCost += $markup['markup_value'];
            }
        }
        
        return $reportedCost;
    }

    /**
     * 同期ステータスの更新
     */
    public function updateSyncStatus(int $accountId, string $date, string $status): void
    {
        $sql = "UPDATE {$this->table} 
                SET sync_status = ?, updated_at = NOW()
                WHERE ad_account_id = ? AND date_value = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $accountId, $date]);
    }

    /**
     * 同期失敗データの取得
     */
    public function getFailedSyncData(int $limit = 100): array
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
        
        return $this->query($sql, [$limit]);
    }

    /**
     * データの整合性チェック
     */
    public function validateDataConsistency(int $accountId, string $date): array
    {
        $data = $this->findBy([
            'ad_account_id' => $accountId,
            'date_value' => $date
        ]);

        if (!$data) {
            return ['valid' => false, 'errors' => ['データが存在しません']];
        }

        $errors = [];

        // 基本的な整合性チェック
        if ($data['impressions'] < $data['clicks']) {
            $errors[] = 'インプレッション数がクリック数より少ない';
        }

        if ($data['clicks'] < $data['conversions']) {
            $errors[] = 'クリック数がコンバージョン数より少ない';
        }

        if ($data['cost'] < 0) {
            $errors[] = '費用が負の値';
        }

        if ($data['ctr'] > 100) {
            $errors[] = 'CTRが100%を超えている';
        }

        // CPAの妥当性チェック
        if ($data['conversions'] > 0) {
            $calculatedCpa = $data['cost'] / $data['conversions'];
            $diff = abs($calculatedCpa - $data['cpa']);
            if ($diff > 0.01) { // 誤差1円以内
                $errors[] = 'CPAの計算値が一致しない';
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
    public function detectAnomalies(int $accountId, int $daysBack = 30): array
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
        
        $stats = $this->query($sql, [$accountId, $startDate]);
        
        if (empty($stats[0])) {
            return [];
        }
        
        $stat = $stats[0];
        
        // 異常値の閾値（平均 ± 2σ）
        $costThresholdHigh = $stat['avg_cost'] + (2 * $stat['stddev_cost']);
        $costThresholdLow = max(0, $stat['avg_cost'] - (2 * $stat['stddev_cost']));
        
        $ctrThresholdHigh = $stat['avg_ctr'] + (2 * $stat['stddev_ctr']);
        $ctrThresholdLow = max(0, $stat['avg_ctr'] - (2 * $stat['stddev_ctr']));
        
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
    public function createMonthlySummary(int $accountId, string $yearMonth): array
    {
        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate)); // 月末日
        
        $summary = $this->getAccountSummary($accountId, $startDate, $endDate);
        
        if (empty($summary['days_count'])) {
            return [];
        }

        // アカウント情報を取得
        $adAccountModel = new AdAccount();
        $account = $adAccountModel->find($accountId);
        
        if (!$account) {
            return [];
        }

        return [
            'client_id' => $account['client_id'],
            'ad_account_id' => $accountId,
            'year_month' => $yearMonth,
            'total_cost' => $summary['total_cost'] ?? 0,
            'total_reported_cost' => $summary['total_reported_cost'] ?? 0,
            'total_impressions' => $summary['total_impressions'] ?? 0,
            'total_clicks' => $summary['total_clicks'] ?? 0,
            'total_conversions' => $summary['total_conversions'] ?? 0,
            'average_ctr' => $summary['average_ctr'] ?? 0,
            'average_cpc' => $summary['average_cpc'] ?? 0,
            'average_cpa' => $summary['average_cpa'] ?? 0,
            'average_conversion_rate' => $summary['average_conversion_rate'] ?? 0,
        ];
    }

    /**
     * 古いデータの削除
     */
    public function deleteOldData(int $retentionDays = 730): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$retentionDays} days"));
        
        $sql = "DELETE FROM {$this->table} WHERE date_value < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cutoffDate]);
        
        return $stmt->rowCount();
    }
}