<?php
// 基本設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// localhost設定を使用（修正版）
require_once __DIR__ . '/config-localhost.php';

try {
    $app_name = $_ENV['APP_NAME'] ?? 'Kanho Ads Manager';
    $app_env = $_ENV['APP_ENV'] ?? 'production';
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
        .status { padding: 12px; margin: 15px 0; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .nav { margin: 25px 0; }
        .nav h3 { color: #495057; margin-bottom: 15px; }
        .nav a { 
            display: inline-block; padding: 12px 24px; margin: 8px; 
            background: #007bff; color: white; text-decoration: none; 
            border-radius: 6px; transition: background 0.2s; font-weight: 500;
        }
        .nav a:hover { background: #0056b3; }
        .nav a.setup { background: #28a745; }
        .nav a.setup:hover { background: #1e7e34; }
        .grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 20px; margin: 20px 0; 
        }
        .card { 
            background: #f8f9fa; padding: 20px; border-radius: 8px; 
            border: 1px solid #e9ecef; 
        }
        .card h4 { margin-top: 0; color: #495057; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 <?php echo htmlspecialchars($app_name); ?></h1>
        
        <div class="status success">
            ✅ アプリケーションが正常に起動しました
        </div>
        
        <div class="status info">
            📊 環境: <?php echo htmlspecialchars($app_env); ?><br>
            🕐 現在時刻: <?php echo date('Y-m-d H:i:s'); ?><br>
            🖥️ PHP バージョン: <?php echo PHP_VERSION; ?><br>
            🔧 設定: <?php echo $_ENV['CONFIG_LOADED'] ?? 'localhost'; ?>
        </div>
        
        <div class="status warning">
            ⚠️ <strong>初回セットアップが必要です</strong><br>
            データベーステーブルを作成してからアプリケーション機能をご利用ください。
        </div>
        
        <div class="nav">
            <h3>🔧 セットアップ & 診断</h3>
            <a href="test-localhost-config.php">設定確認</a>
            <a href="test-db-localhost-fixed.php">データベース接続テスト</a>
            <a href="setup-simple.php" class="setup">データベーステーブル作成</a>
        </div>
        
        <div class="grid">
            <div class="card">
                <h4>📋 クライアント管理</h4>
                <p>顧客情報と広告アカウントの管理</p>
                <a href="clients.php" style="color: #007bff;">クライアント管理画面へ</a>
            </div>
            
            <div class="card">
                <h4>📊 ダッシュボード</h4>
                <p>パフォーマンス分析とレポート表示</p>
                <a href="dashboard.php" style="color: #007bff;">ダッシュボードを開く</a>
            </div>
            
            <div class="card">
                <h4>🎯 専用バージョン</h4>
                <p>Localhost最適化バージョン</p>
                <a href="index-localhost.php" style="color: #007bff;">Localhost版を開く</a>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 6px; font-size: 14px;">
            <strong>💡 推奨手順:</strong><br>
            1. <a href="setup-simple.php">データベーステーブル作成</a> を実行<br>
            2. <a href="clients.php">クライアント管理</a> でデータ登録<br>
            3. <a href="dashboard.php">ダッシュボード</a> で結果確認
        </div>
    </div>
</body>
</html>