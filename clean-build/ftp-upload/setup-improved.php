<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

echo "<h2>Database Setup (Improved with Simple Connection)</h2>";

// シンプルなデータベース接続を使用
require_once __DIR__ . '/config/database/Connection-simple.php';

try {
    echo "<h3>Step 1: Connection Test</h3>";
    
    // 接続テストを実行
    $connectionTest = Database::testConnection();
    
    if ($connectionTest['success']) {
        echo "<div class='success'>";
        echo "✅ データベース接続成功<br>";
        echo "テスト値: {$connectionTest['test_value']}<br>";
        echo "現在時刻: {$connectionTest['current_time']}<br>";
        echo "</div>";
    } else {
        throw new Exception($connectionTest['message']);
    }
    
    echo "<h3>Step 2: Table Setup</h3>";
    
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
    echo "<div class='log-area'>";
    
    foreach ($statements as $i => $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            Database::query($statement);
            $success++;
            
            // テーブル作成の場合はテーブル名を表示
            if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✅ Created table: <strong>{$matches[1]}</strong><br>";
            } elseif (preg_match('/INSERT.*?INTO.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✅ Inserted data into: <strong>{$matches[1]}</strong><br>";
            } else {
                echo "✅ Statement " . ($i + 1) . " executed<br>";
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "❌ Error in statement " . ($i + 1) . ": " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    
    echo "</div>";
    
    echo "<h3>Step 3: Verification</h3>";
    echo "<div class='summary'>";
    echo "✅ Successful: <strong>{$success}</strong><br>";
    echo "❌ Errors: <strong>{$errors}</strong><br>";
    echo "</div>";
    
    // テーブル一覧確認
    $tables = Database::select("SHOW TABLES");
    $tableNames = array_column($tables, array_keys($tables[0])[0]);
    
    echo "<h4>Created Tables (" . count($tableNames) . ")</h4>";
    if (count($tableNames) > 0) {
        echo "<div class='tables-grid'>";
        foreach ($tableNames as $table) {
            try {
                $count = Database::selectOne("SELECT COUNT(*) as count FROM `{$table}`");
                echo "<div class='table-card success-card'>";
                echo "<strong>📋 {$table}</strong><br>";
                echo "Records: {$count['count']}";
                echo "</div>";
            } catch (Exception $e) {
                echo "<div class='table-card error-card'>";
                echo "<strong>📋 {$table}</strong><br>";
                echo "Error: " . htmlspecialchars($e->getMessage());
                echo "</div>";
            }
        }
        echo "</div>";
        
        echo "<div class='completion-message'>";
        echo "🎉 <strong>Database Setup Complete!</strong><br><br>";
        echo "<strong>Next Steps:</strong><br>";
        echo "1. <a href='test-connection.php'>Test Database Connection</a><br>";
        echo "2. <a href='clients-simple.php'>Manage Clients (Simple Version)</a><br>";
        echo "3. <a href='dashboard.php'>View Dashboard</a><br>";
        echo "4. <a href='index.php'>Open Main Application</a>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>❌ No tables were created.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Setup Failed:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<div class='help-section'>";
    echo "<h4>Troubleshooting:</h4>";
    echo "1. データベース設定を確認してください<br>";
    echo "2. データベースサーバーが起動していることを確認してください<br>";
    echo "3. データベース権限を確認してください<br>";
    echo "4. <a href='test-connection.php'>接続テストページ</a>で詳細を確認してください";
    echo "</div>";
}
?>

<style>
body { 
    font-family: system-ui, sans-serif; 
    margin: 20px; 
    background: #f8f9fa; 
    line-height: 1.6;
}

h2, h3, h4 { 
    color: #333; 
    margin-top: 30px;
    margin-bottom: 15px;
}

a { 
    color: #007bff; 
    text-decoration: none; 
    font-weight: 500;
}

a:hover { 
    text-decoration: underline; 
}

.success {
    background: #d4edda; 
    color: #155724; 
    padding: 15px; 
    border-radius: 8px; 
    border: 1px solid #c3e6cb;
    margin: 15px 0;
}

.error {
    background: #f8d7da; 
    color: #721c24; 
    padding: 15px; 
    border-radius: 8px; 
    border: 1px solid #f5c6cb;
    margin: 15px 0;
}

.log-area {
    max-height: 300px; 
    overflow-y: auto; 
    background: white; 
    padding: 15px; 
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 15px 0;
}

.summary {
    background: #e2f3ff; 
    color: #004085; 
    padding: 15px; 
    border-radius: 8px; 
    border: 1px solid #b3d7ff;
    margin: 15px 0;
}

.tables-grid {
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
    gap: 15px;
    margin: 20px 0;
}

.table-card {
    padding: 15px; 
    border-radius: 8px; 
    border: 1px solid;
    font-size: 14px;
}

.success-card {
    background: white; 
    border-color: #28a745;
    border-left: 4px solid #28a745;
}

.error-card {
    background: #f8d7da; 
    border-color: #dc3545;
    border-left: 4px solid #dc3545;
}

.completion-message {
    padding: 20px; 
    background: #d4edda; 
    border: 1px solid #c3e6cb; 
    border-radius: 8px;
    margin: 20px 0;
    font-size: 16px;
}

.help-section {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
    color: #856404;
}
</style>