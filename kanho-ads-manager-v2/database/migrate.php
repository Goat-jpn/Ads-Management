#!/usr/bin/env php
<?php
/**
 * Database Migration Runner
 * Kanho Ads Manager v2.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            putenv($line);
        }
    }
}

require_once __DIR__ . '/../config/database.php';

class MigrationRunner
{
    private $db;
    private $migrationsPath;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = __DIR__ . '/migrations';
        $this->createMigrationsTable();
    }
    
    private function createMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->getConnection()->exec($sql);
    }
    
    public function run()
    {
        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);
        
        $executed = $this->getExecutedMigrations();
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            if (in_array($filename, $executed)) {
                echo "SKIP: {$filename} (already executed)\n";
                continue;
            }
            
            try {
                echo "RUNNING: {$filename}... ";
                $sql = file_get_contents($file);
                
                // Execute migration
                $this->db->getConnection()->exec($sql);
                
                // Record execution
                $stmt = $this->db->getConnection()->prepare(
                    "INSERT INTO migrations (migration) VALUES (?)"
                );
                $stmt->execute([$filename]);
                
                echo "SUCCESS\n";
            } catch (Exception $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
                exit(1);
            }
        }
        
        echo "\nAll migrations completed successfully!\n";
    }
    
    private function getExecutedMigrations()
    {
        try {
            $stmt = $this->db->getConnection()->query("SELECT migration FROM migrations");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function status()
    {
        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);
        
        $executed = $this->getExecutedMigrations();
        
        echo "Migration Status:\n";
        echo "================\n";
        
        foreach ($files as $file) {
            $filename = basename($file);
            $status = in_array($filename, $executed) ? 'EXECUTED' : 'PENDING';
            echo sprintf("%-50s %s\n", $filename, $status);
        }
    }
    
    public function fresh()
    {
        echo "WARNING: This will drop all tables and re-run all migrations!\n";
        echo "Are you sure? (y/N): ";
        $confirmation = trim(fgets(STDIN));
        
        if (strtolower($confirmation) !== 'y') {
            echo "Aborted.\n";
            return;
        }
        
        // Drop all tables
        $this->dropAllTables();
        
        // Re-run migrations
        $this->run();
    }
    
    private function dropAllTables()
    {
        $pdo = $this->db->getConnection();
        
        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Get all table names
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Drop each table
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "Dropped table: {$table}\n";
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $runner = new MigrationRunner();
    $command = $argv[1] ?? 'run';
    
    switch ($command) {
        case 'run':
            $runner->run();
            break;
        case 'status':
            $runner->status();
            break;
        case 'fresh':
            $runner->fresh();
            break;
        default:
            echo "Usage: php migrate.php [run|status|fresh]\n";
            echo "  run     - Run pending migrations\n";
            echo "  status  - Show migration status\n";
            echo "  fresh   - Drop all tables and re-run migrations\n";
    }
}