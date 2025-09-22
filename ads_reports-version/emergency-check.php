<?php
/**
 * 500エラー緊急診断ページ
 * 最小限のコードで問題を特定
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>";
echo "<html><head><title>緊急診断</title>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";

echo "<h1>🚨 500エラー緊急診断</h1>";

// 基本情報
echo "<h2>基本情報</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// ディレクトリ確認
echo "<h2>ディレクトリ確認</h2>";
echo "<p><strong>現在のディレクトリ:</strong> " . __DIR__ . "</p>";
echo "<p><strong>スクリプト:</strong> " . __FILE__ . "</p>";

// 権限確認
echo "<h2>ファイル権限確認</h2>";
$files = array('.', 'index.php', '.htaccess', '.env', 'bootstrap.php');
foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<p><strong>$file:</strong> 存在 (権限: $perms)</p>";
    } else {
        echo "<p><strong>$file:</strong> <span class='error'>未検出</span></p>";
    }
}

// .htaccess確認
echo "<h2>.htaccess 確認</h2>";
if (file_exists('.htaccess')) {
    echo "<p class='ok'>✅ .htaccess ファイルが存在します</p>";
    $htaccess = file_get_contents('.htaccess');
    if ($htaccess !== false) {
        $lines = explode("\n", $htaccess);
        echo "<p><strong>行数:</strong> " . count($lines) . "</p>";
        echo "<p><strong>最初の数行:</strong></p>";
        echo "<pre style='background:#f5f5f5;padding:10px;'>";
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            echo htmlspecialchars($lines[$i]) . "\n";
        }
        echo "</pre>";
    }
} else {
    echo "<p class='error'>❌ .htaccess ファイルが見つかりません</p>";
}

// PHP設定確認
echo "<h2>PHP設定確認</h2>";
echo "<p><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . "</p>";
echo "<p><strong>error_log:</strong> " . (ini_get('error_log') ?: 'default') . "</p>";

// 重要な拡張機能
echo "<h2>PHP拡張機能</h2>";
$extensions = array('pdo', 'pdo_mysql', 'mbstring', 'json');
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p><strong>$ext:</strong> " . ($loaded ? "<span class='ok'>✅</span>" : "<span class='error'>❌</span>") . "</p>";
}

// 簡単なincludeテスト
echo "<h2>ファイル読み込みテスト</h2>";

// .env読み込みテスト
if (file_exists('.env')) {
    echo "<p class='ok'>✅ .env ファイル存在</p>";
    try {
        $env_content = file_get_contents('.env');
        if ($env_content !== false) {
            echo "<p class='ok'>✅ .env ファイル読み込み成功</p>";
            echo "<p><strong>DB_HOST設定:</strong> " . (strpos($env_content, 'DB_HOST') !== false ? '✅' : '❌') . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ .env読み込みエラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>❌ .env ファイルなし</p>";
}

// bootstrap.php テスト (注意深く)
echo "<h2>bootstrap.php テスト</h2>";
if (file_exists('bootstrap.php')) {
    echo "<p class='ok'>✅ bootstrap.php ファイル存在</p>";
    
    // ファイル内容の簡単チェック
    $bootstrap_content = file_get_contents('bootstrap.php');
    if ($bootstrap_content !== false) {
        echo "<p class='ok'>✅ bootstrap.php 読み込み可能</p>";
        echo "<p><strong>ファイルサイズ:</strong> " . strlen($bootstrap_content) . " bytes</p>";
        
        // 危険な構文の確認
        $dangerous = array('match(', ': array', ': string', ': int');
        $found_issues = array();
        foreach ($dangerous as $pattern) {
            if (strpos($bootstrap_content, $pattern) !== false) {
                $found_issues[] = $pattern;
            }
        }
        
        if (empty($found_issues)) {
            echo "<p class='ok'>✅ 危険な構文は検出されませんでした</p>";
        } else {
            echo "<p class='error'>❌ 潜在的問題: " . implode(', ', $found_issues) . "</p>";
        }
    }
} else {
    echo "<p class='error'>❌ bootstrap.php ファイルなし</p>";
}

// ログファイル確認
echo "<h2>ログ確認</h2>";
$log_files = array('logs/php_errors.log', 'error_log', '../error_log');
$found_log = false;
foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        echo "<p class='ok'>✅ ログファイル発見: $log_file</p>";
        $found_log = true;
        
        $log_content = file_get_contents($log_file);
        if ($log_content && strlen($log_content) > 0) {
            echo "<p><strong>最新のログ内容:</strong></p>";
            echo "<pre style='background:#ffebee;padding:10px;max-height:200px;overflow:auto;'>";
            echo htmlspecialchars(substr($log_content, -1000)); // 最後の1000文字
            echo "</pre>";
        } else {
            echo "<p>ログファイルは空です</p>";
        }
        break;
    }
}

if (!$found_log) {
    echo "<p class='info'>📝 ログファイルが見つかりません（まだエラーが記録されていない可能性）</p>";
}

echo "<h2>推奨対処法</h2>";
echo "<div style='background:#e3f2fd;padding:15px;margin:10px 0;'>";
echo "<h3>1. 段階的確認</h3>";
echo "<p>a) このページが表示された = 基本的なPHPは動作</p>";
echo "<p>b) .htaccess を一時的にリネーム (.htaccess → .htaccess-backup)</p>";
echo "<p>c) 直接 index.php にアクセス</p>";

echo "<h3>2. .htaccess問題の可能性</h3>";
echo "<p>• Apache mod_rewrite が無効</p>";
echo "<p>• .htaccess の記述エラー</p>";
echo "<p>• ファイル権限問題</p>";

echo "<h3>3. PHP include/require エラー</h3>";
echo "<p>• bootstrap.php の読み込みエラー</p>";
echo "<p>• クラスファイルの読み込み失敗</p>";
echo "<p>• 名前空間の問題</p>";
echo "</div>";

echo "<h2>次のステップ</h2>";
echo "<p><a href='simple-test.php'>→ 超シンプルテストページ</a></p>";
echo "<p><a href='index-simple.php'>→ 簡易版index.php</a></p>";

echo "</body></html>";
?>