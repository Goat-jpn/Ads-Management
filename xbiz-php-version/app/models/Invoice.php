<?php

namespace App\Models;

class Invoice extends BaseModel
{
    protected string $table = 'invoices';
    protected array $fillable = [
        'client_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'subtotal_ad_cost',
        'subtotal_fees',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'status',
        'issued_at',
        'due_date',
        'paid_at',
        'notes'
    ];

    protected array $casts = [
        'client_id' => 'int',
        'subtotal_ad_cost' => 'float',
        'subtotal_fees' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'issued_at' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime'
    ];

    /**
     * 請求書番号を自動生成
     */
    public function generateInvoiceNumber(): string
    {
        $config = require __DIR__ . '/../../config/app.php';
        $format = $config['billing']['invoice_number_format'] ?? 'INV-%Y%m%d-%04d';
        
        $datePrefix = date(str_replace(['%Y', '%m', '%d'], ['Y', 'm', 'd'], $format));
        
        // 同日の最大連番を取得
        $sql = "SELECT MAX(CAST(SUBSTRING(invoice_number, -4) AS UNSIGNED)) as max_seq
                FROM {$this->table} 
                WHERE invoice_number LIKE ?";
        
        $likePattern = str_replace('-%04d', '-%', $datePrefix);
        $result = $this->query($sql, [$likePattern . '____']);
        
        $nextSeq = ($result[0]['max_seq'] ?? 0) + 1;
        
        return sprintf(str_replace('%04d', '%04d', $datePrefix), $nextSeq);
    }

    /**
     * 請求書を作成
     */
    public function createInvoice(int $clientId, string $startDate, string $endDate): array
    {
        $client = new Client();
        $clientData = $client->find($clientId);
        
        if (!$clientData) {
            throw new \Exception('クライアントが見つかりません');
        }

        // 請求データを集計
        $billingData = $this->aggregateBillingData($clientId, $startDate, $endDate);
        
        if (empty($billingData)) {
            throw new \Exception('請求対象データがありません');
        }

        $config = require __DIR__ . '/../../config/app.php';
        $taxRate = $config['billing']['default_tax_rate'] ?? 0.10;
        $paymentTerms = $clientData['payment_terms'] ?? 30;

        $subtotalAdCost = array_sum(array_column($billingData, 'ad_cost'));
        $subtotalFees = array_sum(array_column($billingData, 'fee_amount'));
        $subtotal = $subtotalAdCost + $subtotalFees;
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount;

        $invoiceData = [
            'client_id' => $clientId,
            'invoice_number' => $this->generateInvoiceNumber(),
            'billing_period_start' => $startDate,
            'billing_period_end' => $endDate,
            'subtotal_ad_cost' => $subtotalAdCost,
            'subtotal_fees' => $subtotalFees,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'due_date' => date('Y-m-d', strtotime("+{$paymentTerms} days"))
        ];

        return $this->transaction(function() use ($invoiceData, $billingData) {
            $invoice = $this->create($invoiceData);
            
            // 請求明細を作成
            $invoiceItemModel = new InvoiceItem();
            foreach ($billingData as $item) {
                $invoiceItemModel->create([
                    'invoice_id' => $invoice['id'],
                    'ad_account_id' => $item['ad_account_id'],
                    'platform' => $item['platform'],
                    'description' => $this->generateItemDescription($item),
                    'ad_cost' => $item['ad_cost'],
                    'fee_amount' => $item['fee_amount']
                ]);
            }

            // 月次集計データの請求フラグを更新
            $this->updateMonthlySummaryInvoiceFlag($billingData, $invoice['id']);

            return $invoice;
        });
    }

    /**
     * 請求データの集計
     */
    private function aggregateBillingData(int $clientId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    ms.ad_account_id,
                    aa.platform,
                    aa.account_name,
                    SUM(ms.total_reported_cost) as ad_cost,
                    SUM(ms.calculated_fee) as fee_amount,
                    COUNT(ms.year_month) as months_count
                FROM monthly_summaries ms
                JOIN ad_accounts aa ON ms.ad_account_id = aa.id
                WHERE ms.client_id = ?
                    AND ms.year_month >= DATE_FORMAT(?, '%Y-%m')
                    AND ms.year_month <= DATE_FORMAT(?, '%Y-%m')
                    AND ms.is_invoiced = 0
                    AND aa.is_active = 1
                GROUP BY ms.ad_account_id
                HAVING ad_cost > 0 OR fee_amount > 0";

        return $this->query($sql, [$clientId, $startDate, $endDate]);
    }

    /**
     * 請求明細の説明文を生成
     */
    private function generateItemDescription(array $item): string
    {
        $platform = match($item['platform']) {
            'google_ads' => 'Google広告',
            'yahoo_display' => 'Yahoo!ディスプレイ広告',
            'yahoo_search' => 'Yahoo!検索広告',
            default => $item['platform']
        };

        return "{$platform} ({$item['account_name']}) - {$item['months_count']}ヶ月分";
    }

