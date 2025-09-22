<?php

namespace App\Utils;

use DateTime;

class Logger
{
    private static ?self $instance = null;
    private string $logPath;
    private string $logLevel;
    private array $levelPriority = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];

    private function __construct(string $logPath, string $logLevel = 'info')
    {
        $this->logPath = rtrim($logPath, '/') . '/';
        $this->logLevel = strtolower($logLevel);
        
        // ログディレクトリを作成
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public static function getInstance(string $logPath = null, string $logLevel = null): self
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/app.php';
            $logPath = $logPath ?? $config['logging']['path'];
            $logLevel = $logLevel ?? $config['logging']['level'];
            self::$instance = new self($logPath, $logLevel);
        }
        
        return self::$instance;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function apiLog(string $platform, string $action, array $data = []): void
    {
        $message = "API Call - Platform: {$platform}, Action: {$action}";
        $this->log('info', $message, $data);
    }

    public function syncLog(string $accountId, string $status, array $details = []): void
    {
        $message = "Data Sync - Account: {$accountId}, Status: {$status}";
        $this->log('info', $message, $details);
    }

    public function billingLog(string $clientId, string $action, array $data = []): void
    {
        $message = "Billing - Client: {$clientId}, Action: {$action}";
        $this->log('info', $message, $data);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        
        // ログレベルチェック
        if ($this->levelPriority[$level] < $this->levelPriority[$this->logLevel]) {
            return;
        }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $pid = getmypid();
        
        // コンテキストデータを文字列化
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        // ログエントリを作成
        $logEntry = sprintf(
            "[%s] %s.%s: %s%s\n",
            $timestamp,
            strtoupper($level),
            $pid,
            $message,
            $contextStr
        );

        // ログファイルに書き込み
        $filename = $this->getLogFilename($level);
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);

        // 一般ログファイルにも書き込み（critical, error, warning）
        if (in_array($level, ['critical', 'error', 'warning'])) {
            $generalFile = $this->logPath . 'app-' . date('Y-m-d') . '.log';
            file_put_contents($generalFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    private function getLogFilename(string $level): string
    {
        $date = date('Y-m-d');
        
        return match ($level) {
            'debug' => $this->logPath . "debug-{$date}.log",
            'info' => $this->logPath . "info-{$date}.log",
            'warning' => $this->logPath . "warning-{$date}.log",
            'error' => $this->logPath . "error-{$date}.log",
            'critical' => $this->logPath . "critical-{$date}.log",
            default => $this->logPath . "app-{$date}.log"
        };
    }

    /**
     * ログファイルのローテーション
     */
    public function rotateLogFiles(int $keepDays = 30): int
    {
        $deletedCount = 0;
        $cutoffDate = date('Y-m-d', strtotime("-{$keepDays} days"));
        
        $files = glob($this->logPath . '*.log');
        foreach ($files as $file) {
            if (preg_match('/(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                if ($matches[1] < $cutoffDate) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }
        
        return $deletedCount;
    }

    /**
     * ログファイルのサイズを取得
     */
    public function getLogFileSize(string $level = null): int
    {
        if ($level) {
            $filename = $this->getLogFilename($level);
            return file_exists($filename) ? filesize($filename) : 0;
        }
        
        $totalSize = 0;
        $files = glob($this->logPath . '*.log');
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return $totalSize;
    }
}