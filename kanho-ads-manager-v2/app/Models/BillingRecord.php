<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class BillingRecord extends Model
{
    protected $table = 'billing_records';
    protected $fillable = [
        'client_id', 'billing_period_start', 'billing_period_end', 
        'status', 'total_amount', 'tax_amount', 'discount_amount',
        'notes', 'due_date', 'paid_date'
    ];
    
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_BILLED = 'billed';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    
    public function getByClient($clientId, $limit = null)
    {
        $sql = "SELECT br.*, c.name as client_name, c.company
                FROM {$this->table} br
                JOIN clients c ON br.client_id = c.id
                WHERE br.client_id = ?
                ORDER BY br.billing_period_start DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clientId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByStatus($status)
    {
        $sql = "SELECT br.*, c.name as client_name, c.company, c.email
                FROM {$this->table} br
                JOIN clients c ON br.client_id = c.id
                WHERE br.status = ?
                ORDER BY br.created_at DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$status]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getOverdueBillings()
    {
        $sql = "SELECT br.*, c.name as client_name, c.company, c.email
                FROM {$this->table} br
                JOIN clients c ON br.client_id = c.id
                WHERE br.status IN ('billed', 'pending') 
                AND br.due_date < CURDATE()
                ORDER BY br.due_date ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        // Update status to overdue
        $overdueIds = [];
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $result) {
            $overdueIds[] = $result['id'];
        }
        
        if (!empty($overdueIds)) {
            $this->markAsOverdue($overdueIds);
            // Update the results to reflect the new status
            foreach ($results as &$result) {
                $result['status'] = self::STATUS_OVERDUE;
            }
        }
        
        return $results;
    }
    
    public function getBillingWithItems($billingId)
    {
        // Get billing record
        $billingSql = "SELECT br.*, c.name as client_name, c.company, c.email, c.address, c.city, c.state, c.zip_code
                       FROM {$this->table} br
                       JOIN clients c ON br.client_id = c.id
                       WHERE br.id = ?";
        
        $stmt = $this->db->getConnection()->prepare($billingSql);
        $stmt->execute([$billingId]);
        
        $billing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$billing) {
            return null;
        }
        
        // Get billing items
        $itemsSql = "SELECT bi.*, aa.account_name, aa.platform
                     FROM billing_items bi
                     LEFT JOIN ad_accounts aa ON bi.ad_account_id = aa.id
                     WHERE bi.billing_record_id = ?
                     ORDER BY bi.service_period_start ASC";
        
        $itemsStmt = $this->db->getConnection()->prepare($itemsSql);
        $itemsStmt->execute([$billingId]);
        
        $billing['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $billing;
    }
    
    public function generateBilling($clientId, $periodStart, $periodEnd)
    {
        // Create billing record
        $billingData = [
            'client_id' => $clientId,
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'status' => self::STATUS_DRAFT,
            'total_amount' => 0,
            'due_date' => date('Y-m-d', strtotime('+30 days'))
        ];
        
        $billingId = $this->create($billingData);
        
        if (!$billingId) {
            return false;
        }
        
        // Get ad accounts for this client
        $adAccountModel = new AdAccount();
        $adAccounts = $adAccountModel->getByClient($clientId);
        
        $totalAmount = 0;
        $billingItemModel = new BillingItem();
        
        foreach ($adAccounts as $account) {
            // Calculate cost for this account in the period
            $cost = $this->calculateAccountCost($account['id'], $periodStart, $periodEnd);
            
            if ($cost > 0) {
                $itemData = [
                    'billing_record_id' => $billingId,
                    'ad_account_id' => $account['id'],
                    'service_type' => 'ad_management',
                    'description' => "Ad management for {$account['account_name']} ({$account['platform']})",
                    'service_period_start' => $periodStart,
                    'service_period_end' => $periodEnd,
                    'quantity' => 1,
                    'unit_price' => $cost,
                    'total_price' => $cost
                ];
                
                $billingItemModel->create($itemData);
                $totalAmount += $cost;
            }
        }
        
        // Update billing record with total
        $this->update($billingId, ['total_amount' => $totalAmount]);
        
        return $billingId;
    }
    
    private function calculateAccountCost($adAccountId, $periodStart, $periodEnd)
    {
        $sql = "SELECT SUM(cost) as total_cost 
                FROM daily_stats 
                WHERE ad_account_id = ? 
                AND date >= ? AND date <= ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$adAccountId, $periodStart, $periodEnd]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Apply management fee (e.g., 20% of ad spend)
        $adSpend = $result['total_cost'] ?? 0;
        $managementFee = $adSpend * 0.20; // 20% management fee
        
        return $managementFee;
    }
    
    public function updateStatus($billingId, $status, $paidDate = null)
    {
        $data = ['status' => $status];
        
        if ($status === self::STATUS_PAID && $paidDate) {
            $data['paid_date'] = $paidDate;
        }
        
        return $this->update($billingId, $data);
    }
    
    private function markAsOverdue($billingIds)
    {
        if (empty($billingIds)) {
            return;
        }
        
        $placeholders = str_repeat('?,', count($billingIds) - 1) . '?';
        $sql = "UPDATE {$this->table} SET status = ? WHERE id IN ({$placeholders})";
        
        $params = array_merge([self::STATUS_OVERDUE], $billingIds);
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
    }
    
    public function getBillingStats()
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_amount) as total_amount
                FROM {$this->table} 
                GROUP BY status
                ORDER BY status";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMonthlyRevenue($year = null)
    {
        $year = $year ?? date('Y');
        
        $sql = "SELECT 
                    MONTH(billing_period_start) as month,
                    SUM(total_amount) as revenue
                FROM {$this->table} 
                WHERE YEAR(billing_period_start) = ? 
                AND status = ?
                GROUP BY MONTH(billing_period_start)
                ORDER BY month";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$year, self::STATUS_PAID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPendingBillings()
    {
        return $this->getByStatus(self::STATUS_PENDING);
    }
    
    public function getDraftBillings()
    {
        return $this->getByStatus(self::STATUS_DRAFT);
    }
}