    /**
     * 月次集計データの請求フラグを更新
     */
    private function updateMonthlySummaryInvoiceFlag(array $billingData, int $invoiceId): void
    {
        $monthlySummaryModel = new MonthlySummary();
        
        foreach ($billingData as $item) {
            $sql = "UPDATE monthly_summaries 
                    SET is_invoiced = 1 
                    WHERE ad_account_id = ? AND is_invoiced = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$item['ad_account_id']]);
        }
    }

    /**
     * 請求書を発行
     */
    public function issueInvoice(int $invoiceId): ?array
    {
        $invoice = $this->find($invoiceId);
        
        if (!$invoice || $invoice['status'] !== 'draft') {
            throw new \Exception('発行できない請求書です');
        }

        return $this->update($invoiceId, [
            'status' => 'sent',
            'issued_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 支払い記録
     */
    public function recordPayment(int $invoiceId, string $paidDate = null): ?array
    {
        $paidDate = $paidDate ?: date('Y-m-d H:i:s');
        
        return $this->update($invoiceId, [
            'status' => 'paid',
            'paid_at' => $paidDate
        ]);
    }

    /**
     * 請求書をキャンセル
     */
    public function cancelInvoice(int $invoiceId): ?array
    {
        $invoice = $this->find($invoiceId);
        
        if (!$invoice || $invoice['status'] === 'paid') {
            throw new \Exception('キャンセルできない請求書です');
        }

        return $this->transaction(function() use ($invoiceId) {
            // 月次集計データの請求フラグをリセット
            $sql = "UPDATE monthly_summaries ms
                    JOIN invoice_items ii ON ms.ad_account_id = ii.ad_account_id
                    SET ms.is_invoiced = 0
                    WHERE ii.invoice_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$invoiceId]);

            return $this->update($invoiceId, ['status' => 'cancelled']);
        });
    }

    /**
     * 延滞請求書を取得
     */
    public function getOverdueInvoices(): array
    {
        $today = date('Y-m-d');
        
        $sql = "SELECT 
                    i.*,
                    c.company_name,
                    c.contact_name,
                    c.email,
                    DATEDIFF(?, i.due_date) as days_overdue
                FROM {$this->table} i
                JOIN clients c ON i.client_id = c.id
                WHERE i.status IN ('sent', 'overdue')
                    AND i.due_date < ?
                ORDER BY days_overdue DESC, i.total_amount DESC";
        
        return $this->processResults($this->query($sql, [$today, $today]));
    }

    /**
     * 今月の請求統計を取得
     */
    public function getMonthlyInvoiceStats(string $yearMonth = null): array
    {
        $yearMonth = $yearMonth ?: date('Y-m');
        
        $sql = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
                    SUM(total_amount) as total_amount,
                    SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status IN ('sent', 'overdue') THEN total_amount ELSE 0 END) as outstanding_amount
                FROM {$this->table}
                WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
        
        $result = $this->query($sql, [$yearMonth]);
        return $result[0] ?? [];
    }

    /**
     * クライアント別請求履歴を取得
     */
    public function getClientInvoiceHistory(int $clientId, int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE client_id = ? 
                ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->processResults($this->query($sql, [$clientId]));
    }

    /**
     * 請求書明細を取得
     */
    public function getInvoiceItems(int $invoiceId): array
    {
        $invoiceItemModel = new InvoiceItem();
        return $invoiceItemModel->findAllBy(['invoice_id' => $invoiceId]);
    }

    /**
     * 請求書詳細（クライアント情報付き）を取得
     */
    public function getInvoiceWithDetails(int $invoiceId): ?array
    {
        $sql = "SELECT 
                    i.*,
                    c.company_name,
                    c.contact_name,
                    c.email,
                    c.phone,
                    c.address
                FROM {$this->table} i
                JOIN clients c ON i.client_id = c.id
                WHERE i.id = ?";
        
        $result = $this->query($sql, [$invoiceId]);
        
        if (empty($result)) {
            return null;
        }

        $invoice = $this->processResult($result[0]);
        $invoice['items'] = $this->getInvoiceItems($invoiceId);

        return $invoice;
    }

    /**
     * 請求書の一括ステータス更新
     */
    public function bulkUpdateStatus(array $invoiceIds, string $status): int
    {
        if (empty($invoiceIds)) {
            return 0;
        }

        $allowedStatuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            throw new \Exception('無効なステータスです');
        }

        $placeholders = array_fill(0, count($invoiceIds), '?');
        $sql = "UPDATE {$this->table} 
                SET status = ?, updated_at = NOW()
                WHERE id IN (" . implode(', ', $placeholders) . ")";
        
        $params = [$status];
        $params = array_merge($params, $invoiceIds);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 延滞ステータスの自動更新
     */
    public function updateOverdueStatus(): int
    {
        $today = date('Y-m-d');
        
        $sql = "UPDATE {$this->table} 
                SET status = 'overdue', updated_at = NOW()
                WHERE status = 'sent' 
                    AND due_date < ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$today]);

        return $stmt->rowCount();
    }
}