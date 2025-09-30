<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class AdAccount extends Model
{
    protected $table = 'ad_accounts';
    protected $fillable = [
        'client_id', 'platform', 'account_name', 'account_id', 
        'access_token', 'refresh_token', 'token_expires_at',
        'currency', 'timezone', 'status', 'last_sync_at'
    ];
    
    protected $hidden = ['access_token', 'refresh_token'];
    
    public function getByClient($clientId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE client_id = ? ORDER BY platform ASC, account_name ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clientId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByPlatform($platform)
    {
        $sql = "SELECT aa.*, c.name as client_name 
                FROM {$this->table} aa
                JOIN clients c ON aa.client_id = c.id
                WHERE aa.platform = ? 
                ORDER BY c.name ASC, aa.account_name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$platform]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveAccounts()
    {
        $sql = "SELECT aa.*, c.name as client_name 
                FROM {$this->table} aa
                JOIN clients c ON aa.client_id = c.id
                WHERE aa.status = 'active' 
                ORDER BY c.name ASC, aa.platform ASC, aa.account_name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAccountsNeedingSync($hours = 24)
    {
        $syncThreshold = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'active' 
                AND (last_sync_at IS NULL OR last_sync_at < ?)
                ORDER BY last_sync_at ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$syncThreshold]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateTokens($accountId, $accessToken, $refreshToken = null, $expiresAt = null)
    {
        $sql = "UPDATE {$this->table} SET 
                access_token = ?, 
                refresh_token = ?, 
                token_expires_at = ?,
                updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([
            $accessToken,
            $refreshToken,
            $expiresAt,
            $accountId
        ]);
    }
    
    public function updateLastSyncAt($accountId)
    {
        $sql = "UPDATE {$this->table} SET last_sync_at = NOW(), updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$accountId]);
    }
    
    public function getAccountWithCampaigns($accountId)
    {
        $sql = "SELECT 
                    aa.*,
                    c.name as client_name,
                    c.email as client_email
                FROM {$this->table} aa
                JOIN clients c ON aa.client_id = c.id
                WHERE aa.id = ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$accountId]);
        
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            return null;
        }
        
        // Get campaigns
        $campaignsSql = "SELECT * FROM campaigns WHERE ad_account_id = ? ORDER BY name ASC";
        $campaignsStmt = $this->db->getConnection()->prepare($campaignsSql);
        $campaignsStmt->execute([$accountId]);
        
        $account['campaigns'] = $campaignsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $account;
    }
    
    public function getTokensForSync($accountId)
    {
        $sql = "SELECT access_token, refresh_token, token_expires_at FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$accountId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function isTokenExpired($accountId)
    {
        $sql = "SELECT token_expires_at FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$accountId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['token_expires_at']) {
            return true; // Assume expired if no expiration date
        }
        
        return strtotime($result['token_expires_at']) <= time();
    }
    
    public function getAccountStats()
    {
        $sql = "SELECT 
                    platform,
                    status,
                    COUNT(*) as count
                FROM {$this->table} 
                GROUP BY platform, status
                ORDER BY platform ASC, status ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($accountId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$status, $accountId]);
    }
}