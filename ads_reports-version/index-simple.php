<?php
/**
 * 簡易版index.php
 * bootstrap.phpを使わずに最小限の動作確認
 */

// エラー表示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 基本設定
$pageTitle = '広告管理システム - 簡易版';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/ads_reports/">
                <i class="fas fa-chart-line me-2"></i>
                広告管理システム (簡易版)
            </a>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle me-2"></i>簡易版モード</h4>
                    <p>このページはbootstrap.phpを使用せずに動作する簡易版です。</p>
                    <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                    <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">システム状態</h6>
                                <div class="h3">正常</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">PHP動作</h6>
                                <div class="h3">OK</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-code fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">データベース</h6>
                                <div class="h3">未テスト</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-database fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">診断モード</h6>
                                <div class="h3">実行中</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-stethoscope fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            システム診断
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>ファイル存在確認</h6>
                        <ul>
                            <?php
                            $files = array(
                                'bootstrap.php' => 'メインブートストラップ',
                                '.env' => '環境設定ファイル',
                                '.htaccess' => 'Apache設定',
                                'config/database/Connection.php' => 'データベース接続',
                                'app/models/BaseModel.php' => '基底モデル'
                            );
                            
                            foreach ($files as $file => $desc) {
                                $exists = file_exists($file);
                                echo "<li>{$desc}: ";
                                if ($exists) {
                                    echo "<span class='text-success'><i class='fas fa-check'></i> 存在</span>";
                                } else {
                                    echo "<span class='text-danger'><i class='fas fa-times'></i> 未検出</span>";
                                }
                                echo " ({$file})</li>";
                            }
                            ?>
                        </ul>

                        <h6 class="mt-4">次のステップ</h6>
                        <div class="btn-group" role="group">
                            <a href="emergency-check.php" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>詳細診断
                            </a>
                            <a href="simple-test.php" class="btn btn-secondary">
                                <i class="fas fa-vial me-1"></i>基本テスト
                            </a>
                            <?php if (file_exists('index.php')): ?>
                            <a href="index.php" class="btn btn-success">
                                <i class="fas fa-home me-1"></i>メイン版に挑戦
                            </a>
                            <?php endif; ?>
                        </div>

                        <?php
                        // .htaccessの確認
                        if (file_exists('.htaccess')) {
                            echo "<div class='alert alert-warning mt-3'>";
                            echo "<strong>⚠️ .htaccess ファイルが検出されました。</strong><br>";
                            echo "500エラーの原因が .htaccess の場合は、一時的にファイル名を変更してテストしてください。<br>";
                            echo "例: .htaccess → .htaccess-backup";
                            echo "</div>";
                        }
                        
                        // bootstrap.phpの確認
                        if (file_exists('bootstrap.php')) {
                            echo "<div class='alert alert-info mt-3'>";
                            echo "<strong>📝 bootstrap.php が検出されました。</strong><br>";
                            echo "メインシステムを使用するには、bootstrap.phpが正常に動作する必要があります。";
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>