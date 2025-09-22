<?php
// bootstrap.phpの詳細分析
echo "<h2>Bootstrap.php Analysis</h2>";

$bootstrap_path = __DIR__ . '/bootstrap.php';

if (file_exists($bootstrap_path)) {
    echo "✅ bootstrap.php exists<br>";
    echo "Size: " . filesize($bootstrap_path) . " bytes<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($bootstrap_path)), -4) . "<br>";
    
    // PHP構文チェック
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($bootstrap_path) . " 2>&1", $output, $return_var);
    
    echo "<h3>Syntax Check Result:</h3>";
    if ($return_var === 0) {
        echo "✅ No syntax errors<br>";
    } else {
        echo "❌ Syntax errors found:<br>";
        foreach ($output as $line) {
            echo "- " . htmlspecialchars($line) . "<br>";
        }
    }
    
    // ファイルの最初の数行を表示
    echo "<h3>First 10 lines:</h3>";
    $lines = file($bootstrap_path);
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "<br>";
    }
    
} else {
    echo "❌ bootstrap.php NOT FOUND<br>";
    
    // 周辺ファイルを確認
    echo "<h3>Files in current directory:</h3>";
    $files = glob(__DIR__ . '/*');
    foreach ($files as $file) {
        $filename = basename($file);
        $size = is_file($file) ? filesize($file) : 'DIR';
        echo "📄 {$filename} ({$size})<br>";
    }
}
?>