<?php
// 段階的bootstrap読み込みテスト
echo "<h2>Step-by-Step Bootstrap Loading Test</h2>";

// エラー表示を強制有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "✅ Error reporting enabled<br>";

// カスタムエラーハンドラー
set_error_handler(function($severity, $message, $file, $line) {
    echo "❌ PHP Error: " . htmlspecialchars($message) . " in " . htmlspecialchars($file) . " line " . $line . "<br>";
    return true;
});

echo "✅ Custom error handler set<br>";

$bootstrap_path = __DIR__ . '/bootstrap.php';

if (file_exists($bootstrap_path)) {
    echo "✅ bootstrap.php found<br>";
    
    try {
        echo "🔄 Attempting to include bootstrap.php...<br>";
        
        // バッファリング開始
        ob_start();
        
        include $bootstrap_path;
        
        $output = ob_get_clean();
        
        echo "✅ bootstrap.php included successfully<br>";
        
        if (!empty($output)) {
            echo "<h3>Output from bootstrap.php:</h3>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
        
        // 定数が定義されているかチェック
        echo "<h3>Defined Constants:</h3>";
        $constants = ['APP_ROOT', 'CONFIG_ROOT', 'STORAGE_ROOT', 'LOG_ROOT'];
        foreach ($constants as $const) {
            if (defined($const)) {
                echo "✅ {$const}: " . constant($const) . "<br>";
            } else {
                echo "❌ {$const}: Not defined<br>";
            }
        }
        
    } catch (ParseError $e) {
        echo "❌ Parse Error: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
    } catch (Error $e) {
        echo "❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
    } catch (Exception $e) {
        echo "❌ Exception: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
    }
} else {
    echo "❌ bootstrap.php NOT FOUND at: {$bootstrap_path}<br>";
}

echo "<br>Test completed at: " . date('Y-m-d H:i:s') . "<br>";
?>