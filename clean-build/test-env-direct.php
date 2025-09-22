<?php
echo "<h2>Direct Environment Configuration Test</h2>";

// 直接設定版のEnvironmentクラスを使用
require_once __DIR__ . '/app/utils/Environment-direct.php';

try {
    Environment::load();
    echo "✅ Environment loaded successfully<br><br>";
    
    $env_vars = [
        'APP_NAME',
        'APP_ENV', 
        'APP_DEBUG',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'CONFIG_LOADED'
    ];
    
    echo "<h3>Configuration Values:</h3>";
    foreach ($env_vars as $var) {
        $value = Environment::get($var, 'NOT SET');
        echo "<strong>{$var}:</strong> {$value}<br>";
    }
    
    // 設定ファイルのソースを確認
    echo "<br><h3>Configuration Source:</h3>";
    $config_source = Environment::get('CONFIG_LOADED', 'unknown');
    if ($config_source === 'direct') {
        echo "✅ Using config-direct.php (recommended)<br>";
    } else {
        echo "ℹ️ Using .env file<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><h3>Files Check:</h3>";
$config_files = [
    'config-direct.php' => __DIR__ . '/config-direct.php',
    '.env' => __DIR__ . '/.env'
];

foreach ($config_files as $name => $path) {
    $exists = file_exists($path);
    $status = $exists ? "✅ EXISTS" : "❌ NOT FOUND";
    echo "{$name}: {$status}<br>";
}
?>

<style>
body { font-family: system-ui, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3 { color: #333; }
</style>