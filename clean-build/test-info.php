<?php
echo "<h2>PHP Information</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current File: " . __FILE__ . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Date/Time: " . date('Y-m-d H:i:s') . "<br>";

// 拡張モジュール確認
echo "<h3>Available Extensions</h3>";
$extensions = ['mysqli', 'pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? "✅" : "❌";
    echo "{$status} {$ext}<br>";
}

// PHP設定確認
echo "<h3>PHP Settings</h3>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Error Reporting: " . ini_get('error_reporting') . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
?>