<?php
// 基本設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// 環境変数読み込み
require_once __DIR__ . '/app/utils/Environment.php';

try {
    Environment::load();
    $app_name = Environment::get('APP_NAME', 'Ads Manager');
    $app_env = Environment::get('APP_ENV', 'production');
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
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
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .nav { margin: 20px 0; }
        .nav a { 
            display: inline-block; padding: 10px 20px; margin: 5px; 
            background: #007bff; color: white; text-decoration: none; 
            border-radius: 5px; transition: background 0.2s;
        }
        .nav a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($app_name); ?></h1>
        
        <div class="status success">
            ✅ アプリケーションが正常に起動しました
        </div>
        
        <div class="status info">
            📊 環境: <?php echo htmlspecialchars($app_env); ?><br>
            🕐 現在時刻: <?php echo date('Y-m-d H:i:s'); ?><br>
            🖥️ PHP バージョン: <?php echo PHP_VERSION; ?>
        </div>
        
        <div class="nav">
            <h3>テストページ</h3>
            <a href="test-minimal.php">最小限テスト</a>
            <a href="test-info.php">PHP情報</a>
            <a href="test-env.php">環境変数テスト</a>
            <a href="test-db.php">データベーステスト</a>
        </div>
        
        <div class="nav">
            <h3>アプリケーション機能</h3>
            <a href="clients.php">クライアント管理</a>
            <a href="dashboard.php">ダッシュボード</a>
        </div>
    </div>
</body>
</html>