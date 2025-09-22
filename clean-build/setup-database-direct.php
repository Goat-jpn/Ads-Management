<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup (Direct Config Version)</h2>";

require_once __DIR__ . '/app/utils/Environment-direct.php';
require_once __DIR__ . '/config/database/Connection-direct.php';

try {
    Environment::load();
    echo "✅ Environment loaded<br>";
    
    // データベース接続テスト
    $pdo = Database::getInstance();
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
    echo "<div style='max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    
    foreach ($statements as $index => $statement) {
        if (empty($statement) || substr(trim($statement), 0, 2) === '--') {
            continue;
        }
        
        $total_count++;
        
        try {
            $pdo->exec($statement);
            $success_count++;
            echo "✅ Statement " . ($total_count) . " executed successfully<br>";
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
    echo "<h3>Database Tables</h3>";
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
    echo "🎉 <strong>Database setup completed!</strong><br><br>";
    echo "<strong>Next Steps:</strong><br>";
    echo "1. <a href='index-direct.php'>Test Main Application</a><br>";
    echo "2. <a href='clients-direct.php'>Test Client Management</a><br>";
    echo "3. <a href='dashboard-direct.php'>Test Dashboard</a>";
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