<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Campaign extends Model
{
    protected $table = 'campaigns';
    protected $fillable = [
        'ad_account_id', 'campaign_id', 'name', 'status', 'campaign_type',
        'budget_amount', 'budget_type', 'start_date', 'end_date',
        'targeting_info', 'bid_strategy', 'last_updated'
    ];
    
    public function getByAdAccount($adAccountId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE ad_account_id = ? ORDER BY name ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$adAccountId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveCampaigns()
    {
        $sql = "SELECT c.*, aa.account_name, cl.name as client_name 
                FROM {$this->table} c
                JOIN ad_accounts aa ON c.ad_account_id = aa.id
                JOIN clients cl ON aa.client_id = cl.id
                WHERE c.status = 'active' 
                ORDER BY cl.name ASC, aa.account_name ASC, c.name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCampaignsByStatus($status)
    {
        $sql = "SELECT c.*, aa.account_name, cl.name as client_name 
                FROM {$this->table} c
                JOIN ad_accounts aa ON c.ad_account_id = aa.id
                JOIN clients cl ON aa.client_id = cl.id
                WHERE c.status = ? 
                ORDER BY cl.name ASC, aa.account_name ASC, c.name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$status]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCampaignWithStats($campaignId, $startDate = null, $endDate = null)
    {
        // Get campaign details
        $campaignSql = "SELECT c.*, aa.account_name, aa.platform, cl.name as client_name 
                        FROM {$this->table} c
                        JOIN ad_accounts aa ON c.ad_account_id = aa.id
                        JOIN clients cl ON aa.client_id = cl.id
                        WHERE c.id = ?";
        
        $stmt = $this->db->getConnection()->prepare($campaignSql);
        $stmt->execute([$campaignId]);
        
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            return null;
        }
        
        // Get daily stats
        $statsSql = "SELECT * FROM daily_stats WHERE campaign_id = ?";
        $params = [$campaignId];
        
        if ($startDate) {
            $statsSql .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $statsSql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $statsSql .= " ORDER BY date DESC";
        
        $statsStmt = $this->db->getConnection()->prepare($statsSql);
        $statsStmt->execute($params);
        
        $campaign['daily_stats'] = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary stats
        $campaign['summary_stats'] = $this->calculateSummaryStats($campaign['daily_stats']);
        
        return $campaign;
    }
    
    private function calculateSummaryStats($dailyStats)
    {
        if (empty($dailyStats)) {
            return [
                'total_impressions' => 0,
                'total_clicks' => 0,
                'total_cost' => 0,
                'total_conversions' => 0,
                'avg_ctr' => 0,
                'avg_cpc' => 0,
                'avg_cpm' => 0,
                'conversion_rate' => 0
            ];
        }
        
        $totals = array_reduce($dailyStats, function($carry, $stat) {
            return [
                'impressions' => $carry['impressions'] + $stat['impressions'],
                'clicks' => $carry['clicks'] + $stat['clicks'],
                'cost' => $carry['cost'] + $stat['cost'],
                'conversions' => $carry['conversions'] + $stat['conversions']
            ];
        }, ['impressions' => 0, 'clicks' => 0, 'cost' => 0, 'conversions' => 0]);
        
        $ctr = $totals['impressions'] > 0 ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;
        $cpc = $totals['clicks'] > 0 ? $totals['cost'] / $totals['clicks'] : 0;
        $cpm = $totals['impressions'] > 0 ? ($totals['cost'] / $totals['impressions']) * 1000 : 0;
        $conversionRate = $totals['clicks'] > 0 ? ($totals['conversions'] / $totals['clicks']) * 100 : 0;
        
        return [
            'total_impressions' => $totals['impressions'],
            'total_clicks' => $totals['clicks'],
            'total_cost' => $totals['cost'],
            'total_conversions' => $totals['conversions'],
            'avg_ctr' => round($ctr, 2),
            'avg_cpc' => round($cpc, 2),
            'avg_cpm' => round($cpm, 2),
            'conversion_rate' => round($conversionRate, 2)
        ];
    }
    
    public function getCampaignsNeedingUpdate($hours = 1)
    {
        $updateThreshold = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $sql = "SELECT c.*, aa.platform 
                FROM {$this->table} c
                JOIN ad_accounts aa ON c.ad_account_id = aa.id
                WHERE c.status = 'active' 
                AND (c.last_updated IS NULL OR c.last_updated < ?)
                ORDER BY c.last_updated ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$updateThreshold]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateLastUpdated($campaignId)
    {
        $sql = "UPDATE {$this->table} SET last_updated = NOW(), updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$campaignId]);
    }
    
    public function getCampaignStats()
    {
        $sql = "SELECT 
                    aa.platform,
                    c.status,
                    COUNT(*) as count,
                    SUM(c.budget_amount) as total_budget
                FROM {$this->table} c
                JOIN ad_accounts aa ON c.ad_account_id = aa.id
                GROUP BY aa.platform, c.status
                ORDER BY aa.platform ASC, c.status ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($campaignId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$status, $campaignId]);
    }
    
    public function searchCampaigns($query)
    {
        $searchTerm = "%{$query}%";
        $sql = "SELECT c.*, aa.account_name, aa.platform, cl.name as client_name 
                FROM {$this->table} c
                JOIN ad_accounts aa ON c.ad_account_id = aa.id
                JOIN clients cl ON aa.client_id = cl.id
                WHERE c.name LIKE ? OR c.campaign_type LIKE ?
                ORDER BY cl.name ASC, aa.account_name ASC, c.name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}