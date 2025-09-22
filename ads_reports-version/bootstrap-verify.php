<?php
/**
 * Bootstrap.php 検証テスト
 * このファイルでbootstrap.phpが正常に読み込めるかテストします
 */

echo "<h2>Bootstrap.php 検証テスト</h2>";
echo "<hr>";

echo "<h3>1. ファイル存在確認</h3>";
if (file_exists(__DIR__ . '/bootstrap.php')) {
    echo "✅ bootstrap.php ファイルが見つかりました<br>";
    echo "📁 ファイルパス: " . __DIR__ . '/bootstrap.php<br>';
    echo "📊 ファイルサイズ: " . filesize(__DIR__ . '/bootstrap.php') . " bytes<br>";
} else {
    echo "❌ bootstrap.php ファイルが見つかりません<br>";
    exit;
}

echo "<h3>2. PHP構文チェック</h3>";
$output = [];
$return_var = 0;
exec("php -l " . escapeshellarg(__DIR__ . '/bootstrap.php') . " 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo "✅ PHP構文エラーなし<br>";
    echo "📝 結果: " . implode('<br>', $output) . "<br>";
} else {
    echo "❌ PHP構文エラーが見つかりました<br>";
    echo "📝 エラー詳細:<br>";
    foreach ($output as $line) {
        echo "　　" . htmlspecialchars($line) . "<br>";
    }
    exit;
}

echo "<h3>3. Bootstrap読み込みテスト</h3>";
try {
    // エラー出力をキャプチャ
    ob_start();
    $error_occurred = false;
    
    set_error_handler(function($severity, $message, $file, $line) use (&$error_occurred) {
        $error_occurred = true;
        echo "❌ PHP Error: " . htmlspecialchars($message) . " in " . htmlspecialchars($file) . " on line " . $line . "<br>";
        return true;
    });
    
    require_once __DIR__ . '/bootstrap.php';
    
    restore_error_handler();
    $output_content = ob_get_clean();
    
    if (!$error_occurred && empty($output_content)) {
        echo "✅ Bootstrap.php が正常に読み込まれました<br>";
        echo "🎯 アプリケーション初期化完了<br>";
        
        // 定義された定数をチェック
        echo "<h4>定義された定数:</h4>";
        if (defined('APP_ROOT')) echo "✅ APP_ROOT: " . APP_ROOT . "<br>";
        if (defined('CONFIG_ROOT')) echo "✅ CONFIG_ROOT: " . CONFIG_ROOT . "<br>";
        if (defined('STORAGE_ROOT')) echo "✅ STORAGE_ROOT: " . STORAGE_ROOT . "<br>";
        if (defined('LOG_ROOT')) echo "✅ LOG_ROOT: " . LOG_ROOT . "<br>";
        
        // ヘルパー関数をチェック
        echo "<h4>定義されたヘルパー関数:</h4>";
        if (function_exists('config')) echo "✅ config() 関数<br>";
        if (function_exists('env')) echo "✅ env() 関数<br>";
        if (function_exists('logger')) echo "✅ logger() 関数<br>";
        if (function_exists('formatCurrency')) echo "✅ formatCurrency() 関数<br>";
        if (function_exists('csrf_token')) echo "✅ csrf_token() 関数<br>";
        
    } else {
        echo "❌ Bootstrap読み込み中にエラーが発生しました<br>";
        if (!empty($output_content)) {
            echo "📝 出力内容:<br>";
            echo "<pre>" . htmlspecialchars($output_content) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "📁 ファイル: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "📍 行: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "📁 ファイル: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "📍 行: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<h3>テスト完了</h3>";
echo "📅 実行日時: " . date('Y-m-d H:i:s') . "<br>";
echo "🖥️ PHP バージョン: " . PHP_VERSION . "<br>";
?>