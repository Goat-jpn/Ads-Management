<?php

namespace App\Models;

class FeeSetting extends BaseModel
{
    protected string $table = 'fee_settings';
    protected array $fillable = [
        'client_id',
        'platform',
        'fee_type',
        'base_percentage',
        'fixed_amount',
        'minimum_fee',
        'maximum_fee',
        'is_active',
        'effective_from',
        'effective_to'
    ];

    protected array $casts = [
        'client_id' => 'int',
        'base_percentage' => 'float',
        'fixed_amount' => 'float',
        'minimum_fee' => 'float',
        'maximum_fee' => 'float',
        'is_active' => 'bool',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime'
    ];

    /**
     * クライアント・プラットフォーム別のアクティブな手数料設定を取得
     */
    public function getActiveSettingsForClient(int $clientId): array
    {
        $today = date('Y-m-d');
        
        $sql = "SELECT * FROM {$this->table}
                WHERE client_id = ? 
                    AND is_active = 1
                    AND effective_from <= ?
                    AND (effective_to IS NULL OR effective_to >= ?)
                ORDER BY platform, effective_from DESC";
        
        return $this->processResults($this->query($sql, [$clientId, $today, $today]));
    }

    /**
     * 特定プラットフォームの手数料設定を取得
     */
    public function getSettingForPlatform(int $clientId, string $platform, string $date = null): ?array
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT * FROM {$this->table}
                WHERE client_id = ? 
                    AND platform = ?
                    AND is_active = 1
                    AND effective_from <= ?
                    AND (effective_to IS NULL OR effective_to >= ?)
                ORDER BY effective_from DESC
                LIMIT 1";
        
