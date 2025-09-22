<?php
/**
 * デプロイメント確認用スクリプト
 * https://app.kanho.co.jp/check-deployment.php でアクセス
 */

// セキュリティ: 本番環境では削除またはアクセス制限をかけること
$isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'kanho.co.jp') !== false);

if ($isProduction) {
    // 本番環境での実行を制限（必要に応じてIPアドレス制限等を追加）
    $allowedIPs = ['127.0.0.1', '::1']; // 必要に応じて管理者IPを追加
    if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
        http_response_code(403);
        die('Access Denied');
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>デプロイメント確認 - 広告管理システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .check-item { margin: 10px 0; }
        .status-ok { color: #198754; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            広告管理システム - デプロイメント確認
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        $checks = [];
                        $overallStatus = true;

                        // 1. PHP バージョン確認
                        $phpVersion = PHP_VERSION;
                        $phpOk = version_compare($phpVersion, '8.0', '>=');
                        $checks[] = [
                            'name' => 'PHP バージョン',
                            'status' => $phpOk,
                            'message' => "PHP {$phpVersion}" . ($phpOk ? ' (OK)' : ' (8.0以上推奨)'),
                            'critical' => true
                        ];
                        if (!$phpOk) $overallStatus = false;

                        // 2. ファイル存在確認
                        $requiredFiles = [
                            '.env' => '.env設定ファイル',
                            '.htaccess' => 'Apache設定ファイル',
                            'bootstrap.php' => 'オートローダー',
                            'index.php' => 'メインページ',
                            'clients.php' => 'クライアント管理ページ',
                            'api/dashboard/data.php' => 'ダッシュボードAPI',
                            'api/clients/index.php' => 'クライアントAPI'
                        ];

                        foreach ($requiredFiles as $file => $description) {
                            $exists = file_exists($file);
                            $checks[] = [
                                'name' => $description,
                                'status' => $exists,
                                'message' => $file . ($exists ? ' (存在)' : ' (未検出)'),
                                'critical' => true
                            ];
                            if (!$exists) $overallStatus = false;
                        }

                        // 3. データベース接続確認
                        $dbConnected = false;
                        $dbError = '';
                        try {
                            require_once 'bootstrap.php';
                            $connection = Connection::getInstance();
                            $dbConnected = true;
                            $dbMessage = '接続成功';
                        } catch (Exception $e) {
                            $dbError = $e->getMessage();
                            $dbMessage = '接続失敗: ' . $dbError;
                            $overallStatus = false;
                        }

                        $checks[] = [
                            'name' => 'MariaDB接続',
                            'status' => $dbConnected,
                            'message' => $dbMessage,
                            'critical' => true
                        ];

                        // 4. テーブル存在確認
                        if ($dbConnected) {
                            try {
                                $requiredTables = ['clients', 'ad_accounts', 'daily_ad_data', 'fee_settings'];
                                $pdo = $connection->getPDO();
                                
                                foreach ($requiredTables as $table) {
                                    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                                    $exists = $stmt->fetch() !== false;
                                    $checks[] = [
                                        'name' => "テーブル: {$table}",
                                        'status' => $exists,
                                        'message' => $exists ? '存在' : '未作成',
                                        'critical' => true
                                    ];
                                    if (!$exists) $overallStatus = false;
                                }
                            } catch (Exception $e) {
                                $checks[] = [
                                    'name' => 'テーブル確認',
                                    'status' => false,
                                    'message' => 'エラー: ' . $e->getMessage(),
                                    'critical' => true
                                ];
                                $overallStatus = false;
                            }
                        }

                        // 5. ディレクトリ権限確認
                        $directories = ['logs', 'public', 'api'];
                        foreach ($directories as $dir) {
                            if (is_dir($dir)) {
                                $writable = is_writable($dir);
                                $checks[] = [
                                    'name' => "ディレクトリ権限: {$dir}",
                                    'status' => $writable,
                                    'message' => $writable ? '書き込み可能' : '書き込み不可',
                                    'critical' => $dir === 'logs'
                                ];
                                if ($dir === 'logs' && !$writable) $overallStatus = false;
                            }
                        }

                        // 6. Apache mod_rewrite確認
                        $rewriteEnabled = function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : null;
                        if ($rewriteEnabled !== null) {
                            $checks[] = [
                                'name' => 'Apache mod_rewrite',
                                'status' => $rewriteEnabled,
                                'message' => $rewriteEnabled ? '有効' : '無効',
                                'critical' => true
                            ];
                            if (!$rewriteEnabled) $overallStatus = false;
                        }

                        // 結果表示
                        ?>

                        <!-- 総合判定 -->
                        <div class="alert <?php echo $overallStatus ? 'alert-success' : 'alert-danger'; ?> mb-4">
                            <h4>
                                <i class="fas <?php echo $overallStatus ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                                総合判定: <?php echo $overallStatus ? '✅ デプロイメント成功' : '❌ 設定に問題があります'; ?>
                            </h4>
                            <?php if ($overallStatus): ?>
                                <p class="mb-0">すべての必須チェック項目をクリアしました。システムは正常に動作する準備ができています。</p>
                            <?php else: ?>
                                <p class="mb-0">以下の問題を解決してください。赤色の項目は必須設定です。</p>
                            <?php endif; ?>
                        </div>

                        <!-- チェック結果一覧 -->
                        <h5>📋 詳細チェック結果</h5>
                        <div class="row">
                            <?php foreach ($checks as $check): ?>
                                <?php 
                                $statusClass = $check['status'] ? 'status-ok' : ($check['critical'] ? 'status-error' : 'status-warning');
                                $iconClass = $check['status'] ? 'fa-check-circle' : 'fa-times-circle';
                                ?>
                                <div class="col-md-6 check-item">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas <?php echo $iconClass; ?> <?php echo $statusClass; ?> me-2"></i>
                                                <?php echo htmlspecialchars($check['name']); ?>
                                            </h6>
                                            <p class="card-text <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($check['message']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- システム情報 -->
                        <div class="mt-4">
                            <h5>🔧 システム情報</h5>
                            <div class="code-block">
                                <strong>サーバー情報:</strong><br>
                                PHP Version: <?php echo PHP_VERSION; ?><br>
                                Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                                Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?><br>
                                Current Directory: <?php echo __DIR__; ?><br>
                                Host: <?php echo $_SERVER['HTTP_HOST'] ?? 'Unknown'; ?><br>
                                
                                <?php if ($dbConnected): ?>
                                <br><strong>データベース情報:</strong><br>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT VERSION() as version");
                                    $version = $stmt->fetch();
                                    echo "MariaDB Version: " . $version['version'] . "<br>";
                                    
                                    $stmt = $pdo->query("SELECT DATABASE() as db_name");
                                    $dbInfo = $stmt->fetch();
                                    echo "Database Name: " . $dbInfo['db_name'] . "<br>";
                                } catch (Exception $e) {
                                    echo "DB Info Error: " . $e->getMessage() . "<br>";
                                }
                                ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- アクション -->
                        <div class="mt-4">
                            <h5>🚀 次のステップ</h5>
                            <?php if ($overallStatus): ?>
                                <div class="alert alert-info">
                                    <h6>✅ デプロイメント完了！</h6>
                                    <ul class="mb-0">
                                        <li><a href="index.php" target="_blank">メインページにアクセス</a></li>
                                        <li><a href="clients.php" target="_blank">クライアント管理画面を確認</a></li>
                                        <li><a href="api/dashboard/data" target="_blank">API動作を確認</a></li>
                                        <li><strong>セキュリティ</strong>: このファイル（check-deployment.php）を削除してください</li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <h6>⚠️ 設定を完了してください</h6>
                                    <ul class="mb-0">
                                        <li>赤色のエラー項目を解決してください</li>
                                        <li>DATABASE_SETUP.sqlを実行してください</li>
                                        <li>.envファイルのDB設定を確認してください</li>
                                        <li>ファイル権限を適切に設定してください</li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>