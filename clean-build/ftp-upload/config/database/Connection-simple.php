<?php

/**
 * シンプルなデータベース接続クラス
 * 環境ファイル読み込みの問題を回避するため、直接設定を使用
 */
class Database 
{
    private static $instance = null;
    private $connection;

    // データベース設定（直接定義）
    private static $config = [
        'host' => 'localhost',
        'database' => 'kanho_adsmanager',
        'username' => 'kanho_adsmanager',
        'password' => 'Kanho20200701',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_TIMEOUT => 60,
            PDO::MYSQL_ATTR_RECONNECT => true
        ]
    ];

    private function __construct() 
    {
        $host = self::$config['host'];
        $database = self::$config['database'];
        $username = self::$config['username'];
        $password = self::$config['password'];
        $charset = self::$config['charset'];

        $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";
        
        try {
            $this->connection = new PDO($dsn, $username, $password, self::$config['options']);
            
            // 接続テストクエリを実行
            $this->connection->query("SELECT 1");
            
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
        
        // 接続が切れている場合は再接続
        try {
            self::$instance->connection->query("SELECT 1");
        } catch (PDOException $e) {
            self::$instance = new self();
        }
        
        return self::$instance->connection;
    }

    public static function query($sql, $params = []) 
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function select($sql, $params = []) 
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function selectOne($sql, $params = []) 
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function insert($table, $data) 
    {
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        $sql = "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})";
        
        self::query($sql, $data);
        return self::getInstance()->lastInsertId();
    }

    public static function update($table, $data, $where, $whereParams = []) 
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "`{$column}` = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
        
        return self::query($sql, array_merge($data, $whereParams));
    }

    public static function delete($table, $where, $params = []) 
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return self::query($sql, $params);
    }
    
    /**
     * 接続テスト用メソッド
     */
    public static function testConnection()
    {
        try {
            $pdo = self::getInstance();
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