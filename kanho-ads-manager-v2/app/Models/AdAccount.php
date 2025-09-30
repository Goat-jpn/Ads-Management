<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class AdAccount extends Model
{
    protected $table = 'ad_accounts';
    protected $fillable = [
        'client_id', 'platform', 'account_id', 'account_name', 'currency', 
        'timezone', 'access_token', 'refresh_token', 'token_expires_at',
        'last_sync', 'sync_enabled', 'status'
    ];
    
    protected $hidden = ['access_token', 'refresh_token'];
    
    // プラットフォーム定数
    const PLATFORM_GOOGLE = 'google';
    const PLATFORM_YAHOO = 'yahoo';
    
    // ステータス定数
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive'; 
    const STATUS_SUSPENDED = 'suspended';
    
    public function create($data)
    {
        // デフォルト値を設定
        $data['status'] = $data['status'] ?? self::STATUS_INACTIVE;
        $data['sync_enabled'] = $data['sync_enabled'] ?? 1;
        
        return parent::create($data);
    }
    
    /**
     * クライアント別の広告アカウント一覧を取得
     */
    public function findByClient($clientId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE client_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clientId]);
        
        return $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * プラットフォーム別の広告アカウント一覧を取得
     */
    public function findByPlatform($platform)
    {
        $sql = "SELECT * FROM {$this->table} WHERE platform = ? ORDER BY account_name ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$platform]);
        
        return $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * アクティブな広告アカウント一覧を取得
     */
    public function getActiveAccounts($clientId = null)
    {
        $sql = "SELECT aa.*, c.company_name 
                FROM {$this->table} aa 
                LEFT JOIN clients c ON aa.client_id = c.id 
                WHERE aa.status = ?";
        $params = [self::STATUS_ACTIVE];
        
        if ($clientId) {
            $sql .= " AND aa.client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY aa.account_name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 同期が必要なアカウント一覧を取得
     */
    public function getAccountsNeedingSync($hoursOld = 24)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE sync_enabled = 1 
                AND status = ? 
                AND (last_sync IS NULL OR last_sync < DATE_SUB(NOW(), INTERVAL ? HOUR))
                ORDER BY last_sync ASC";
                
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([self::STATUS_ACTIVE, $hoursOld]);
        
        return $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * アカウント統計情報を取得
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
                
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * アカウントの同期状態を更新
     */
    public function updateSyncStatus($accountId, $status = 'success', $errorMessage = null)
    {
        $data = [
            'last_sync' => date('Y-m-d H:i:s'),
        ];
        
        if ($status === 'error' && $errorMessage) {
            // エラーログ記録用の追加処理（将来実装）
            error_log("Ad Account sync error for ID {$accountId}: {$errorMessage}");
        }
        
        return $this->update($accountId, $data);
    }
    
    /**
     * プラットフォーム別のアカウント数を取得
     */
    public function countByPlatform()
    {
        $sql = "SELECT 
                    platform,
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active
                FROM {$this->table} 
                GROUP BY platform";
                
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['platform']] = [
                'total' => (int)$row['total'],
                'active' => (int)$row['active']
            ];
        }
        
        return $result;
    }
    
    /**
     * アカウント検索
     */
    public function searchAccounts($query, $clientId = null)
    {
        $sql = "SELECT aa.*, c.company_name 
                FROM {$this->table} aa 
                LEFT JOIN clients c ON aa.client_id = c.id 
                WHERE (aa.account_name LIKE ? OR aa.account_id LIKE ? OR c.company_name LIKE ?)";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        
        if ($clientId) {
            $sql .= " AND aa.client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY aa.account_name ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * アクセストークンの暗号化保存
     */
    public function saveEncryptedTokens($accountId, $accessToken, $refreshToken = null, $expiresAt = null)
    {
        // 本番環境では適切な暗号化を実装
        // 現在は開発用に平文で保存（セキュリティ注意）
        
        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $expiresAt
        ];
        
        return $this->update($accountId, $data);
    }
    
    /**
     * 復号化されたアクセストークンを取得
     */
    public function getDecryptedTokens($accountId)
    {
        $account = $this->find($accountId);
        
        if (!$account) {
            return null;
        }
        
        // 本番環境では復号化処理を実装
        return [
            'access_token' => $account['access_token'],
            'refresh_token' => $account['refresh_token'],
            'expires_at' => $account['token_expires_at']
        ];
    }
    
    /**
     * プラットフォーム名の日本語変換
     */
    public static function getPlatformName($platform)
    {
        $names = [
            self::PLATFORM_GOOGLE => 'Google Ads',
            self::PLATFORM_YAHOO => 'Yahoo Ads'
        ];
        
        return $names[$platform] ?? $platform;
    }
    
    /**
     * ステータス名の日本語変換
     */
    public static function getStatusName($status)
    {
        $names = [
            self::STATUS_ACTIVE => 'アクティブ',
            self::STATUS_INACTIVE => '非アクティブ',
            self::STATUS_SUSPENDED => '停止中'
        ];
        
        return $names[$status] ?? $status;
    }
}