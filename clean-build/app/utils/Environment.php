<?php

class Environment 
{
    private static $variables = [];
    private static $loaded = false;

    public static function load($path = null) 
    {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = __DIR__ . '/../../.env';
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
                
                self::$variables[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }

    public static function get($key, $default = null) 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$variables[$key]) ? self::$variables[$key] : $default;
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