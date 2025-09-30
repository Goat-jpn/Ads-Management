<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class BillingItem extends Model
{
    protected $table = 'billing_items';
    protected $fillable = [
        'billing_record_id', 'ad_account_id', 'service_type', 
        'description', 'service_period_start', 'service_period_end',
        'quantity', 'unit_price', 'total_price'
    ];
    
    public function getByBillingRecord($billingRecordId)
    {
        $sql = "SELECT bi.*, aa.account_name, aa.platform
                FROM {$this->table} bi
                LEFT JOIN ad_accounts aa ON bi.ad_account_id = aa.id
                WHERE bi.billing_record_id = ?
                ORDER BY bi.service_period_start ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$billingRecordId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByAdAccount($adAccountId, $startDate = null, $endDate = null)
    {
        $sql = "SELECT bi.*, br.billing_period_start, br.billing_period_end, br.status as billing_status
                FROM {$this->table} bi
                JOIN billing_records br ON bi.billing_record_id = br.id
                WHERE bi.ad_account_id = ?";
        
        $params = [$adAccountId];
        
        if ($startDate) {
            $sql .= " AND bi.service_period_start >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND bi.service_period_end <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY bi.service_period_start DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getServiceTypeStats()
    {
        $sql = "SELECT 
                    service_type,
                    COUNT(*) as count,
                    SUM(total_price) as total_revenue
                FROM {$this->table} 
                GROUP BY service_type
                ORDER BY total_revenue DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTopPerformingAccounts($limit = 10)
    {
        $sql = "SELECT 
                    aa.account_name,
                    aa.platform,
                    c.name as client_name,
                    SUM(bi.total_price) as total_revenue,
                    COUNT(bi.id) as billing_count
                FROM {$this->table} bi
                JOIN ad_accounts aa ON bi.ad_account_id = aa.id
                JOIN clients c ON aa.client_id = c.id
                GROUP BY bi.ad_account_id
                ORDER BY total_revenue DESC
                LIMIT ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function calculateTotalForBilling($billingRecordId)
    {
        $sql = "SELECT SUM(total_price) as total FROM {$this->table} WHERE billing_record_id = ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$billingRecordId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] ?? 0;
    }
    
    public function addCustomItem($billingRecordId, $itemData)
    {
        $data = array_merge($itemData, [
            'billing_record_id' => $billingRecordId,
            'total_price' => $itemData['quantity'] * $itemData['unit_price']
        ]);
        
        return $this->create($data);
    }
    
    public function updateItem($itemId, $itemData)
    {
        // Recalculate total price if quantity or unit price changed
        if (isset($itemData['quantity']) || isset($itemData['unit_price'])) {
            $currentItem = $this->find($itemId);
            if ($currentItem) {
                $quantity = $itemData['quantity'] ?? $currentItem['quantity'];
                $unitPrice = $itemData['unit_price'] ?? $currentItem['unit_price'];
                $itemData['total_price'] = $quantity * $unitPrice;
            }
        }
        
        return $this->update($itemId, $itemData);
    }
    
    public function deleteItemsForBilling($billingRecordId)
    {
        $sql = "DELETE FROM {$this->table} WHERE billing_record_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$billingRecordId]);
    }
    
    public function getRevenueByPeriod($startDate, $endDate, $groupBy = 'month')
    {
        $dateFormat = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';
        
        $sql = "SELECT 
                    DATE_FORMAT(service_period_start, ?) as period,
                    SUM(total_price) as revenue,
                    COUNT(*) as item_count
                FROM {$this->table} 
                WHERE service_period_start >= ? AND service_period_end <= ?
                GROUP BY period
                ORDER BY period ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$dateFormat, $startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getItemsByServiceType($serviceType, $startDate = null, $endDate = null)
    {
        $sql = "SELECT bi.*, aa.account_name, aa.platform, c.name as client_name
                FROM {$this->table} bi
                LEFT JOIN ad_accounts aa ON bi.ad_account_id = aa.id
                LEFT JOIN clients c ON aa.client_id = c.id
                WHERE bi.service_type = ?";
        
        $params = [$serviceType];
        
        if ($startDate) {
            $sql .= " AND bi.service_period_start >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND bi.service_period_end <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY bi.service_period_start DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function duplicateItemsForNewBilling($sourceBillingId, $targetBillingId)
    {
        $sql = "INSERT INTO {$this->table} (
                    billing_record_id, ad_account_id, service_type, description,
                    service_period_start, service_period_end, quantity, unit_price, total_price
                )
                SELECT 
                    ? as billing_record_id, ad_account_id, service_type, description,
                    service_period_start, service_period_end, quantity, unit_price, total_price
                FROM {$this->table}
                WHERE billing_record_id = ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$targetBillingId, $sourceBillingId]);
    }
    
    public function getDetailedItemReport($startDate = null, $endDate = null)
    {
        $sql = "SELECT 
                    bi.*,
                    aa.account_name,
                    aa.platform,
                    c.name as client_name,
                    c.company,
                    br.status as billing_status,
                    br.billing_period_start,
                    br.billing_period_end
                FROM {$this->table} bi
                LEFT JOIN ad_accounts aa ON bi.ad_account_id = aa.id
                LEFT JOIN clients c ON aa.client_id = c.id
                JOIN billing_records br ON bi.billing_record_id = br.id";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " WHERE bi.service_period_start >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $whereClause = $startDate ? " AND" : " WHERE";
            $sql .= "{$whereClause} bi.service_period_end <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY bi.service_period_start DESC, c.name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}