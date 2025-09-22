<?php
/**
 * bootstrap.php 段階的テスト
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Bootstrap.php テスト</h1>";

echo "<h2>1. ファイル存在確認</h2>";
if (file_exists('bootstrap.php')) {
    echo "<p style='color:green'>✅ bootstrap.php ファイル存在</p>";
    
    echo "<h2>2. ファイル読み込みテスト</h2>";
    
    // まずファイル内容を確認
    $content = file_get_contents('bootstrap.php');
    if ($content === false) {
        echo "<p style='color:red'>❌ ファイル読み込み失敗</p>";
    } else {
        echo "<p style='color:green'>✅ ファイル読み込み成功 (" . strlen($content) . " bytes)</p>";
        
        // 危険な構文をチェック
        echo "<h2>3. 構文チェック</h2>";
        $dangerous_patterns = array(
            'match(' => 'match式（PHP8.0+）',
            ': string' => '型宣言（string）',
            ': array' => '型宣言（array）',
            ': int' => '型宣言（int）',
            ': bool' => '型宣言（bool）',
            'string $' => 'プロパティ型宣言',
            'array $' => 'プロパティ型宣言',
            '?PDO $' => 'Nullable型宣言'
        );
        
        $found_issues = array();
        foreach ($dangerous_patterns as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                $found_issues[] = $description . " ({$pattern})";
            }
        }
        
        if (empty($found_issues)) {
            echo "<p style='color:green'>✅ 危険な構文は検出されませんでした</p>";
            
            echo "<h2>4. 実際の読み込みテスト</h2>";
            echo "<p>bootstrap.php の実際の読み込みを試行中...</p>";
            
            try {
                // エラーをキャッチするためのハンドラー設定
                set_error_handler(function($severity, $message, $file, $line) {
                    throw new ErrorException($message, 0, $severity, $file, $line);
                });
                
                require_once 'bootstrap.php';
                
                echo "<p style='color:green'>✅ bootstrap.php 読み込み成功！</p>";
                echo "<p>システムは正常に動作する可能性があります。</p>";
                
            } catch (ParseError $e) {
                echo "<p style='color:red'>❌ 構文エラー:</p>";
                echo "<pre style='background:#ffebee;padding:10px;'>";
                echo "ファイル: " . htmlspecialchars($e->getFile()) . "\n";
                echo "行番号: " . $e->getLine() . "\n"; 
                echo "エラー: " . htmlspecialchars($e->getMessage()) . "\n";
                echo "</pre>";
                
            } catch (ErrorException $e) {
                echo "<p style='color:red'>❌ 実行エラー:</p>";
                echo "<pre style='background:#ffebee;padding:10px;'>";
                echo "ファイル: " . htmlspecialchars($e->getFile()) . "\n";
                echo "行番号: " . $e->getLine() . "\n";
                echo "エラー: " . htmlspecialchars($e->getMessage()) . "\n";
                echo "</pre>";
                
            } catch (Exception $e) {
                echo "<p style='color:red'>❌ その他のエラー:</p>";
                echo "<pre style='background:#ffebee;padding:10px;'>";
                echo "エラー: " . htmlspecialchars($e->getMessage()) . "\n";
                echo "ファイル: " . htmlspecialchars($e->getFile()) . "\n";
                echo "行番号: " . $e->getLine() . "\n";
                echo "</pre>";
            }
            
        } else {
            echo "<p style='color:orange'>⚠️ 潜在的な問題が検出されました:</p>";
            echo "<ul>";
            foreach ($found_issues as $issue) {
                echo "<li style='color:red'>" . htmlspecialchars($issue) . "</li>";
            }
            echo "</ul>";
            echo "<p style='color:blue'>これらの構文はPHP 7.4では対応していない可能性があります。</p>";
        }
    }
    
} else {
    echo "<p style='color:red'>❌ bootstrap.php ファイルが見つかりません</p>";
}

echo "<hr>";
echo "<h2>次のステップ</h2>";
echo "<p><a href='test.php'>← 基本テストに戻る</a></p>";
echo "<p><a href='index.php'>メインページを試す</a> (エラーが解決していれば)</p>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
pre { border: 1px solid #ccc; border-radius: 4px; }
</style>