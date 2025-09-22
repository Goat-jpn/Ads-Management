<?php
echo "<h2>Localhost Configuration Test</h2>";

// localhost設定を使用
require_once __DIR__ . '/config-localhost.php';
require_once __DIR__ . '/app/utils/Environment-direct.php';

try {
    // 設定値を表示
    echo "<h3>Configuration Values (Localhost)</h3>";
    echo "<strong>DB_HOST:</strong> " . $_ENV['DB_HOST'] . "<br>";
    echo "<strong>DB_DATABASE:</strong> " . $_ENV['DB_DATABASE'] . "<br>";
    echo "<strong>DB_USERNAME:</strong> " . $_ENV['DB_USERNAME'] . "<br>";
    echo "<strong>CONFIG_LOADED:</strong> " . $_ENV['CONFIG_LOADED'] . "<br><br>";
    
    // データベース接続テスト
    $host = $_ENV['DB_HOST'];
    $database = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    
    echo "<h3>Database Connection Test (Localhost)</h3>";
    
    // 接続テスト
    $dsn = "mysql:host={$host};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ MySQL connection successful with localhost<br>";
    
    // サーバー情報取得
    $stmt = $pdo->query("SELECT VERSION() as version, USER() as current_user");
    $info = $stmt->fetch();
    echo "Server Version: {$info['version']}<br>";
    echo "Connected as: {$info['current_user']}<br>";
    
    // データベース確認
    echo "<br><h4>Database Check</h4>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '{$database}' exists<br>";
        
        // データベース選択テスト
        $pdo->exec("USE `{$database}`");
        echo "✅ Database selected successfully<br>";
        
        // テーブル確認
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "<br>";
        
        if (count($tables) > 0) {
            echo "<h5>Existing Tables:</h5>";
            foreach ($tables as $table) {
                echo "📋 {$table}<br>";
            }
        } else {
            echo "ℹ️ No tables found - Database is empty<br>";
        }
        
        echo "<br><div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "🎉 <strong>Localhost connection successful!</strong><br>";
        echo "Database exists and is accessible. You can proceed with table setup.";
        echo "</div>";
        
    } else {
        echo "❌ Database '{$database}' does not exist<br>";
        echo "<p><strong>Solution:</strong> Contact hosting provider to create database or use existing database name.</p>";
        
        // 利用可能なデータベース一覧
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<h5>Available Databases:</h5>";
        foreach ($databases as $db) {
            if (!in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                echo "📋 {$db}<br>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "<br>";
    echo "<br><h4>Troubleshooting:</h4>";
    echo "<ul>";
    echo "<li>Verify database server is running</li>";
    echo "<li>Check if credentials are correct</li>";
    echo "<li>Ensure database user has proper permissions</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4, h5 { color: #333; }
ul { background: #fff3cd; padding: 15px; border-radius: 5px; }
</style>