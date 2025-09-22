<?php
// ファイル存在確認
echo "<h2>File Existence Check</h2>";

$files_to_check = [
    'bootstrap.php',
    '.env',
    'config/app.php',
    'app/utils/Environment.php',
    'config/database/Connection.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "✅ {$file} - EXISTS (" . filesize($full_path) . " bytes)<br>";
    } else {
        echo "❌ {$file} - NOT FOUND<br>";
    }
}

echo "<h3>Directory Contents</h3>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "📁 {$file}<br>";
    }
}
?>