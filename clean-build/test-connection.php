<?php
// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// シンプルなデータベース接続を使用
require_once __DIR__ . '/config/database/Connection-simple.php';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>データベース接続テスト</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>データベース接続テスト</h1>
        
        <h3>1. 基本接続テスト</h3>
        <?php
        try {
            $testResult = Database::testConnection();
            if ($testResult['success']) {
                echo '<div class="success">✅ 基本接続成功<br>';
                echo 'テスト値: ' . $testResult['test_value'] . '<br>';
                echo '現在時刻: ' . $testResult['current_time'] . '</div>';
            } else {
                echo '<div class="error">❌ 基本接続失敗: ' . $testResult['message'] . '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ 基本接続エラー: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <h3>2. テーブル存在確認</h3>
        <?php
        try {
            $tables = Database::select("SHOW TABLES");
            if (empty($tables)) {
                echo '<div class="error">❌ テーブルが存在しません</div>';
                echo '<div class="info">💡 データベースセットアップが必要です。<br><a href="setup-simple.php">setup-simple.php</a> を実行してください。</div>';
            } else {
                echo '<div class="success">✅ テーブル確認成功 (' . count($tables) . '個のテーブル)</div>';
                echo '<pre>';
                foreach ($tables as $table) {
                    echo '- ' . array_values($table)[0] . "\n";
                }
                echo '</pre>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ テーブル確認エラー: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <h3>3. クライアントテーブル確認</h3>
        <?php
        try {
            $clientsCount = Database::selectOne("SELECT COUNT(*) as count FROM clients");
            echo '<div class="success">✅ クライアントテーブル読み取り成功<br>';
            echo '登録済みクライアント数: ' . $clientsCount['count'] . '件</div>';
            
            if ($clientsCount['count'] > 0) {
                $sampleClients = Database::select("SELECT id, name, status FROM clients LIMIT 3");
                echo '<h4>サンプルデータ:</h4><pre>';
                foreach ($sampleClients as $client) {
                    echo "ID: {$client['id']}, 名前: {$client['name']}, ステータス: {$client['status']}\n";
                }
                echo '</pre>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ クライアントテーブルエラー: ' . $e->getMessage() . '</div>';
            echo '<div class="info">💡 テーブルが存在しない可能性があります。<a href="setup-simple.php">setup-simple.php</a> を実行してください。</div>';
        }
        ?>
        
        <h3>4. 書き込みテスト</h3>
        <?php
        try {
            // テスト用の一意な名前を生成
            $testName = 'テスト接続_' . date('Y-m-d_H-i-s');
            
            $insertId = Database::insert('clients', [
                'name' => $testName,
                'email' => 'test@example.com',
                'status' => 'active'
            ]);
            
            echo '<div class="success">✅ 書き込みテスト成功<br>';
            echo '挿入されたID: ' . $insertId . '</div>';
            
            // 挿入したデータを削除
            Database::delete('clients', 'id = :id', ['id' => $insertId]);
            echo '<div class="info">🗑️ テストデータを削除しました</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ 書き込みテストエラー: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px;">
            <a href="clients-simple.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">クライアント管理画面へ</a>
            <a href="setup-simple.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">データベースセットアップ</a>
        </div>
    </div>
</body>
</html>