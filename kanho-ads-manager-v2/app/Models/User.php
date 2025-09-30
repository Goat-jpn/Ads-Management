<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active', 
        'email_verified_at', 'remember_token', 'reset_token', 'reset_expires_at'
    ];
    
    protected $hidden = ['password', 'remember_token', 'reset_token'];
    
    public function create($data)
    {
        // Hash password before saving
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return parent::create($data);
    }
    
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }
    
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    public function generateResetToken($email)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "UPDATE {$this->table} SET reset_token = ?, reset_expires_at = ? WHERE email = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$token, $expiresAt, $email]);
        
        return $token;
    }
    
    public function verifyResetToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE reset_token = ? AND reset_expires_at > NOW() LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$token]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function clearResetToken($userId)
    {
        $sql = "UPDATE {$this->table} SET reset_token = NULL, reset_expires_at = NULL WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$userId]);
    }
    
    public function markEmailAsVerified($userId)
    {
        $sql = "UPDATE {$this->table} SET email_verified_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$userId]);
    }
    
    public function getActiveUsers()
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateLastLoginAt($userId)
    {
        $sql = "UPDATE {$this->table} SET last_login_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$userId]);
    }
}