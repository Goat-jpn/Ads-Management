<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class AdAccount extends Model
{
    protected $table = 'ad_accounts';
    protected $fillable = [
        'user_id', 'client_id', 'platform', 'account_name', 'customer_id', 
        'account_id', 'currency', 'timezone', 'status', 'sync_enabled',
        'last_sync_at', 'access_token', 'refresh_token'
    ];
    
    // プラットフォーム定数
    const PLATFORM_GOOGLE = 'google';
    const PLATFORM_YAHOO = 'yahoo';
    const PLATFORM_META = 'meta';
    const PLATFORM_TWITTER = 'twitter';
    
    // ステータス定数
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    
    /**
     * 全ての広告アカウントを取得
     */
    public function getAll($userId = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * IDで広告アカウントを取得（エイリアス - 基底クラスのfind()を使用）
     */
    public function getById($id)
    {
        return $this->find($id);
    }
    
    /**
     * 広告アカウントを作成
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::create($data);
    }
    
    /**
     * 広告アカウントを更新
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::update($id, $data);
    }
    
    /**
     * 広告アカウントを削除（エイリアス）
     */
    public function delete($id)
    {
        return parent::delete($id);
    }
    
    /**
     * プラットフォーム別の広告アカウント数を取得
     */
    public function getCountByPlatform($userId = null)
    {
        $sql = "SELECT platform, COUNT(*) as count 
                FROM {$this->table}";
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " GROUP BY platform";
        
        return $this->query($sql, $params);
    }
    
    /**
     * プラットフォーム別統計（エイリアス）
     */
    public function countByPlatform($userId = null)
    {
        return $this->getCountByPlatform($userId);
    }
    
    /**
     * アクティブなアカウント数を取得
     */
    public function getActiveCount($userId = null)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE status = 'active'";
        $params = [];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $result = $this->query($sql, $params);
        return $result ? $result[0]['count'] : 0;
    }
    
    /**
     * 顧客IDで広告アカウントを検索
     */
    public function getByCustomerId($customerId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE customer_id = ?";
        $result = $this->query($sql, [$customerId]);
        return $result ? $result[0] : null;
    }
    
    /**
     * ユーザーの広告アカウントを取得（クライアント情報も含む）
     */
    public function getByUserWithClient($userId)
    {
        $sql = "SELECT aa.*, c.company_name as client_name
                FROM {$this->table} aa
                LEFT JOIN clients c ON aa.client_id = c.id
                WHERE aa.user_id = ?
                ORDER BY aa.created_at DESC";
        
        return $this->db->select($sql, [$userId]);
    }
    
    /**
     * 検証ルール
     */
    public static function getValidationRules()
    {
        return [
            'platform' => 'required|in:google_ads,yahoo_ads,meta_ads,twitter_ads',
            'account_name' => 'required|max:255',
            'customer_id' => 'required|max:255',
            'status' => 'in:active,inactive,suspended',
        ];
    }
    
    /**
     * ページネーション機能
     */
    public function paginate($page = 1, $perPage = 10, $orderBy = 'created_at', $order = 'DESC')
    {
        $offset = ($page - 1) * $perPage;
        
        // 総件数を取得
        $total = $this->count();
        
        // データを取得
        $sql = "SELECT aa.*, c.company_name as client_name
                FROM {$this->table} aa
                LEFT JOIN clients c ON aa.client_id = c.id
                ORDER BY aa.{$orderBy} {$order}
                LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->query($sql);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage),
            'has_more' => ($offset + $perPage) < $total
        ];
    }
    
    /**
     * アカウント検索
     */
    public function searchAccounts($search, $clientId = null)
    {
        $sql = "SELECT aa.*, c.company_name as client_name
                FROM {$this->table} aa
                LEFT JOIN clients c ON aa.client_id = c.id
                WHERE (aa.account_name LIKE ? OR aa.customer_id LIKE ? OR c.company_name LIKE ?)";
        
        $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        
        if ($clientId) {
            $sql .= " AND aa.client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY aa.created_at DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * プラットフォーム名を日本語で取得
     */
    public static function getPlatformName($platform)
    {
        $platforms = [
            'google_ads' => 'Google Ads',
            'yahoo_ads' => 'Yahoo! 広告',
            'meta_ads' => 'Meta Ads',
            'twitter_ads' => 'Twitter Ads'
        ];
        
        return $platforms[$platform] ?? $platform;
    }
    
    /**
     * ステータス名を日本語で取得
     */
    public static function getStatusName($status)
    {
        $statuses = [
            'active' => 'アクティブ',
            'inactive' => '非アクティブ',
            'suspended' => '停止中'
        ];
        
        return $statuses[$status] ?? $status;
    }
    
    /**
     * アカウント統計を取得
     */
    public function getAccountStats()
    {
        $sql = "SELECT 
                    platform,
                    status,
                    COUNT(*) as count
                FROM {$this->table}
                GROUP BY platform, status
                ORDER BY platform, status";
        
        return $this->db->select($sql);
    }
    
    /**
     * 同期が必要なアカウントを取得
     */
    public function getAccountsNeedingSync($hours = 24)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE (last_sync_at IS NULL OR last_sync_at < DATE_SUB(NOW(), INTERVAL ? HOUR))
                AND status = 'active'
                ORDER BY last_sync_at ASC";
        
        return $this->query($sql, [$hours]);
    }
    
    /**
     * 同期ステータスを更新
     */
    public function updateSyncStatus($accountId, $status, $errorMessage = null)
    {
        $updateData = [
            'last_sync_at' => date('Y-m-d H:i:s'),
            'sync_status' => $status
        ];
        
        if ($errorMessage) {
            $updateData['sync_error'] = $errorMessage;
        }
        
        return $this->update($accountId, $updateData);
    }
    
    /**
     * 同期エラーをクリア
     */
    public function clearSyncError($accountId)
    {
        return $this->update($accountId, [
            'sync_error' => null,
            'sync_status' => 'success'
        ]);
    }
}