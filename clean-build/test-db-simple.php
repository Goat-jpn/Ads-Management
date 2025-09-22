<?php
echo "<h2>Simple MariaDB Connection Test</h2>";

// 直接設定版のEnvironmentクラスを使用
require_once __DIR__ . '/app/utils/Environment-direct.php';

try {
    Environment::load();
    
    $host = Environment::get('DB_HOST');
    $database = Environment::get('DB_DATABASE');
    $username = Environment::get('DB_USERNAME');
    $password = Environment::get('DB_PASSWORD');
    
    echo "<h3>Testing Step by Step</h3>";
    
    // Step 1: Test host connectivity
    echo "<strong>Step 1:</strong> Testing host connectivity...<br>";
    $connection = @fsockopen($host, 3306, $errno, $errstr, 10);
    if ($connection) {
        fclose($connection);
        echo "✅ Host is reachable<br><br>";
    } else {
        echo "❌ Host unreachable: {$errstr}<br><br>";
        throw new Exception("Cannot connect to database host");
    }
    
    // Step 2: Test MySQL connection (without database)
    echo "<strong>Step 2:</strong> Testing MySQL service...<br>";
    try {
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 15,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        echo "✅ MySQL service accessible<br>";
        
        // Get server info
        $stmt = $pdo->query("SELECT VERSION() as version, USER() as current_user");
        $info = $stmt->fetch();
        echo "Server Version: {$info['version']}<br>";
        echo "Connected as: {$info['current_user']}<br><br>";
        
    } catch (PDOException $e) {
        echo "❌ MySQL connection failed: " . $e->getMessage() . "<br><br>";
        throw $e;
    }
    
    // Step 3: Check if database exists
    echo "<strong>Step 3:</strong> Checking database existence...<br>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '{$database}' exists<br><br>";
    } else {
        echo "❌ Database '{$database}' does not exist<br>";
        
        // Show available databases
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<strong>Available databases:</strong><br>";
        foreach ($databases as $db) {
            echo "- {$db}<br>";
        }
        echo "<br>";
    }
    
    // Step 4: Test connection with database
    echo "<strong>Step 4:</strong> Testing connection to specific database...<br>";
    try {
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 15,
        ];
        
        $pdo_db = new PDO($dsn, $username, $password, $options);
        echo "✅ Successfully connected to database '{$database}'<br>";
        
        // Test a simple query
        $stmt = $pdo_db->query("SELECT DATABASE() as current_db, NOW() as server_time");
        $result = $stmt->fetch();
        echo "Current Database: {$result['current_db']}<br>";
        echo "Server Time: {$result['server_time']}<br>";
        
        echo "<br><div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "🎉 <strong>Database connection successful!</strong><br>";
        echo "You can now proceed to <a href='setup-database-direct.php'>Database Setup</a>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
        
        // Provide specific guidance based on error
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<br><strong>💡 Suggestion:</strong> Check username and password<br>";
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            echo "<br><strong>💡 Suggestion:</strong> Database '{$database}' needs to be created<br>";
        } elseif (strpos($e->getMessage(), 'gone away') !== false) {
            echo "<br><strong>💡 Suggestion:</strong> Server timeout or connection limit reached<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "❌ <strong>Connection Test Failed</strong><br>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<br><strong>Possible causes:</strong><br>";
    echo "1. MariaDB service is not running<br>";
    echo "2. Incorrect hostname or port<br>";
    echo "3. Username/password incorrect<br>";
    echo "4. Database does not exist<br>";
    echo "5. Firewall blocking connection<br>";
    echo "</div>";
}
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>