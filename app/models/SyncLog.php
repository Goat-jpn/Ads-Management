<?php

namespace App\Models;

class SyncLog extends BaseModel
{
    protected string $table = 'sync_logs';
    protected array $fillable = [
        'ad_account_id',
        'sync_type',
        'sync_date',
        'status',
        'records_processed',
        'error_message',
        'execution_time_ms',
        'started_at',
        'completed_at'
    ];

    protected array $casts = [
        'ad_account_id' => 'int',
        'records_processed' => 'int',
        'execution_time_ms' => 'int',
        'sync_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * 同期ログを開始
     */
    public function startSync(int $accountId, string $syncType, string $syncDate): array
    {
        return $this->create([
            'ad_account_id' => $accountId,
            'sync_type' => $syncType,
            'sync_date' => $syncDate,
            'status' => 'started',
            'started_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 同期ログを完了
     */
    public function completeSync(int $logId, int $recordsProcessed, string $errorMessage = null): ?array
    {
        $status = $errorMessage ? 'failed' : 'completed';
        $startedAt = $this->find($logId)['started_at'] ?? null;
        
        $executionTime = null;
        if ($startedAt) {
            $start = new \DateTime($startedAt);
            $end = new \DateTime();
            $executionTime = $end->getTimestamp() - $start->getTimestamp();
            $executionTime = $executionTime * 1000; // ミリ秒に変換
        }

        return $this->update($logId, [
            'status' => $status,
            'records_processed' => $recordsProcessed,
            'error_message' => $errorMessage,
            'execution_time_ms' => $executionTime,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 最近の同期ログを取得
     */
    public function getRecentLogs(int $limit = 50): array
    {
        $sql = "SELECT 
                    sl.*,
                    aa.account_name,
                    aa.platform,
                    c.company_name
                FROM {$this->table} sl
                JOIN ad_accounts aa ON sl.ad_account_id = aa.id
                JOIN clients c ON aa.client_id = c.id
                ORDER BY sl.started_at DESC
                LIMIT ?";
        
        return $this->processResults($this->query($sql, [$limit]));
    }

    /**
     * 失敗した同期ログを取得
     */
    public function getFailedLogs(int $limit = 20): array
    {
        return $this->findAllBy(['status' => 'failed'], ['started_at' => 'DESC'], $limit);
    }

    /**
     * 同期統計を取得
     */
    public function getSyncStats(string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?: date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?: date('Y-m-d');

        $sql = "SELECT 
                    sync_type,
                    COUNT(*) as total_syncs,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_syncs,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_syncs,
                    AVG(execution_time_ms) as avg_execution_time,
                    SUM(records_processed) as total_records_processed
                FROM {$this->table}
                WHERE sync_date BETWEEN ? AND ?
                GROUP BY sync_type";
        
        return $this->query($sql, [$startDate, $endDate]);
    }
}