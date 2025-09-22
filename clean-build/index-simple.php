<?php
// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// シンプルなデータベース接続を使用
require_once __DIR__ . '/config/database/Connection-simple.php';

$app_name = 'Kanho Ads Manager';
$connection_status = 'unknown';
$db_tables_count = 0;
$clients_count = 0;

// データベース接続状態確認
try {
    $connectionTest = Database::testConnection();
    if ($connectionTest['success']) {
        $connection_status = 'success';
        
        // テーブル数確認
        $tables = Database::select("SHOW TABLES");
        $db_tables_count = count($tables);
        
        // クライアント数確認
        if ($db_tables_count > 0) {
            try {
                $clientsData = Database::selectOne("SELECT COUNT(*) as count FROM clients");
                $clients_count = $clientsData['count'];
            } catch (Exception $e) {
                // テーブルが存在しない場合
                $clients_count = 0;
            }
        }
    } else {
        $connection_status = 'error';
    }
} catch (Exception $e) {
    $connection_status = 'error';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_name); ?></title>
    <style>
        body { 
            font-family: system-ui, sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .header h1 { 
            color: #333; 
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .header p {
            color: #6c757d;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .status-section {
            margin-bottom: 40px;
        }
        
        .status-card {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .status-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .status-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .status-icon {
            font-size: 24px;
            margin-right: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .actions-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .action-category {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }
        
        .action-category h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 1.1rem;
        }
        
        .action-link {
            display: block;
            padding: 12px 16px;
            margin-bottom: 8px;
            background: white;
            color: #495057;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .action-link:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .action-link.primary {
            background: #007bff;
            color: white;
            border-color: #007bff;
            font-weight: 500;
        }
        
        .action-link.primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }
        
        .action-link.success {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .action-link.success:hover {
            background: #1e7e34;
            border-color: #1e7e34;
        }
        
        .action-link.warning {
            background: #ffc107;
            color: #212529;
            border-color: #ffc107;
        }
        
        .action-link.warning:hover {
            background: #e0a800;
            border-color: #e0a800;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($app_name); ?></h1>
            <p>広告コスト管理とフィー請求システム</p>
        </div>
        
        <div class="status-section">
            <?php if ($connection_status === 'success'): ?>
                <div class="status-card status-success">
                    <div class="status-icon">✅</div>
                    <div>
                        <strong>データベース接続</strong><br>
                        正常に動作しています
                    </div>
                </div>
            <?php else: ?>
                <div class="status-card status-error">
                    <div class="status-icon">❌</div>
                    <div>
                        <strong>データベース接続エラー</strong><br>
                        設定を確認してください
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($db_tables_count === 0): ?>
                <div class="status-card status-warning">
                    <div class="status-icon">⚠️</div>
                    <div>
                        <strong>データベースセットアップが必要</strong><br>
                        テーブルが作成されていません
                    </div>
                </div>
            <?php else: ?>
                <div class="status-card status-success">
                    <div class="status-icon">📊</div>
                    <div>
                        <strong>データベーステーブル</strong><br>
                        <?php echo $db_tables_count; ?>個のテーブルが利用可能です
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $clients_count; ?></div>
                <div class="stat-label">登録クライアント数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $db_tables_count; ?></div>
                <div class="stat-label">データベーステーブル</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $connection_status === 'success' ? '正常' : 'エラー'; ?></div>
                <div class="stat-label">システム状態</div>
            </div>
        </div>
        
        <div class="actions-section">
            <h3>🚀 アプリケーションメニュー</h3>
            
            <div class="actions-grid">
                <!-- セットアップ & 診断 -->
                <div class="action-category">
                    <h4>🔧 セットアップ & 診断</h4>
                    <?php if ($db_tables_count === 0): ?>
                        <a href="setup-improved.php" class="action-link warning">📋 データベースセットアップ (推奨)</a>
                    <?php endif; ?>
                    <a href="test-connection.php" class="action-link">🔍 データベース接続テスト</a>
                    <a href="setup-simple.php" class="action-link">⚙️ 旧セットアップ (参考)</a>
                </div>
                
                <!-- メイン機能 -->
                <div class="action-category">
                    <h4>📊 メイン機能</h4>
                    <?php if ($connection_status === 'success' && $db_tables_count > 0): ?>
                        <a href="clients-simple.php" class="action-link primary">👥 クライアント管理</a>
                        <a href="dashboard.php" class="action-link primary">📈 ダッシュボード</a>
                    <?php else: ?>
                        <a href="#" class="action-link" style="opacity: 0.5; cursor: not-allowed;">👥 クライアント管理 (要セットアップ)</a>
                        <a href="#" class="action-link" style="opacity: 0.5; cursor: not-allowed;">📈 ダッシュボード (要セットアップ)</a>
                    <?php endif; ?>
                </div>
                
                <!-- 開発者ツール -->
                <div class="action-category">
                    <h4>🛠️ 開発者ツール</h4>
                    <a href="clients.php" class="action-link">👥 クライアント管理 (旧版)</a>
                    <a href="index.php" class="action-link">🏠 メインページ (旧版)</a>
                    <a href="test-db.php" class="action-link">🧪 DB テスト (旧版)</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Kanho Ads Manager - Version 1.0 | PHP <?php echo PHP_VERSION; ?></p>
            <p>Localhost Database Configuration | <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>