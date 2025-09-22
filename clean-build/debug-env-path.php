<?php
echo "<h2>Environment File Debug</h2>";

// 現在の環境情報を表示
echo "<h3>Server Environment Info</h3>";
echo "<strong>Current Working Directory:</strong> " . getcwd() . "<br>";
echo "<strong>Script Filename:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "<strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "<br>";
echo "<strong>__DIR__:</strong> " . __DIR__ . "<br>";
echo "<strong>__FILE__:</strong> " . __FILE__ . "<br>";

// 可能なパスをすべてテスト
echo "<h3>Searching for .env file</h3>";
$possible_paths = [
    __DIR__ . '/.env',                    // 現在のディレクトリ
    __DIR__ . '/../../.env',              // 通常のパス
    dirname(dirname(__DIR__)) . '/.env',  // より安全なパス
    $_SERVER['DOCUMENT_ROOT'] . '/../.env', // Document rootの上
    getcwd() . '/.env',                   // 現在の作業ディレクトリ
    dirname($_SERVER['SCRIPT_FILENAME']) . '/.env', // 実行ファイルと同じディレクトリ
];

$found_path = null;
foreach ($possible_paths as $i => $test_path) {
    $exists = file_exists($test_path);
    $status = $exists ? "✅ EXISTS" : "❌ NOT FOUND";
    echo ($i + 1) . ". {$test_path} - {$status}<br>";
    
    if ($exists && $found_path === null) {
        $found_path = $test_path;
    }
}

if ($found_path) {
    echo "<br><h3>✅ Found .env file at: {$found_path}</h3>";
    
    // ファイルの内容を確認
    $content = file_get_contents($found_path);
    $lines = explode("\n", $content);
    
    echo "<h4>File Contents (first 10 lines):</h4>";
    echo "<pre>";
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
    
    // Environment クラスのテスト
    echo "<h3>Testing Environment Class</h3>";
    try {
        require_once __DIR__ . '/app/utils/Environment.php';
        Environment::load($found_path);
        
        echo "✅ Environment loaded successfully<br>";
        
        $test_vars = ['APP_NAME', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
        foreach ($test_vars as $var) {
            $value = Environment::get($var, 'NOT SET');
            echo "<strong>{$var}:</strong> {$value}<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error loading environment: " . $e->getMessage();
    }
    
} else {
    echo "<br><h3>❌ .env file not found in any location</h3>";
    echo "<p>Please ensure .env file is placed in the same directory as this PHP file.</p>";
    
    // ディレクトリ内のファイルを表示
    echo "<h4>Files in current directory:</h4>";
    $files = scandir(__DIR__);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "📄 {$file}<br>";
        }
    }
}
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3, h4 { color: #333; }
pre { background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
</style>