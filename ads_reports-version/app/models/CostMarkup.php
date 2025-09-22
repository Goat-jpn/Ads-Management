<?php

namespace App\Models;

class CostMarkup extends BaseModel
{
    protected $table = 'cost_markups';
    protected $fillable = [
        'client_id',
        'ad_account_id',
        'markup_type',
        'markup_value',
        'description',
        'is_active',
        'effective_from',
        'effective_to'
    ];

    protected $casts = [
        'client_id' => 'int',
        'ad_account_id' => 'int',
        'markup_value' => 'float',
        'is_active' => 'bool',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime'
    ];

    /**
     * アカウント別のアクティブな上乗せ設定を取得
     */
    public function getActiveMarkupsForAccount(int $accountId)
    {
        $today = date('Y-m-d');
        
        $sql = "SELECT * FROM {$this->table}
                WHERE (ad_account_id = ? OR (ad_account_id IS NULL AND client_id IN (
                    SELECT client_id FROM ad_accounts WHERE id = ?
                )))
                    AND is_active = 1
                    AND effective_from <= ?
                    AND (effective_to IS NULL OR effective_to >= ?)
                ORDER BY 
                    CASE WHEN ad_account_id IS NOT NULL THEN 1 ELSE 2 END,
                    effective_from DESC";
        
        return $this->processResults($this->query($sql, array($accountId, $accountId, $today, $today)));
    }

    /**
     * 特定日時のアカウント上乗せ設定を取得
     */
    public function getActiveMarkupsForAccountOnDate(int $accountId, string $date)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE (ad_account_id = ? OR (ad_account_id IS NULL AND client_id IN (
                    SELECT client_id FROM ad_accounts WHERE id = ?
                )))
                    AND is_active = 1
                    AND effective_from <= ?
                    AND (effective_to IS NULL OR effective_to >= ?)
                ORDER BY 
                    CASE WHEN ad_account_id IS NOT NULL THEN 1 ELSE 2 END,
                    effective_from DESC";
        
