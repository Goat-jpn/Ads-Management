<?php
echo "<h2>Environment Variables Test</h2>";

// Simple include path setup
require_once __DIR__ . '/app/utils/Environment.php';

try {
    Environment::load();
    echo "✅ Environment loaded successfully<br><br>";
    
    $env_vars = [
        'APP_NAME',
        'APP_ENV', 
        'APP_DEBUG',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME'
    ];
    
    foreach ($env_vars as $var) {
        $value = Environment::get($var, 'NOT SET');
        echo "<strong>{$var}:</strong> {$value}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>