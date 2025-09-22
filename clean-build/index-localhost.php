<?php
// 基本設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// localhost設定を使用
require_once __DIR__ . '/config-localhost.php';

try {
    $app_name = $_ENV['APP_NAME'];
    $app_env = $_ENV['APP_ENV'];
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_name); ?> - Localhost Version</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            margin: 0; padding: 20px; background: #f5f5f5; 
        }
        .container { 
            max-width: 1200px; margin: 0 auto; background: white; 
            padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 30px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .nav { margin: 20px 0; }
        .nav a { 
            display: inline-block; padding: 12px 24px; margin: 8px; 
            background: #007bff; color: white; text-decoration: none; 
            border-radius: 6px; transition: background 0.2s;
            font-weight: 500;
        }
        .nav a:hover { background: #0056b3; }
        .nav a.setup { background: #28a745; }
        .nav a.setup:hover { background: #1e7e34; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; }
        .card h3 { margin-top: 0; color: #495057; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 <?php echo htmlspecialchars($app_name); ?></h1>
        
        <div class="status success">
            ✅ アプリケーションが正常に起動しました (Localhost版)
        </div>
        
        <div class="status info">
            📊 環境: <?php echo htmlspecialchars($app_env); ?><br>
            🕐 現在時刻: <?php echo date('Y-m-d H:i:s'); ?><br>
            🖥️ PHP バージョン: <?php echo PHP_VERSION; ?><br>
            🔧 接続方式: localhost (<?php echo $_ENV['CONFIG_LOADED']; ?>)
        </div>
        
        <div class="status warning">
            ⚠️ <strong>重要:</strong> このアプリケーションはlocalhost接続用に設定されています。<br>
            Xbizサーバーの共有ホスティング環境に最適化されています。
        </div>
        
        <div class="nav">
            <h3>🔧 セットアップ & テスト</h3>
            <a href="test-localhost-config.php">設定確認</a>
            <a href="setup-simple.php" class="setup">データベースセットアップ</a>
        </div>
        
        <div class="grid">
            <div class="card">
                <h3>📋 管理機能</h3>
                <p>クライアント情報や広告アカウントの管理</p>
                <a href="clients.php">クライアント管理</a>
            </div>
            
            <div class="card">
                <h3>📊 分析機能</h3>
                <p>パフォーマンス分析とレポート表示</p>
                <a href="dashboard.php">ダッシュボード</a>
            </div>
            
            <div class="card">
                <h3>🔍 診断機能</h3>
                <p>システム状態とデータベース接続確認</p>
                <a href="test-db-localhost-fixed.php">接続テスト</a>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px;">
            <h3 style="margin: 0 0 15px 0; color: #1565c0;">📱 システム情報</h3>
            <div class="grid">
                <div>
                    <strong>データベースホスト:</strong><br>
                    <?php echo $_ENV['DB_HOST']; ?>
                </div>
                <div>
                    <strong>データベース名:</strong><br>
                    <?php echo $_ENV['DB_DATABASE']; ?>
                </div>
                <div>
                    <strong>設定方式:</strong><br>
                    <?php echo $_ENV['CONFIG_LOADED']; ?>
                </div>
                <div>
                    <strong>アプリケーションURL:</strong><br>
                    <?php echo $_ENV['APP_URL']; ?>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; font-size: 14px; color: #6c757d;">
            <strong>Version Info:</strong> Localhost Optimized v1.6 | 
            <strong>Server:</strong> Xbiz Shared Hosting | 
            <strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>