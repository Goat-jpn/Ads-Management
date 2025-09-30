<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Database Setup (Localhost)</h2>";

// localhost設定を直接使用
require_once __DIR__ . '/config-localhost.php';

try {
    echo "<h3>Step 1: Connection Test</h3>";
    $host = $_ENV['DB_HOST'];
    $database = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    
    echo "Host: {$host}<br>";
    echo "Database: {$database}<br>";
    echo "Username: {$username}<br><br>";

    // データベース接続（まずデータベース指定なし）
    $dsn = "mysql:host={$host};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ MySQL connection successful<br>";
    
    // データベース存在確認
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '{$database}' exists<br>";
        
        // データベースを選択
        $pdo->exec("USE `{$database}`");
        echo "✅ Database selected<br><br>";
        
    } else {
        echo "❌ Database '{$database}' does not exist<br>";
        echo "<strong>Available databases:</strong><br>";
        $stmt = $pdo->query("SHOW DATABASES");
        $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($dbs as $db) {
            if (!in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                echo "- {$db}<br>";
            }
        }
        throw new Exception("Target database does not exist");
    }
    
    echo "<h3>Step 2: Table Creation</h3>";
    
    // SQLファイル読み込み
    $sqlFile = __DIR__ . '/database-setup-simple.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✅ SQL file loaded (" . number_format(strlen($sql)) . " characters)<br>";
    
    // SQLを個別のステートメントに分割
    $statements = preg_split('/;\s*$/m', $sql);
    $statements = array_filter(array_map('trim', $statements));
    
    $success = 0;
    $errors = 0;
    
    echo "<h4>Executing Statements</h4>";
    echo "<div style='max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
    
    foreach ($statements as $i => $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success++;
            
            // テーブル作成の場合はテーブル名を表示
            if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✅ Created table: <strong>{$matches[1]}</strong><br>";
            } elseif (preg_match('/INSERT.*?INTO.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✅ Inserted data into: <strong>{$matches[1]}</strong><br>";
            } else {
                echo "✅ Statement " . ($i + 1) . " executed<br>";
            }
            
        } catch (PDOException $e) {
            $errors++;
            echo "❌ Error in statement " . ($i + 1) . ": " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    
    echo "</div>";
    
    echo "<h3>Step 3: Verification</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "✅ Successful: <strong>{$success}</strong><br>";
    echo "❌ Errors: <strong>{$errors}</strong><br>";
    echo "</div><br>";
    
    // テーブル一覧確認
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h4>Created Tables (" . count($tables) . ")</h4>";
    if (count($tables) > 0) {
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;'>";
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
                $count = $stmt->fetch()['count'];
                echo "<div style='background: white; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
                echo "<strong>📋 {$table}</strong><br>";
                echo "Records: {$count}";
                echo "</div>";
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
                echo "<strong>📋 {$table}</strong><br>";
                echo "Error: " . htmlspecialchars($e->getMessage());
                echo "</div>";
            }
        }
        echo "</div>";
        
        echo "<br><div style='padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px;'>";
        echo "🎉 <strong>Database Setup Complete!</strong><br><br>";
        echo "<strong>Next Steps:</strong><br>";
        echo "1. <a href='test-localhost-config.php'>Test Configuration</a><br>";
        echo "2. <a href='index.php'>Open Main Application</a><br>";
        echo "3. <a href='clients.php'>Manage Clients</a><br>";
        echo "4. <a href='dashboard.php'>View Dashboard</a>";
        echo "</div>";
        
    } else {
        echo "<p>❌ No tables were created.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "❌ <strong>Setup Failed:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>