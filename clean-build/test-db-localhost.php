<?php
echo "<h2>Localhost Database Connection Test</h2>";
echo "<p><strong>Theory:</strong> Xbizサーバーではlocalhost接続が必要な可能性があります</p>";

// 直接設定版のEnvironmentクラスを使用
require_once __DIR__ . '/app/utils/Environment-direct.php';

try {
    Environment::load();
    
    $database = Environment::get('DB_DATABASE');
    $username = Environment::get('DB_USERNAME');
    $password = Environment::get('DB_PASSWORD');
    
    // 複数のホスト設定をテスト
    $hosts_to_test = [
        'localhost',
        '127.0.0.1',
        'sv301.xbiz.ne.jp',
        'mysql.xbiz.ne.jp'
    ];
    
    echo "<h3>Testing Different Hosts</h3>";
    
    foreach ($hosts_to_test as $host) {
        echo "<h4>Testing: {$host}</h4>";
        
        // Step 1: Host connectivity
        if ($host !== 'localhost') {
            $connection = @fsockopen($host, 3306, $errno, $errstr, 5);
            if ($connection) {
                fclose($connection);
                echo "✅ Host reachable<br>";
            } else {
                echo "❌ Host unreachable: {$errstr}<br>";
                continue;
            }
        }
        
        // Step 2: MySQL connection test
        try {
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            echo "✅ MySQL connection successful with {$host}<br>";
            
            // Get server info
            $stmt = $pdo->query("SELECT VERSION() as version, USER() as current_user, DATABASE() as current_db");
            $info = $stmt->fetch();
            echo "Server Version: {$info['version']}<br>";
            echo "Connected as: {$info['current_user']}<br>";
            echo "Current DB: " . ($info['current_db'] ?: 'none') . "<br>";
            
            // Test database selection
            echo "<br><strong>Testing database selection:</strong><br>";
            try {
                $pdo->exec("USE `{$database}`");
                echo "✅ Successfully selected database '{$database}'<br>";
                
                // Test a simple query
                $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '{$database}'");
                $result = $stmt->fetch();
                echo "Tables in database: {$result['table_count']}<br>";
                
                echo "<br><div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                echo "🎉 <strong>SUCCESS!</strong> Working connection found with host: <strong>{$host}</strong><br>";
                echo "You can proceed with database setup using this host.";
                echo "</div><br>";
                
                $pdo = null;
                break; // Success found, no need to test other hosts
                
            } catch (PDOException $e) {
                echo "❌ Database selection failed: " . $e->getMessage() . "<br>";
                if (strpos($e->getMessage(), 'Unknown database') !== false) {
                    echo "💡 Database '{$database}' needs to be created<br>";
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ MySQL connection failed with {$host}: " . $e->getMessage() . "<br>";
            
            // Provide specific suggestions
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                echo "💡 Suggestion: Check credentials or host access permissions<br>";
            } elseif (strpos($e->getMessage(), 'gone away') !== false) {
                echo "💡 Suggestion: Server timeout or connection limit<br>";
            }
        }
        
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "<br>";
}

echo "<h3>📋 Next Steps</h3>";
echo "<ul>";
echo "<li>If localhost works: Update config-direct.php to use localhost</li>";
echo "<li>If database doesn't exist: Contact hosting provider to create it</li>";
echo "<li>If credentials fail: Verify username/password with hosting provider</li>";
echo "</ul>";
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4 { color: #333; }
hr { margin: 20px 0; border: 1px solid #ddd; }
ul { background: #e3f2fd; padding: 15px; border-radius: 5px; }
</style>