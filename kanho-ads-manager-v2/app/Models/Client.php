<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Client extends Model
{
    protected $table = 'clients';
    protected $fillable = [
        'name', 'email', 'phone', 'company', 'address', 
        'city', 'state', 'zip_code', 'country', 'website',
        'contact_person', 'notes', 'tags', 'status', 'contract_start_date', 'contract_end_date'
    ];
    
    public function getActiveClients()
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY name ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientsByStatus($status)
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = ? ORDER BY name ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$status]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function searchClients($query)
    {
        $searchTerm = "%{$query}%";
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE ? OR email LIKE ? OR company LIKE ? OR tags LIKE ?
                ORDER BY name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientStats()
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM {$this->table} 
                GROUP BY status";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientsWithUpcomingContracts($days = 30)
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE contract_end_date <= ? AND contract_end_date >= CURDATE() 
                ORDER BY contract_end_date ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$futureDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientWithAdAccounts($clientId)
    {
        $sql = "SELECT 
                    c.*,
                    aa.id as ad_account_id,
                    aa.platform,
                    aa.account_name,
                    aa.account_id as platform_account_id,
                    aa.status as account_status
                FROM {$this->table} c
                LEFT JOIN ad_accounts aa ON c.id = aa.client_id
                WHERE c.id = ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clientId]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return null;
        }
        
        // Format the results
        $client = [
            'id' => $results[0]['id'],
            'name' => $results[0]['name'],
            'email' => $results[0]['email'],
            'phone' => $results[0]['phone'],
            'company' => $results[0]['company'],
            'address' => $results[0]['address'],
            'city' => $results[0]['city'],
            'state' => $results[0]['state'],
            'zip_code' => $results[0]['zip_code'],
            'country' => $results[0]['country'],
            'website' => $results[0]['website'],
            'contact_person' => $results[0]['contact_person'],
            'notes' => $results[0]['notes'],
            'tags' => $results[0]['tags'],
            'status' => $results[0]['status'],
            'contract_start_date' => $results[0]['contract_start_date'],
            'contract_end_date' => $results[0]['contract_end_date'],
            'created_at' => $results[0]['created_at'],
            'updated_at' => $results[0]['updated_at'],
            'ad_accounts' => []
        ];
        
        foreach ($results as $result) {
            if ($result['ad_account_id']) {
                $client['ad_accounts'][] = [
                    'id' => $result['ad_account_id'],
                    'platform' => $result['platform'],
                    'account_name' => $result['account_name'],
                    'platform_account_id' => $result['platform_account_id'],
                    'status' => $result['account_status']
                ];
            }
        }
        
        return $client;
    }
    
    public function updateStatus($clientId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$status, $clientId]);
    }
    
    public function addTags($clientId, $newTags)
    {
        $client = $this->find($clientId);
        if (!$client) {
            return false;
        }
        
        $existingTags = $client['tags'] ? explode(',', $client['tags']) : [];
        $tagsToAdd = is_array($newTags) ? $newTags : [$newTags];
        
        $allTags = array_unique(array_merge($existingTags, $tagsToAdd));
        $tagsString = implode(',', array_filter($allTags));
        
        $sql = "UPDATE {$this->table} SET tags = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$tagsString, $clientId]);
    }
}