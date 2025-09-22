<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup</h2>";

require_once __DIR__ . '/app/utils/Environment.php';
require_once __DIR__ . '/config/database/Connection.php';

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
    $total_count = count($statements);
    
    echo "<h3>Executing SQL Statements</h3>";
    
    foreach ($statements as $index => $statement) {
        if (empty($statement) || substr(trim($statement), 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success_count++;
            echo "✅ Statement " . ($index + 1) . " executed successfully<br>";
        } catch (PDOException $e) {
            echo "❌ Statement " . ($index + 1) . " failed: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h3>Summary</h3>";
    echo "✅ Successfully executed: {$success_count}/{$total_count} statements<br>";
    
    // テーブル一覧の表示
    echo "<h3>Created Tables</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
        $count = $stmt->fetch()['count'];
        echo "📋 {$table} ({$count} records)<br>";
    }
    
    echo "<br><div style='padding:15px; background:#d4edda; border:1px solid #c3e6cb; border-radius:5px;'>";
    echo "🎉 <strong>Database setup completed successfully!</strong><br>";
    echo "You can now use the application with the created tables and sample data.";
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
h2, h3 { color: #333; }
</style>