<?php
echo "<h2>MariaDB Connection Test (Direct Config)</h2>";

// 直接設定版のEnvironmentクラスを使用
require_once __DIR__ . '/app/utils/Environment-direct.php';

try {
    Environment::load();
    echo "✅ Environment loaded<br>";
    
    $host = Environment::get('DB_HOST');
    $database = Environment::get('DB_DATABASE');
    $username = Environment::get('DB_USERNAME');
    $password = Environment::get('DB_PASSWORD');
    
    echo "<h3>Connection Details</h3>";
    echo "Host: {$host}<br>";
    echo "Database: {$database}<br>";
    echo "Username: {$username}<br>";
    echo "Password: " . str_repeat('*', strlen($password)) . "<br><br>";
    
    // Test connection
    echo "<h3>Connection Test</h3>";
    
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ Database connection successful<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version, NOW() as current_time");
    $result = $stmt->fetch();
    
    echo "<h3>Database Info</h3>";
    echo "Version: {$result['version']}<br>";
    echo "Current Time: {$result['current_time']}<br>";
    
    // Test if our database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '{$database}' exists<br>";
        
        // Show tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<h4>Existing Tables (" . count($tables) . "):</h4>";
            foreach ($tables as $table) {
                // Get row count for each table
                try {
                    $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
                    $count = $count_stmt->fetch()['count'];
                    echo "📋 {$table} ({$count} records)<br>";
                } catch (Exception $e) {
                    echo "📋 {$table} (count error)<br>";
                }
            }
        } else {
            echo "ℹ️ No tables found - Database is empty<br>";
            echo "<p><strong>Next step:</strong> Run <a href='setup-database-direct.php'>Database Setup</a> to create tables</p>";
        }
        
    } else {
        echo "❌ Database '{$database}' does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Connection Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    
    // Additional debug info
    echo "<br><h3>Debug Information:</h3>";
    echo "PDO Available: " . (class_exists('PDO') ? 'Yes' : 'No') . "<br>";
    echo "MySQL Driver: " . (in_array('mysql', PDO::getAvailableDrivers()) ? 'Yes' : 'No') . "<br>";
}
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>