<?php

class Database 
{
    private static $instance = null;
    private $connection;

    private function __construct() 
    {
        // 直接設定版のEnvironmentクラスを使用
        require_once __DIR__ . '/../../app/utils/Environment-direct.php';
        Environment::load();

        $host = Environment::get('DB_HOST');
        $database = Environment::get('DB_DATABASE');
        $username = Environment::get('DB_USERNAME');
        $password = Environment::get('DB_PASSWORD');
        $charset = Environment::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
        ];

        try {
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() 
    {
        if (self::$instance === null) {
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
}