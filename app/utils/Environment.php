<?php

namespace App\Utils;

class Environment
{
    private static bool $loaded = false;
    private static array $variables = [];

    /**
     * .envファイルを読み込み
     */
    public static function load(string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $path ?? __DIR__ . '/../../.env';
        
        if (!file_exists($envFile)) {
            // .envファイルが存在しない場合はスキップ（本番環境では環境変数を直接設定）
            self::$loaded = true;
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // コメント行をスキップ
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // 環境変数を解析
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 引用符を除去
                $value = self::parseValue($value);
                
                // 環境変数を設定
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("{$key}={$value}");
                }
                
                self::$variables[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * 環境変数の値を取得
     */
    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? self::$variables[$key] ?? $default;
    }

    /**
     * 環境変数を設定
     */
    public static function set(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
        self::$variables[$key] = $value;
    }

    /**
     * 指定した環境かどうかチェック
     */
    public static function is(string $environment): bool
    {
        return self::get('APP_ENV', 'production') === $environment;
    }

    /**
     * 開発環境かどうか
     */
    public static function isDevelopment(): bool
    {
        return self::is('development');
    }

    /**
     * 本番環境かどうか
     */
    public static function isProduction(): bool
    {
        return self::is('production');
    }

    /**
     * テスト環境かどうか
     */
    public static function isTesting(): bool
    {
        return self::is('testing');
    }

    /**
     * デバッグモードかどうか
     */
    public static function isDebug(): bool
    {
        return filter_var(self::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 必要な環境変数がセットされているかチェック
     */
    public static function validateRequired(array $requiredKeys): array
    {
        $missing = [];
        
        foreach ($requiredKeys as $key) {
            if (empty(self::get($key))) {
                $missing[] = $key;
            }
        }
        
        return $missing;
    }

    /**
     * API設定の検証
     */
    public static function validateApiConfig(): array
    {
        $errors = [];
        
        // Google Ads API設定チェック
        $googleRequired = [
            'GOOGLE_ADS_DEVELOPER_TOKEN',
            'GOOGLE_ADS_CLIENT_ID',
            'GOOGLE_ADS_CLIENT_SECRET',
            'GOOGLE_ADS_REFRESH_TOKEN'
        ];
        
        $googleMissing = self::validateRequired($googleRequired);
        if (!empty($googleMissing)) {
            $errors['google_ads'] = $googleMissing;
        }
        
        // Yahoo広告API設定チェック
        $yahooDisplayRequired = [
            'YAHOO_DISPLAY_APP_ID',
            'YAHOO_DISPLAY_SECRET',
            'YAHOO_DISPLAY_REFRESH_TOKEN'
        ];
        
        $yahooDisplayMissing = self::validateRequired($yahooDisplayRequired);
        if (!empty($yahooDisplayMissing)) {
            $errors['yahoo_display'] = $yahooDisplayMissing;
        }
        
        $yahooSearchRequired = [
            'YAHOO_SEARCH_LICENSE_ID',
            'YAHOO_SEARCH_API_ACCOUNT_ID',
            'YAHOO_SEARCH_API_ACCOUNT_PASSWORD'
        ];
        
        $yahooSearchMissing = self::validateRequired($yahooSearchRequired);
        if (!empty($yahooSearchMissing)) {
            $errors['yahoo_search'] = $yahooSearchMissing;
        }
        
        return $errors;
    }

    /**
     * 値を適切な型に変換
     */
    private static function parseValue(string $value)
    {
        $value = trim($value);
        
        // 引用符を除去
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        
        // 特別な値の処理
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value
        };
    }

    /**
     * すべての環境変数を取得
     */
    public static function all(): array
    {
        return array_merge($_ENV, self::$variables);
    }

    /**
     * 設定を配列で出力（デバッグ用）
     */
    public static function dump(bool $hideSensitive = true): array
    {
        $variables = self::all();
        
        if ($hideSensitive) {
            $sensitiveKeys = [
                'password', 'secret', 'token', 'key', 'pass', 
                'credential', 'api_key', 'private'
            ];
            
            foreach ($variables as $key => $value) {
                $lowerKey = strtolower($key);
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (strpos($lowerKey, $sensitiveKey) !== false) {
                        $variables[$key] = '***HIDDEN***';
                        break;
                    }
                }
            }
        }
        
        return $variables;
    }
}