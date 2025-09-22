<?php
/**
 * エラー表示用の緊急ページ
 * PHP構文エラー等でbootstrap.phpが読み込めない場合用
 */

// PHP エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラー診断 - 広告管理システム</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .error { color: #d32f2f; background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #d32f2f; }
        .success { color: #388e3c; background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid #388e3c; }
        .info { color: #1976d2; background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid #1976d2; }
        .code { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔧 エラー診断ページ</h1>
    
    <div class="info">
        <strong>このページは PHP 構文エラーの診断用です。</strong><br>
        bootstrap.php でエラーが発生している場合に、問題を特定するために使用します。
    </div>

    <h2>PHP 基本情報</h2>
    <div class="code">
        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
        <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
        <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?><br>
        <strong>Script:</strong> <?php echo __FILE__; ?><br>
    </div>

    <h2>ファイル存在確認</h2>
    <?php
    $files = array(
        'bootstrap.php' => 'メインブートストラップ',
        '.env' => '環境設定ファイル',
        'config/database/Connection.php' => 'データベース接続クラス',
        'app/models/BaseModel.php' => '基底モデルクラス',
        'index.php' => 'メインページ'
    );

    foreach ($files as $file => $description) {
        echo '<p><strong>' . htmlspecialchars($description) . ':</strong> ';
        if (file_exists($file)) {
            echo '<span style="color: green;">✅ 存在</span>';
        } else {
            echo '<span style="color: red;">❌ 未検出</span>';
        }
        echo ' (' . htmlspecialchars($file) . ')</p>';
    }
    ?>

    <h2>bootstrap.php 読み込みテスト</h2>
    <?php
    try {
        if (file_exists('bootstrap.php')) {
            echo '<div class="info">bootstrap.php の読み込みを試行中...</div>';
            
            // エラーハンドラーを設定して詳細なエラー情報を取得
            set_error_handler(function($severity, $message, $file, $line) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            });
            
            require_once 'bootstrap.php';
            
            echo '<div class="success">✅ bootstrap.php の読み込みに成功しました。</div>';
            echo '<div class="info">システムは正常に動作する可能性があります。</div>';
            
            // データベース接続テスト
            try {
                $connection = Connection::getInstance();
                echo '<div class="success">✅ データベース接続も成功しました。</div>';
                echo '<p><a href="index.php" style="color: #1976d2; font-weight: bold;">→ メインページに移動</a></p>';
            } catch (Exception $dbError) {
                echo '<div class="error">❌ データベース接続エラー: ' . htmlspecialchars($dbError->getMessage()) . '</div>';
                echo '<div class="info">ファイルは正常ですが、データベース設定を確認してください。</div>';
            }
            
        } else {
            echo '<div class="error">❌ bootstrap.php ファイルが見つかりません。</div>';
        }
        
    } catch (ParseError $e) {
        echo '<div class="error">';
        echo '<strong>❌ PHP 構文エラーが発生しました:</strong><br>';
        echo '<strong>ファイル:</strong> ' . htmlspecialchars($e->getFile()) . '<br>';
        echo '<strong>行番号:</strong> ' . $e->getLine() . '<br>';
        echo '<strong>エラー:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
        echo '</div>';
        
        echo '<div class="info">';
        echo '<strong>対処法:</strong><br>';
        echo '1. PHP バージョンを確認してください (PHP 7.4以上推奨)<br>';
        echo '2. 該当ファイルの構文を確認してください<br>';
        echo '3. match式や型宣言等のPHP 8.0以降の機能が使用されていないか確認してください<br>';
        echo '</div>';
        
    } catch (ErrorException $e) {
        echo '<div class="error">';
        echo '<strong>❌ PHP エラーが発生しました:</strong><br>';
        echo '<strong>ファイル:</strong> ' . htmlspecialchars($e->getFile()) . '<br>';
        echo '<strong>行番号:</strong> ' . $e->getLine() . '<br>';
        echo '<strong>エラー:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<strong>❌ 予期しないエラーが発生しました:</strong><br>';
        echo '<strong>エラー:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
        echo '<strong>ファイル:</strong> ' . htmlspecialchars($e->getFile()) . '<br>';
        echo '<strong>行番号:</strong> ' . $e->getLine() . '<br>';
        echo '</div>';
    }
    ?>

    <h2>追加診断ツール</h2>
    <p>
        <a href="php-version-check.php" style="color: #1976d2;">PHP バージョン詳細チェック</a> | 
        <a href="check-deployment.php" style="color: #1976d2;">デプロイメント確認ツール</a>
    </p>

    <div class="info">
        <strong>サポート情報:</strong><br>
        このエラー情報をサポートに提供する際は、上記のエラーメッセージとPHP バージョン情報をお知らせください。
    </div>

</body>
</html>