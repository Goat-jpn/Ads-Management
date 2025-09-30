<?php

namespace App\Controllers;

/**
 * テスト用コントローラー
 */
class TestController
{
    public function database()
    {
        // データベース接続テスト
        require_once base_path('config/database.php');
        
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>データベース接続テスト - <?= h(config('app.name')) ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { background: #f8f9fa; }
                .container { margin-top: 50px; }
                .test-card { margin-bottom: 20px; }
                .success { color: #28a745; }
                .error { color: #dc3545; }
                .info { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0; }
                pre { background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1 class="text-center mb-5">🚀 Kanho Ads Manager v2.0</h1>
                <h2 class="text-center mb-4">データベース接続テスト</h2>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        
                        <!-- 設定情報 -->
                        <div class="card test-card">
                            <div class="card-header">
                                <h5><i class="fas fa-cog"></i> システム設定</h5>
                            </div>
                            <div class="card-body">
                                <div class="info">
                                    <strong>アプリケーション名:</strong> <?= h(config('app.name')) ?><br>
                                    <strong>環境:</strong> <?= h(config('app.env')) ?><br>
                                    <strong>デバッグモード:</strong> <?= config('app.debug') ? '有効' : '無効' ?><br>
                                    <strong>PHP バージョン:</strong> <?= PHP_VERSION ?><br>
                                    <strong>タイムゾーン:</strong> <?= date_default_timezone_get() ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- データベース接続テスト -->
                        <div class="card test-card">
                            <div class="card-header">
                                <h5><i class="fas fa-database"></i> データベース接続テスト</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                try {
                                    $db = \Database::getInstance();
                                    $result = $db->testConnection();
                                    
                                    if ($result['success']) {
                                        echo '<div class="alert alert-success">';
                                        echo '<strong>✅ 接続成功!</strong><br>';
                                        echo 'テスト値: ' . $result['test_value'] . '<br>';
                                        echo '現在時刻: ' . $result['current_time'];
                                        echo '</div>';
                                        
                                        // データベース情報取得
                                        $dbInfo = $db->selectOne("
                                            SELECT 
                                                VERSION() as version,
                                                DATABASE() as current_db,
                                                USER() as current_user
                                        ");
                                        
                                        echo '<div class="info">';
                                        echo '<strong>データベース情報:</strong><br>';
                                        echo 'バージョン: ' . h($dbInfo['version']) . '<br>';
                                        echo 'データベース: ' . h($dbInfo['current_db']) . '<br>';
                                        echo 'ユーザー: ' . h($dbInfo['current_user']);
                                        echo '</div>';
                                        
                                    } else {
                                        echo '<div class="alert alert-danger">';
                                        echo '<strong>❌ 接続失敗</strong><br>';
                                        echo 'エラー: ' . h($result['message']);
                                        echo '</div>';
                                    }
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger">';
                                    echo '<strong>❌ 接続エラー</strong><br>';
                                    echo 'エラー: ' . h($e->getMessage());
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- ファイルシステムテスト -->
                        <div class="card test-card">
                            <div class="card-header">
                                <h5><i class="fas fa-folder"></i> ファイルシステムテスト</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $directories = [
                                    'storage/logs' => storage_path('logs'),
                                    'storage/cache' => storage_path('cache'),
                                    'storage/sessions' => storage_path('sessions'),
                                    'public/uploads' => public_path('uploads')
                                ];
                                
                                $allWritable = true;
                                foreach ($directories as $name => $path) {
                                    $exists = is_dir($path);
                                    $writable = is_writable($path);
                                    
                                    if (!$exists) {
                                        mkdir($path, 0755, true);
                                        $exists = is_dir($path);
                                        $writable = is_writable($path);
                                    }
                                    
                                    $status = ($exists && $writable) ? 'success' : 'error';
                                    $icon = ($exists && $writable) ? '✅' : '❌';
                                    
                                    echo "<div class=\"{$status}\">";
                                    echo "{$icon} {$name}: ";
                                    echo $exists ? '存在' : '存在しない';
                                    echo ' / ';
                                    echo $writable ? '書き込み可' : '書き込み不可';
                                    echo "</div>";
                                    
                                    if (!$exists || !$writable) {
                                        $allWritable = false;
                                    }
                                }
                                
                                if ($allWritable) {
                                    echo '<div class="alert alert-success mt-3">';
                                    echo '<strong>✅ すべてのディレクトリが正常です</strong>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-warning mt-3">';
                                    echo '<strong>⚠️ 一部のディレクトリに問題があります</strong>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Composer依存関係 -->
                        <div class="card test-card">
                            <div class="card-header">
                                <h5><i class="fas fa-box"></i> Composer依存関係</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $composerFile = base_path('composer.json');
                                $vendorDir = base_path('vendor');
                                
                                if (file_exists($composerFile)) {
                                    echo '<div class="success">✅ composer.json が存在します</div>';
                                    
                                    if (is_dir($vendorDir)) {
                                        echo '<div class="success">✅ vendor ディレクトリが存在します</div>';
                                        
                                        // 主要ライブラリの確認
                                        $libraries = [
                                            'firebase/php-jwt' => 'Firebase\JWT\JWT',
                                            'vlucas/phpdotenv' => 'Dotenv\Dotenv',
                                            'guzzlehttp/guzzle' => 'GuzzleHttp\Client'
                                        ];
                                        
                                        foreach ($libraries as $package => $class) {
                                            if (class_exists($class)) {
                                                echo "<div class=\"success\">✅ {$package} が利用可能</div>";
                                            } else {
                                                echo "<div class=\"error\">❌ {$package} が見つかりません</div>";
                                            }
                                        }
                                        
                                    } else {
                                        echo '<div class="error">❌ vendor ディレクトリが存在しません</div>';
                                        echo '<div class="alert alert-info mt-2">';
                                        echo 'composer install を実行してください';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="error">❌ composer.json が見つかりません</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- ナビゲーション -->
                        <div class="card">
                            <div class="card-body text-center">
                                <h5>🚀 次のステップ</h5>
                                <div class="btn-group" role="group">
                                    <a href="/" class="btn btn-primary">ダッシュボード</a>
                                    <a href="/login" class="btn btn-success">ログイン</a>
                                    <a href="/register" class="btn btn-info">新規登録</a>
                                </div>
                                
                                <?php if (config('app.debug')): ?>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        デバッグモード有効 | 
                                        <a href="<?= url('/') ?>">ホームに戻る</a>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
        </body>
        </html>
        <?php
    }
}