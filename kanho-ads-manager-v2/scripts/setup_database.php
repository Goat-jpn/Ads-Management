<?php
/**
 * データベースセットアップスクリプト
 * テーブル作成とマイグレーションを実行
 */

// 環境変数を読み込む
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $_ENV[$name] = $value;
        }
    }
}

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'kanho_ads_manager_v2';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

echo "=== データベースセットアップ開始 ===\n";
echo "データベース: {$dbName}\n";
echo "ホスト: {$dbHost}:{$dbPort}\n";
echo "ユーザー: {$dbUser}\n\n";

try {
    // データベースなしで接続
    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // データベースを作成（存在しない場合）
    echo "データベースを作成中...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    echo "✅ データベース作成完了\n\n";

    // マイグレーションファイルを実行
    $migrationDir = __DIR__ . '/../database/migrations';
    $migrations = glob($migrationDir . '/*.sql');
    sort($migrations);

    echo "=== マイグレーション実行 ===\n";
    foreach ($migrations as $migration) {
        $filename = basename($migration);
        echo "実行中: {$filename}...";
        
        $sql = file_get_contents($migration);
        if (empty(trim($sql))) {
            echo " スキップ（空ファイル）\n";
            continue;
        }

        try {
            // セミコロンで分割して実行
            $statements = explode(';', $sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            echo " ✅ 完了\n";
        } catch (Exception $e) {
            echo " ⚠️ エラー: " . $e->getMessage() . "\n";
            // 重複テーブルエラーは無視
            if (strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'already exists') !== false) {
                echo "    (テーブルが既に存在するため無視)\n";
            }
        }
    }

    // テーブル確認
    echo "\n=== テーブル確認 ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "✅ {$table}\n";
        
        // カラム数を確認
        $stmt = $pdo->query("SELECT COUNT(*) as col_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = '{$table}'");
        $result = $stmt->fetch();
        echo "   カラム数: {$result['col_count']}\n";
    }

    echo "\n=== セットアップ完了 ===\n";
    echo "✅ データベースセットアップが正常に完了しました！\n";

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    exit(1);
}