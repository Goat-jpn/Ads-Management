<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup (Localhost Configuration)</h2>";

// localhost設定を直接使用
require_once __DIR__ . '/config-localhost.php';

try {
    // 設定情報表示
    echo "<h3>Configuration Info</h3>";
    echo "Host: " . $_ENV['DB_HOST'] . "<br>";
    echo "Database: " . $_ENV['DB_DATABASE'] . "<br>";
    echo "Username: " . $_ENV['DB_USERNAME'] . "<br><br>";
    
    // データベース接続
    $host = $_ENV['DB_HOST'];
    $database = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $charset = $_ENV['DB_CHARSET'];

    $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ Database connection established<br><br>";
    
    // SQLファイルの読み込み
    $sqlFile = __DIR__ . '/database-setup.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✅ SQL file loaded (" . strlen($sql) . " characters)<br>";
    
    // SQLの実行（複数のステートメントを分割）
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $success_count = 0;
    $error_count = 0;
    $total_count = 0;
    
    echo "<h3>Executing SQL Statements</h3>";
    echo "<div style='max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    
    foreach ($statements as $index => $statement) {
        if (empty($statement) || substr(trim($statement), 0, 2) === '--') {
            continue;
        }
        
        $total_count++;
        
        try {
            $pdo->exec($statement);
            $success_count++;
            echo "✅ Statement " . ($total_count) . " executed successfully<br>";
            
            // 特定のステートメントの詳細を表示
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches);
                if (!empty($matches[1])) {
                    echo "　　📋 Created table: {$matches[1]}<br>";
                }
            } elseif (stripos($statement, 'INSERT') !== false) {
                preg_match('/INSERT.*?INTO.*?`([^`]+)`/i', $statement, $matches);
                if (!empty($matches[1])) {
                    echo "　　📝 Inserted data into: {$matches[1]}<br>";
                }
            }
            
        } catch (PDOException $e) {
            $error_count++;
            echo "❌ Statement " . ($total_count) . " failed: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    
    echo "</div>";
    
    echo "<br><h3>Execution Summary</h3>";
    echo "<div style='padding: 15px; background: #e8f5e8; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "✅ Successfully executed: <strong>{$success_count}/{$total_count}</strong> statements<br>";
    if ($error_count > 0) {
        echo "❌ Errors: <strong>{$error_count}</strong><br>";
    }
    echo "</div>";
    
    // テーブル一覧の表示
    echo "<h3>Created Database Tables</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;'>";
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
                $count = $stmt->fetch()['count'];
                
                // テーブル構造も表示
                $stmt = $pdo->query("DESCRIBE `{$table}`");
                $columns = $stmt->fetchAll();
                
                echo "<div style='background: white; padding: 15px; border: 1px solid #ddd; border-radius: 8px;'>";
                echo "<h4 style='margin-top: 0; color: #007bff;'>📋 {$table}</h4>";
                echo "<p><strong>Records:</strong> {$count}</p>";
                echo "<p><strong>Columns:</strong> " . count($columns) . "</p>";
                
                if ($count > 0) {
                    echo "<div style='font-size: 12px; color: #6c757d;'>";
                    foreach (array_slice($columns, 0, 5) as $col) {
                        echo "• " . $col['Field'] . " (" . $col['Type'] . ")<br>";
                    }
                    if (count($columns) > 5) {
                        echo "• ... and " . (count($columns) - 5) . " more columns<br>";
                    }
                    echo "</div>";
                }
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 8px;'>";
                echo "<h4 style='margin-top: 0;'>📋 {$table}</h4>";
                echo "<p>Error getting table info: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        }
        echo "</div>";
    } else {
        echo "<p>No tables found.</p>";
    }
    
    echo "<br><div style='padding:20px; background:#d4edda; border:1px solid #c3e6cb; border-radius:8px;'>";
    echo "🎉 <strong>Database setup completed successfully!</strong><br><br>";
    echo "<strong>🚀 Ready to Use Application:</strong><br>";
    echo "1. <a href='index-localhost.php' style='color: #007bff;'>Main Application (Localhost Version)</a><br>";
    echo "2. <a href='clients-localhost.php' style='color: #007bff;'>Client Management (Localhost Version)</a><br>";
    echo "3. <a href='dashboard-localhost.php' style='color: #007bff;'>Dashboard (Localhost Version)</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='padding:15px; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "Line: " . $e->getLine();
    echo "</div>";
}
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>