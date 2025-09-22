<?php

namespace Config\Database;

use PDO;
use PDOException;

class Connection
{
    private static $instance = null;
    private static $config = array();

    /**
     * データベース設定を初期化
     */
    public static function initialize($config)
    {
        self::$config = $config;
    }

    /**
     * PDOインスタンスを取得
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = self::createConnection();
        }
        
        return self::$instance;
    }

    /**
     * 新しいPDO接続を作成
     */
    private static function createConnection()
    {
        if (empty(self::$config)) {
            throw new PDOException('データベース設定が初期化されていません');
        }

        // MariaDB/MySQL用DSN
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            self::$config['host'],
            self::$config['port'],
            self::$config['database']
        );

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        );

        try {
            $pdo = new PDO($dsn, self::$config['username'], self::$config['password'], $options);
            
            // MariaDB/MySQL設定
            $pdo->exec("SET time_zone = '+09:00'");
            $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            
            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException('MariaDBデータベース接続に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 接続をリセット
     */
    public static function reset()
    {
        self::$instance = null;
    }

    /**
     * トランザクション実行
     */
    public static function transaction($callback)
    {
        $pdo = self::getInstance();
        
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}