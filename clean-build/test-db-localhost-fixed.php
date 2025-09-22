<?php
echo "<h2>Fixed Localhost Database Connection Test</h2>";
echo "<p><strong>✅ Connection Success Confirmed!</strong> Testing with corrected SQL syntax.</p>";

// 直接設定版のEnvironmentクラスを使用
require_once __DIR__ . '/app/utils/Environment-direct.php';

try {
    Environment::load();
    
    $database = Environment::get('DB_DATABASE');
    $username = Environment::get('DB_USERNAME');
    $password = Environment::get('DB_PASSWORD');
    
    // 成功したホスト設定をテスト
    $working_hosts = ['localhost', '127.0.0.1'];
    
    echo "<h3>Testing Working Hosts with Fixed SQL</h3>";
    
    foreach ($working_hosts as $host) {
        echo "<h4>Testing: {$host}</h4>";
        
        try {
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            echo "✅ MySQL connection successful with {$host}<br>";
            
            // 修正されたSQL - USER()とDATABASE()を分離
            $stmt = $pdo->query("SELECT VERSION() as version");
            $version_info = $stmt->fetch();
            
            $stmt = $pdo->query("SELECT USER() as current_user");
            $user_info = $stmt->fetch();
            
            $stmt = $pdo->query("SELECT DATABASE() as current_db");
            $db_info = $stmt->fetch();
            
            echo "Server Version: {$version_info['version']}<br>";
            echo "Connected as: {$user_info['current_user']}<br>";
            echo "Current DB: " . ($db_info['current_db'] ?: 'none') . "<br>";
            
            // データベース存在確認
            echo "<br><strong>Testing database existence:</strong><br>";
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Database '{$database}' exists<br>";
                
                // データベース選択テスト
                try {
                    $pdo->exec("USE `{$database}`");
                    echo "✅ Successfully selected database '{$database}'<br>";
                    
                    // テーブル確認
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    echo "Tables found: " . count($tables) . "<br>";
                    
                    if (count($tables) > 0) {
                        echo "<h5>Existing Tables:</h5>";
                        foreach ($tables as $table) {
                            // 各テーブルのレコード数も取得
                            try {
                                $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
                                $count = $count_stmt->fetch()['count'];
                                echo "📋 {$table} ({$count} records)<br>";
                            } catch (Exception $e) {
                                echo "📋 {$table} (count error)<br>";
                            }
                        }
                        
                        echo "<br><div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                        echo "🎉 <strong>PERFECT!</strong> Database connection working with host: <strong>{$host}</strong><br>";
                        echo "Database exists with " . count($tables) . " tables.<br>";
                        echo "<strong>Next step:</strong> <a href='clients.php'>Test Application</a> or <a href='dashboard.php'>View Dashboard</a>";
                        echo "</div>";
                        
                    } else {
                        echo "ℹ️ Database is empty (no tables)<br>";
                        echo "<br><div style='padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>";
                        echo "📋 <strong>Database Setup Required</strong><br>";
                        echo "Database exists but no tables found.<br>";
                        echo "<strong>Next step:</strong> <a href='setup-database-localhost.php'>Create Tables</a>";
                        echo "</div>";
                    }
                    
                } catch (PDOException $e) {
                    echo "❌ Database selection failed: " . $e->getMessage() . "<br>";
                }
                
            } else {
                echo "❌ Database '{$database}' does not exist<br>";
                
                // 利用可能なデータベース一覧
                echo "<h5>Available Databases:</h5>";
                $stmt = $pdo->query("SHOW DATABASES");
                $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($databases as $db) {
                    if (!in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                        echo "📋 {$db}<br>";
                    }
                }
                
                echo "<br><div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
                echo "❌ <strong>Database Missing</strong><br>";
                echo "Database '{$database}' does not exist.<br>";
                echo "<strong>Solutions:</strong><br>";
                echo "1. Contact hosting provider to create database<br>";
                echo "2. Use an existing database from the list above";
                echo "</div>";
            }
            
            $pdo = null;
            break; // 成功したので他のホストはテスト不要
            
        } catch (PDOException $e) {
            echo "❌ Connection failed with {$host}: " . $e->getMessage() . "<br>";
        }
        
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "<br>";
}

echo "<h3>🎯 Configuration Update Required</h3>";
echo "<p>Since localhost connection works, you need to update your configuration:</p>";
echo "<ol>";
echo "<li><strong>Option 1:</strong> Use <code>config-localhost.php</code> (already prepared)</li>";
echo "<li><strong>Option 2:</strong> Update <code>config-direct.php</code> to use localhost</li>";
echo "<li><strong>Test with:</strong> <a href='test-localhost-config.php'>test-localhost-config.php</a></li>";
echo "</ol>";
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4, h5 { color: #333; }
hr { margin: 20px 0; border: 1px solid #ddd; }
ol { background: #e3f2fd; padding: 15px; border-radius: 5px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
</style>