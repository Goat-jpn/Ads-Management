<?php
echo "<h2>Advanced MariaDB Connection Diagnostics</h2>";

// 直接設定版のEnvironmentクラスを使用
require_once __DIR__ . '/app/utils/Environment-direct.php';

try {
    Environment::load();
    echo "✅ Environment loaded<br>";
    
    $host = Environment::get('DB_HOST');
    $database = Environment::get('DB_DATABASE');
    $username = Environment::get('DB_USERNAME');
    $password = Environment::get('DB_PASSWORD');
    $port = Environment::get('DB_PORT', '3306');
    
    echo "<h3>Connection Details</h3>";
    echo "Host: {$host}<br>";
    echo "Port: {$port}<br>";
    echo "Database: {$database}<br>";
    echo "Username: {$username}<br>";
    echo "Password: " . str_repeat('*', strlen($password)) . "<br><br>";
    
    // Test 1: Basic connectivity check
    echo "<h3>Test 1: Basic Host Connectivity</h3>";
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($connection) {
        fclose($connection);
        echo "✅ Host {$host}:{$port} is reachable<br>";
    } else {
        echo "❌ Cannot reach {$host}:{$port} - Error: {$errno} - {$errstr}<br>";
    }
    
    // Test 2: Try connection without specifying database
    echo "<h3>Test 2: MySQL Connection (No Database)</h3>";
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        echo "✅ MySQL connection successful (without database)<br>";
        
        // Test MySQL version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        echo "MySQL Version: {$result['version']}<br>";
        
        // List databases
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h4>Available Databases:</h4>";
        $target_found = false;
        foreach ($databases as $db) {
            $marker = ($db === $database) ? "✅ (TARGET)" : "📋";
            echo "{$marker} {$db}<br>";
            if ($db === $database) $target_found = true;
        }
        
        if (!$target_found) {
            echo "<br>❌ Target database '{$database}' not found!<br>";
        }
        
        $pdo = null; // Close connection
        
    } catch (PDOException $e) {
        echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>";
    }
    
    // Test 3: Try with different connection parameters
    echo "<h3>Test 3: Database Connection with Different Parameters</h3>";
    
    $test_configs = [
        'default' => [
            'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ],
        'with_timeout' => [
            'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
                PDO::MYSQL_ATTR_CONNECT_TIMEOUT => 10,
                PDO::MYSQL_ATTR_READ_TIMEOUT => 10,
            ]
        ],
        'with_ssl_disabled' => [
            'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]
        ]
    ];
    
    foreach ($test_configs as $config_name => $config) {
        echo "<h4>Configuration: {$config_name}</h4>";
        try {
            $start_time = microtime(true);
            $pdo = new PDO($config['dsn'], $username, $password, $config['options']);
            $end_time = microtime(true);
            $connection_time = round(($end_time - $start_time) * 1000, 2);
            
            echo "✅ Connection successful (took {$connection_time}ms)<br>";
            
            // Simple test query
            $stmt = $pdo->query("SELECT 1 as test, NOW() as current_time");
            $result = $stmt->fetch();
            echo "Test Query: {$result['test']}, Time: {$result['current_time']}<br>";
            
            $pdo = null; // Close connection
            break; // If successful, no need to test other configs
            
        } catch (PDOException $e) {
            echo "❌ Failed: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
    // Test 4: Alternative host formats
    echo "<h3>Test 4: Alternative Connection Methods</h3>";
    
    $alternative_hosts = [
        $host,
        $host . '.xbiz.ne.jp',
        'localhost', 
        '127.0.0.1'
    ];
    
    foreach ($alternative_hosts as $alt_host) {
        echo "<h4>Testing host: {$alt_host}</h4>";
        try {
            $dsn = "mysql:host={$alt_host};port={$port};charset=utf8mb4";
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            echo "✅ Connection successful with {$alt_host}<br>";
            $pdo = null;
            
        } catch (PDOException $e) {
            echo "❌ Failed with {$alt_host}: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<h3>💡 Troubleshooting Tips</h3>";
echo "<ul>";
echo "<li><strong>Server Gone Away:</strong> Usually indicates timeout or connection limits</li>";
echo "<li><strong>Check with hosting provider:</strong> Verify MariaDB service is running</li>";
echo "<li><strong>Firewall/Security:</strong> Ensure database port is accessible</li>";
echo "<li><strong>Credentials:</strong> Verify username/password are correct</li>";
echo "<li><strong>Database existence:</strong> Confirm database '{$database}' exists</li>";
echo "</ul>";
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4 { color: #333; }
ul { background: #e3f2fd; padding: 15px; border-radius: 5px; }
</style>