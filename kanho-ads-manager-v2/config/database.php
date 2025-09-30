<?php

class Database 
{
    private static $instance = null;
    private $pdo;
    private $config;
    
    private function __construct() 
    {
        $this->config = require __DIR__ . '/app.php';
        $this->connect();
    }
    
    private function connect()
    {
        $dbConfig = $this->config['database']['connections']['mysql'];
        
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database'],
            $dbConfig['charset']
        );
        
        try {
            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
            
            // 接続テスト
            $this->pdo->query("SELECT 1");
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function getConnection()
    {
        // 接続が切れている場合は再接続
        try {
            $this->pdo->query("SELECT 1");
        } catch (PDOException $e) {
            $this->connect();
        }
        
        return $this->pdo;
    }
    
    public function query($sql, $params = [])
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function select($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function selectOne($sql, $params = [])
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }
    
    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        $sql = "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->getConnection()->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "`{$column}` = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
        
        $stmt = $this->query($sql, array_merge($data, $whereParams));
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commit()
    {
        return $this->getConnection()->commit();
    }
    
    public function rollback()
    {
        return $this->getConnection()->rollback();
    }
    
    public function testConnection()
    {
        try {
            $pdo = $this->getConnection();
            $result = $pdo->query("SELECT 1 as test, NOW() as current_time")->fetch();
            return [
                'success' => true,
                'test_value' => $result['test'],
                'current_time' => $result['current_time'],
                'message' => '接続成功'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}