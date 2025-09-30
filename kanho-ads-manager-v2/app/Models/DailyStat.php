<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class DailyStat extends Model
{
    protected $table = 'daily_stats';
    protected $fillable = [
        'ad_account_id', 'campaign_id', 'date', 'impressions', 
        'clicks', 'cost', 'conversions', 'conversion_value'
    ];
    
    public function getByAdAccount($adAccountId, $startDate = null, $endDate = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE ad_account_id = ?";
        $params = [$adAccountId];
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY date DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByCampaign($campaignId, $startDate = null, $endDate = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE campaign_id = ?";
        $params = [$campaignId];
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY date DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAggregatedStats($adAccountId = null, $campaignId = null, $startDate = null, $endDate = null)
    {
        $sql = "SELECT 
                    DATE(date) as stat_date,
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    SUM(cost) as total_cost,
                    SUM(conversions) as total_conversions,
                    SUM(conversion_value) as total_conversion_value
                FROM {$this->table} 
                WHERE 1=1";
        
        $params = [];
        
        if ($adAccountId) {
            $sql .= " AND ad_account_id = ?";
            $params[] = $adAccountId;
        }
        
        if ($campaignId) {
            $sql .= " AND campaign_id = ?";
            $params[] = $campaignId;
        }
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY DATE(date) ORDER BY stat_date DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSummaryStats($adAccountId = null, $campaignId = null, $startDate = null, $endDate = null)
    {
        $sql = "SELECT 
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    SUM(cost) as total_cost,
                    SUM(conversions) as total_conversions,
                    SUM(conversion_value) as total_conversion_value,
                    AVG(CASE WHEN impressions > 0 THEN (clicks / impressions) * 100 ELSE 0 END) as avg_ctr,
                    AVG(CASE WHEN clicks > 0 THEN cost / clicks ELSE 0 END) as avg_cpc,
                    AVG(CASE WHEN impressions > 0 THEN (cost / impressions) * 1000 ELSE 0 END) as avg_cpm,
                    AVG(CASE WHEN clicks > 0 THEN (conversions / clicks) * 100 ELSE 0 END) as avg_conversion_rate
                FROM {$this->table} 
                WHERE 1=1";
        
        $params = [];
        
        if ($adAccountId) {
            $sql .= " AND ad_account_id = ?";
            $params[] = $adAccountId;
        }
        
        if ($campaignId) {
            $sql .= " AND campaign_id = ?";
            $params[] = $campaignId;
        }
        
        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate derived metrics
        if ($result) {
            $totalImpressions = $result['total_impressions'] ?? 0;
            $totalClicks = $result['total_clicks'] ?? 0;
            $totalCost = $result['total_cost'] ?? 0;
            $totalConversions = $result['total_conversions'] ?? 0;
            
            $result['overall_ctr'] = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
            $result['overall_cpc'] = $totalClicks > 0 ? $totalCost / $totalClicks : 0;
            $result['overall_cpm'] = $totalImpressions > 0 ? ($totalCost / $totalImpressions) * 1000 : 0;
            $result['overall_conversion_rate'] = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
            $result['overall_roas'] = $totalCost > 0 ? ($result['total_conversion_value'] ?? 0) / $totalCost : 0;
            
            // Round values (null安全)
            $result['avg_ctr'] = round($result['avg_ctr'] ?? 0, 2);
            $result['avg_cpc'] = round($result['avg_cpc'] ?? 0, 2);
            $result['avg_cpm'] = round($result['avg_cpm'] ?? 0, 2);
            $result['avg_conversion_rate'] = round($result['avg_conversion_rate'] ?? 0, 2);
            $result['overall_ctr'] = round($result['overall_ctr'] ?? 0, 2);
            $result['overall_cpc'] = round($result['overall_cpc'] ?? 0, 2);
            $result['overall_cpm'] = round($result['overall_cpm'] ?? 0, 2);
            $result['overall_conversion_rate'] = round($result['overall_conversion_rate'] ?? 0, 2);
            $result['overall_roas'] = round($result['overall_roas'] ?? 0, 2);
        }
        
        return $result;
    }
    
    public function getTopPerformingCampaigns($limit = 10, $orderBy = 'cost', $startDate = null, $endDate = null)
    {
        $validOrderBy = ['impressions', 'clicks', 'cost', 'conversions', 'conversion_value', 'ctr', 'cpc', 'conversion_rate'];
        
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'cost';
        }
        
        $orderClause = match($orderBy) {
            'ctr' => '(SUM(clicks) / SUM(impressions)) * 100',
            'cpc' => 'SUM(cost) / SUM(clicks)',
            'conversion_rate' => '(SUM(conversions) / SUM(clicks)) * 100',
            default => "SUM({$orderBy})"
        };
        
        $sql = "SELECT 
                    c.name as campaign_name,
                    c.campaign_type,
                    aa.account_name,
                    aa.platform,
                    cl.name as client_name,
                    SUM(ds.impressions) as total_impressions,
                    SUM(ds.clicks) as total_clicks,
                    SUM(ds.cost) as total_cost,
                    SUM(ds.conversions) as total_conversions,
                    SUM(ds.conversion_value) as total_conversion_value,
                    CASE WHEN SUM(ds.impressions) > 0 THEN (SUM(ds.clicks) / SUM(ds.impressions)) * 100 ELSE 0 END as ctr,
                    CASE WHEN SUM(ds.clicks) > 0 THEN SUM(ds.cost) / SUM(ds.clicks) ELSE 0 END as cpc,
                    CASE WHEN SUM(ds.clicks) > 0 THEN (SUM(ds.conversions) / SUM(ds.clicks)) * 100 ELSE 0 END as conversion_rate
                FROM {$this->table} ds
                JOIN campaigns c ON ds.campaign_id = c.id
                JOIN ad_accounts aa ON ds.ad_account_id = aa.id
                JOIN clients cl ON aa.client_id = cl.id
                WHERE 1=1";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND ds.date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND ds.date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY ds.campaign_id 
                  ORDER BY {$orderClause} DESC
                  LIMIT ?";
        
        $params[] = $limit;
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function insertOrUpdateStat($data)
    {
        // Check if record exists
        $sql = "SELECT id FROM {$this->table} 
                WHERE ad_account_id = ? AND campaign_id = ? AND date = ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$data['ad_account_id'], $data['campaign_id'], $data['date']]);
        
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            return $this->update($existing['id'], $data);
        } else {
            // Create new record
            return $this->create($data);
        }
    }
    
    public function getClientPerformanceReport($clientId, $startDate = null, $endDate = null)
    {
        $sql = "SELECT 
                    aa.account_name,
                    aa.platform,
                    c.name as campaign_name,
                    c.campaign_type,
                    SUM(ds.impressions) as total_impressions,
                    SUM(ds.clicks) as total_clicks,
                    SUM(ds.cost) as total_cost,
                    SUM(ds.conversions) as total_conversions,
                    SUM(ds.conversion_value) as total_conversion_value
                FROM {$this->table} ds
                JOIN campaigns c ON ds.campaign_id = c.id
                JOIN ad_accounts aa ON ds.ad_account_id = aa.id
                WHERE aa.client_id = ?";
        
        $params = [$clientId];
        
        if ($startDate) {
            $sql .= " AND ds.date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND ds.date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY ds.campaign_id, ds.ad_account_id
                  ORDER BY aa.platform ASC, aa.account_name ASC, c.name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDateRange()
    {
        $sql = "SELECT MIN(date) as min_date, MAX(date) as max_date FROM {$this->table}";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}