<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password_hash', 'role', 
        'email_verified', 'email_verification_token', 'password_reset_token', 'password_reset_expires'
    ];
    
    protected $hidden = ['password_hash', 'email_verification_token', 'password_reset_token'];
    
    public function create($data)
    {
        // Hash password before saving
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']); // Remove the plain password
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
        
        $sql = "UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    public function generateResetToken($email)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "UPDATE {$this->table} SET password_reset_token = ?, password_reset_expires = ? WHERE email = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$token, $expiresAt, $email]);
        
        return $token;
    }
    
    public function verifyResetToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE password_reset_token = ? AND password_reset_expires > NOW() LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$token]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function clearResetToken($userId)
    {
        $sql = "UPDATE {$this->table} SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$userId]);
    }
    
    public function markEmailAsVerified($userId)
    {
        $sql = "UPDATE {$this->table} SET email_verified = 1, email_verification_token = NULL WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$userId]);
    }
    
    public function getActiveUsers()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function updateLastLoginAt($userId)
    {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$userId]);
    }
}