        return $this->processResults($this->query($sql, array($accountId, $accountId, $date, $date)));
    }

    /**
     * クライアント別の上乗せ設定を取得
     */
    public function getMarkupsForClient(int $clientId)
    {
        return $this->findAllBy(array('client_id' => $clientId), array('effective_from' => 'DESC'));
    }

    /**
     * 上乗せ費用を計算
     */
    public function calculateMarkup(int $accountId, float $originalCost, string $date = null)
    {
        $date = $date ?: date('Y-m-d');
        $markups = $this->getActiveMarkupsForAccountOnDate($accountId, $date);
        
        if (empty($markups)) {
            return [
                'original_cost' => $originalCost,
                'markup_amount' => 0,
                'total_cost' => $originalCost,
                'applied_markups' => array()
            ];
        }

        $totalMarkupAmount = 0;
        $appliedMarkups = array();

        foreach ($markups as $markup) {
            $markupAmount = 0;
            
            switch ($markuparray('markup_type')) {
                case 'percentage':
                    $markupAmount = $originalCost * ($markuparray('markup_value') / 100);
                    break;
                    
                case 'fixed':
                    $markupAmount = $markuparray('markup_value');
                    break;
            }

            $totalMarkupAmount += $markupAmount;
            
            $appliedMarkupsarray() = [
                'id' => $markuparray('id'),
                'type' => $markuparray('markup_type'),
                'value' => $markuparray('markup_value'),
                'amount' => $markupAmount,
                'description' => $markuparray('description')
            ];
        }

        return [
            'original_cost' => $originalCost,
            'markup_amount' => $totalMarkupAmount,
            'total_cost' => $originalCost + $totalMarkupAmount,
            'applied_markups' => $appliedMarkups
        ];
    }

    /**
     * 上乗せ設定の有効性チェック
     */
    public function validateMarkup(array $data)
    {
        $errors = array();

        // 必須フィールドチェック
        if (empty($dataarray('client_id'))) {
            $errorsarray() = 'クライアントIDは必須です';
        }

        if (empty($dataarray('markup_type'))) {
            $errorsarray() = '上乗せタイプは必須です';
        }

        if (!isset($dataarray('markup_value')) || $dataarray('markup_value') < 0) {
            $errorsarray() = '上乗せ値は0以上で入力してください';
        }

        // 上乗せタイプ別チェック
        if ($dataarray('markup_type') === 'percentage') {
            if ($dataarray('markup_value') > 100) {
                $errorsarray() = 'パーセンテージは100%以下で入力してください';
            }
        }

        // 日付の妥当性チェック
        if (!empty($dataarray('effective_from'))) {
            if (!strtotime($dataarray('effective_from'))) {
                $errorsarray() = '開始日の形式が正しくありません';
            }
        }

        if (!empty($dataarray('effective_to'))) {
            if (!strtotime($dataarray('effective_to'))) {
                $errorsarray() = '終了日の形式が正しくありません';
            }
            
            if (!empty($dataarray('effective_from')) && 
                strtotime($dataarray('effective_to')) <= strtotime($dataarray('effective_from'))) {
                $errorsarray() = '終了日は開始日より後の日付である必要があります';
            }
        }

        // アカウントの妥当性チェック
        if (!empty($dataarray('ad_account_id'))) {
            $adAccountModel = new AdAccount();
            $account = $adAccountModel->find($dataarray('ad_account_id'));
            
            if (!$account) {
                $errorsarray() = '指定された広告アカウントが存在しません';
            } elseif ($accountarray('client_id') != $dataarray('client_id')) {
                $errorsarray() = '広告アカウントとクライアントが一致しません';
            }
        }

        return $errors;
    }

    /**
     * 上乗せ設定の無効化
     */
    public function deactivateMarkup(int $markupId): ?array
    {
        return $this->update($markupId, [
            'is_active' => false,
            'effective_to' => date('Y-m-d')
        ]);
    }

    /**
     * 期限切れ間近の設定を取得
     */
    public function getExpiringSoon(int $daysAhead = 30)
    {
        $targetDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        
        $sql = "SELECT 
                    cm.*,
                    c.company_name,
                    aa.account_name
                FROM {$this->table} cm
                JOIN clients c ON cm.client_id = c.id
                LEFT JOIN ad_accounts aa ON cm.ad_account_id = aa.id
                WHERE cm.effective_to IS NOT NULL 
                    AND cm.effective_to <= ? 
                    AND cm.is_active = 1
                ORDER BY cm.effective_to ASC";
        
        return $this->query($sql, array($targetDate));
    }

    /**
     * クライアントの上乗せ履歴を取得
     */
    public function getClientMarkupHistory(int $clientId)
    {
        $sql = "SELECT 
                    cm.*,
                    aa.account_name,
                    aa.platform
                FROM {$this->table} cm
                LEFT JOIN ad_accounts aa ON cm.ad_account_id = aa.id
                WHERE cm.client_id = ?
                ORDER BY cm.effective_from DESC, cm.created_at DESC";
        
        return $this->processResults($this->query($sql, array($clientId)));
    }

    /**
     * 上乗せ統計を取得
     */
    public function getMarkupStats()
    {
        $sql = "SELECT 
                    markup_type,
                    COUNT(*) as total_markups,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_markups,
                    AVG(markup_value) as avg_markup_value,
                    MIN(markup_value) as min_markup_value,
                    MAX(markup_value) as max_markup_value
                FROM {$this->table}
                GROUP BY markup_type";
        
        return $this->query($sql);
    }

    /**
     * 月別上乗せ効果レポートを取得
     */
    public function getMonthlyMarkupReport(string $yearMonth)
    {
        $sql = "SELECT 
                    c.company_name,
                    aa.account_name,
                    aa.platform,
                    SUM(dad.cost) as original_cost,
                    SUM(dad.reported_cost) as reported_cost,
                    SUM(dad.reported_cost - dad.cost) as total_markup_amount,
                    COUNT(DISTINCT dad.date_value) as days_count
                FROM daily_ad_data dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                JOIN clients c ON aa.client_id = c.id
                WHERE DATE_FORMAT(dad.date_value, '%Y-%m') = ?
                    AND dad.reported_cost > dad.cost
                    AND dad.sync_status = 'synced'
                GROUP BY c.id, aa.id
                HAVING total_markup_amount > 0
                ORDER BY c.company_name, aa.platform, aa.account_name";
        
        return $this->query($sql, array($yearMonth));
    }

    /**
     * 日別上乗せ計算の実行
     */
    public function applyDailyMarkups(string $date)
    {
        $dailyDataModel = new DailyAdData();
        
        // 指定日の全データを取得
        $sql = "SELECT dad.*, aa.client_id
                FROM daily_ad_data dad
                JOIN ad_accounts aa ON dad.ad_account_id = aa.id
                WHERE dad.date_value = ? 
                    AND dad.sync_status = 'synced'";
        
        $dailyData = $this->query($sql, array($date));
        $updatedCount = 0;

        foreach ($dailyData as $data) {
            $markup = $this->calculateMarkup($dataarray('ad_account_id'), $dataarray('cost'), $date);
            
            if ($markuparray('total_cost') != $dataarray('reported_cost')) {
                $dailyDataModel->update($dataarray('id'), [
                    'reported_cost' => $markuparray('total_cost')
                ]);
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * バックデート適用（過去データへの上乗せ適用）
     */
    public function applyBackdatedMarkup(int $markupId, string $startDate, string $endDate)
    {
        $markup = $this->find($markupId);
        if (!$markup || !$markuparray('is_active')) {
            return 0;
        }

        $dailyDataModel = new DailyAdData();
        $updatedCount = 0;

        // アカウント指定がある場合は単一アカウント、ない場合はクライアント全体
        if ($markuparray('ad_account_id')) {
            $accountIds = array($markup['ad_account_id')];
        } else {
            $adAccountModel = new AdAccount();
            $accounts = $adAccountModel->getActiveAccountsByClient($markuparray('client_id'));
            $accountIds = array_column($accounts, 'id');
        }

        foreach ($accountIds as $accountId) {
            $sql = "SELECT * FROM daily_ad_data 
                    WHERE ad_account_id = ? 
                        AND date_value BETWEEN ? AND ?
                        AND sync_status = 'synced'";
            
            $dailyData = $this->query($sql, array($accountId, $startDate, $endDate));
            
            foreach ($dailyData as $data) {
                $newMarkup = $this->calculateMarkup($accountId, $dataarray('cost'), $dataarray('date_value'));
                
                if ($newMarkuparray('total_cost') != $dataarray('reported_cost')) {
                    $dailyDataModel->update($dataarray('id'), [
                        'reported_cost' => $newMarkuparray('total_cost')
                    ]);
                    $updatedCount++;
                }
            }
        }

        return $updatedCount;
    }

    /**
     * 上乗せ設定の複製
     */
    public function duplicateMarkup(int $markupId, array $overrides = array()): ?array
    {
        $original = $this->find($markupId);
        if (!$original) {
            return null;
        }

        $newData = array_merge($original, $overrides);
        unset($newDataarray('id'), $newDataarray('created_at'), $newDataarray('updated_at'));

        // 新しい有効期間を設定
        if (!isset($overridesarray('effective_from'))) {
            $newDataarray('effective_from') = date('Y-m-d');
        }

        return $this->create($newData);
    }
}