        $result = $this->query($sql, [$clientId, $platform, $date, $date]);
        return $result[0] ?? null;
    }

    /**
     * 手数料を計算
     */
    public function calculateFee(int $clientId, string $platform, float $adCost, string $date = null): array
    {
        $setting = $this->getSettingForPlatform($clientId, $platform, $date);
        
        if (!$setting) {
            return [
                'fee_amount' => 0,
                'calculation_method' => 'no_setting',
                'details' => '手数料設定が見つかりません'
            ];
        }

        $feeAmount = 0;
        $calculationMethod = $setting['fee_type'];
        $details = [];

        switch ($setting['fee_type']) {
            case 'percentage':
                $feeAmount = $adCost * ($setting['base_percentage'] / 100);
                $details[] = "基本料率: {$setting['base_percentage']}%";
                break;

            case 'fixed':
                $feeAmount = $setting['fixed_amount'];
                $details[] = "固定手数料: ¥" . number_format($setting['fixed_amount']);
                break;

            case 'tiered':
                $feeAmount = $this->calculateTieredFee($setting['id'], $adCost);
                $calculationMethod = 'tiered';
                $details[] = "階段型手数料";
                break;
        }

        // 最低・最高手数料の適用
        if ($setting['minimum_fee'] && $feeAmount < $setting['minimum_fee']) {
            $feeAmount = $setting['minimum_fee'];
            $details[] = "最低手数料適用: ¥" . number_format($setting['minimum_fee']);
        }

        if ($setting['maximum_fee'] && $feeAmount > $setting['maximum_fee']) {
            $feeAmount = $setting['maximum_fee'];
            $details[] = "最高手数料適用: ¥" . number_format($setting['maximum_fee']);
        }

        return [
            'fee_amount' => $feeAmount,
            'calculation_method' => $calculationMethod,
            'details' => implode(', ', $details),
            'setting_id' => $setting['id']
        ];
    }

    /**
     * 階段型手数料の計算
     */
    public function calculateTieredFee(int $feeSettingId, float $adCost): float
    {
        $tieredFeeModel = new TieredFee();
        $tiers = $tieredFeeModel->findAllBy(
            ['fee_setting_id' => $feeSettingId], 
            ['min_amount' => 'ASC']
        );

        $totalFee = 0;
        $remainingAmount = $adCost;

        foreach ($tiers as $tier) {
            $tierMin = $tier['min_amount'];
            $tierMax = $tier['max_amount'] ?? PHP_FLOAT_MAX;
            $percentage = $tier['percentage'];

            // この階層で適用する金額を計算
            $applicableAmount = 0;
            
            if ($remainingAmount > 0 && $adCost > $tierMin) {
                $tierStart = max($tierMin, $adCost - $remainingAmount);
                $tierEnd = min($tierMax, $adCost);
                
                if ($tierEnd > $tierStart) {
                    $applicableAmount = $tierEnd - $tierStart;
                    $totalFee += $applicableAmount * ($percentage / 100);
                    $remainingAmount -= $applicableAmount;
                }
            }

            if ($remainingAmount <= 0) {
                break;
            }
        }

        return $totalFee;
    }

    /**
     * 手数料設定の有効性チェック
     */
    public function validateSetting(array $data): array
    {
        $errors = [];

        // 必須フィールドチェック
        if (empty($data['client_id'])) {
            $errors[] = 'クライアントIDは必須です';
        }

        if (empty($data['platform'])) {
            $errors[] = 'プラットフォームは必須です';
        }

        if (empty($data['fee_type'])) {
            $errors[] = '手数料タイプは必須です';
        }

        // 日付の妥当性チェック
        if (!empty($data['effective_from'])) {
            if (!strtotime($data['effective_from'])) {
                $errors[] = '開始日の形式が正しくありません';
            }
        }

        if (!empty($data['effective_to'])) {
            if (!strtotime($data['effective_to'])) {
                $errors[] = '終了日の形式が正しくありません';
            }
            
            if (!empty($data['effective_from']) && 
                strtotime($data['effective_to']) <= strtotime($data['effective_from'])) {
                $errors[] = '終了日は開始日より後の日付である必要があります';
            }
        }

        // 手数料タイプ別チェック
        switch ($data['fee_type']) {
            case 'percentage':
                if (empty($data['base_percentage']) || $data['base_percentage'] < 0 || $data['base_percentage'] > 100) {
                    $errors[] = '基本料率は0〜100の範囲で入力してください';
                }
                break;

            case 'fixed':
                if (empty($data['fixed_amount']) || $data['fixed_amount'] < 0) {
                    $errors[] = '固定手数料額は0以上で入力してください';
                }
                break;

            case 'tiered':
                // 階段型の場合は最低手数料のみチェック
                if (!empty($data['minimum_fee']) && $data['minimum_fee'] < 0) {
                    $errors[] = '最低手数料額は0以上で入力してください';
                }
                break;
        }

        // 重複チェック
        if (empty($errors)) {
            $duplicate = $this->checkDuplicateSetting($data);
            if ($duplicate) {
                $errors[] = '同じ期間に同一プラットフォームの設定が既に存在します';
            }
        }

        return $errors;
    }

    /**
     * 重複設定のチェック
     */
    private function checkDuplicateSetting(array $data): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE client_id = ? 
                    AND platform = ?
                    AND is_active = 1
                    AND (
                        (effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)) OR
                        (? <= effective_from AND (? IS NULL OR ? >= effective_from))
                    )";

        $params = [
            $data['client_id'],
            $data['platform'],
            $data['effective_from'],
            $data['effective_from'],
            $data['effective_from'],
            $data['effective_to'] ?? null,
            $data['effective_to'] ?? null
        ];

        if (!empty($data['id'])) {
            $sql .= " AND id != ?";
            $params[] = $data['id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * 手数料設定の無効化
     */
    public function deactivateSetting(int $settingId): ?array
    {
        return $this->update($settingId, [
            'is_active' => false,
            'effective_to' => date('Y-m-d')
        ]);
    }

    /**
     * プラットフォーム別の設定統計を取得
     */
    public function getPlatformStats(): array
    {
        $sql = "SELECT 
                    platform,
                    COUNT(*) as total_settings,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_settings,
                    AVG(CASE WHEN fee_type = 'percentage' THEN base_percentage ELSE NULL END) as avg_percentage,
                    AVG(CASE WHEN fee_type = 'fixed' THEN fixed_amount ELSE NULL END) as avg_fixed_amount
                FROM {$this->table}
                GROUP BY platform
                ORDER BY platform";
        
        return $this->query($sql);
    }

    /**
     * 期限切れ間近の設定を取得
     */
    public function getExpiringSoon(int $daysAhead = 30): array
    {
        $targetDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        
        $sql = "SELECT 
                    fs.*,
                    c.company_name
                FROM {$this->table} fs
                JOIN clients c ON fs.client_id = c.id
                WHERE fs.effective_to IS NOT NULL 
                    AND fs.effective_to <= ? 
                    AND fs.is_active = 1
                ORDER BY fs.effective_to ASC";
        
        return $this->query($sql, [$targetDate]);
    }

    /**
     * 手数料設定の履歴を取得
     */
    public function getSettingHistory(int $clientId, string $platform): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE client_id = ? AND platform = ?
                ORDER BY effective_from DESC, created_at DESC";
        
        return $this->processResults($this->query($sql, [$clientId, $platform]));
    }

    /**
     * 月次手数料レポートデータを取得
     */
    public function getMonthlyFeeReport(string $yearMonth): array
    {
        $sql = "SELECT 
                    c.company_name,
                    fs.platform,
                    fs.fee_type,
                    fs.base_percentage,
                    fs.fixed_amount,
                    ms.total_cost,
                    ms.calculated_fee
                FROM fee_settings fs
                JOIN clients c ON fs.client_id = c.id
                JOIN monthly_summaries ms ON fs.client_id = ms.client_id
                JOIN ad_accounts aa ON ms.ad_account_id = aa.id AND aa.platform = fs.platform
                WHERE ms.year_month = ?
                    AND fs.is_active = 1
                    AND fs.effective_from <= LAST_DAY(STR_TO_DATE(?, '%Y-%m'))
                    AND (fs.effective_to IS NULL OR fs.effective_to >= CONCAT(?, '-01'))
                ORDER BY c.company_name, fs.platform";
        
        return $this->query($sql, [$yearMonth, $yearMonth, $yearMonth]);
    }
}