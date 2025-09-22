<?php

class Environment 
{
    private static $loaded = false;

    public static function load($path = null) 
    {
        if (self::$loaded) {
            return;
        }
        
        // 直接設定ファイルを優先して読み込み
        $config_direct = dirname(dirname(__DIR__)) . '/config-direct.php';
        if (file_exists($config_direct)) {
            require_once $config_direct;
            self::$loaded = true;
            return;
        }
        
        // .envファイルからの読み込み（フォールバック）
        if ($path === null) {
            $possible_paths = [
                dirname(dirname(__DIR__)) . '/.env',
                __DIR__ . '/../../.env',
                dirname($_SERVER['SCRIPT_FILENAME']) . '/.env',
                getcwd() . '/.env',
            ];
            
            $path = null;
            foreach ($possible_paths as $test_path) {
                if (file_exists($test_path)) {
                    $path = $test_path;
                    break;
                }
            }
            
            if ($path === null) {
                throw new Exception("Neither config-direct.php nor .env file found. Please ensure configuration file exists.");
            }
        }

        if (!file_exists($path)) {
            throw new Exception("Environment file not found: " . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }

    public static function get($key, $default = null) 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }

    public static function isDevelopment() 
    {
        return self::get('APP_ENV', 'production') === 'development';
    }

    public static function isDebug() 
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}