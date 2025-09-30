<?php

namespace App\Models;

use PDO;

class Client
{
    private $db;
    protected $table = 'clients';
    protected $fillable = [
        'user_id', 'company_name', 'contact_person', 'email', 'phone', 
        'address', 'tax_number', 'status', 'tags', 'notes'
    ];
    
    protected $hidden = [];
    
    public function __construct()
    {
        $this->db = \Database::getInstance();
    }
    
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->selectOne($sql, [$id]);
    }
    
    public function create($data)
    {
        // デフォルト値を設定
        $data['status'] = $data['status'] ?? 'active';
        $data['user_id'] = $data['user_id'] ?? $_SESSION['user_id'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert($this->table, $data);
    }
    
    public function findByUser($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findByCompanyName($companyName)
    {
        $sql = "SELECT * FROM {$this->table} WHERE company_name LIKE ? ORDER BY created_at DESC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute(["%{$companyName}%"]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveClients($userId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active'";
        $params = [];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY company_name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientStats($clientId)
    {
        // クライアント統計情報を取得（将来的にはad_accountsテーブルと連携）
        $client = $this->find($clientId);
        
        if (!$client) {
            return null;
        }
        
        // 基本統計（現在は仮の値）
        return [
            'client' => $client,
            'ad_accounts_count' => 0,
            'active_campaigns' => 0,
            'total_spend_this_month' => 0.00,
            'last_sync' => null
        ];
    }
    
    public function searchClients($query, $userId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE 
                (company_name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY company_name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientsByStatus($status, $userId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = ?";
        $params = [$status];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($clientId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$status, $clientId]);
    }
    
    public function getRecentClients($limit = 5, $userId = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}