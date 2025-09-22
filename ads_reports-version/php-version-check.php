<?php
/**
 * PHP バージョンチェック
 * エラー発生時の緊急診断用
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>PHP Version Check</title>";
echo "<style>body{font-family:Arial;margin:40px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";
echo "</head><body>";

echo "<h2>🔧 PHP 環境チェック</h2>";

// PHP バージョン
$phpVersion = PHP_VERSION;
echo "<p><strong>PHP Version:</strong> {$phpVersion}";

if (version_compare($phpVersion, '7.0', '>=')) {
    echo " <span class='ok'>✅ PHP 7.0以上</span>";
} else {
    echo " <span class='error'>❌ PHP 7.0未満 (古いバージョン)</span>";
}
echo "</p>";

// 重要な拡張機能チェック
$extensions = array('pdo', 'pdo_mysql', 'mbstring', 'json');
echo "<h3>拡張機能チェック</h3>";
foreach ($extensions as $ext) {
    echo "<p><strong>{$ext}:</strong> ";
    if (extension_loaded($ext)) {
        echo "<span class='ok'>✅ 利用可能</span>";
    } else {
        echo "<span class='error'>❌ 未インストール</span>";
    }
    echo "</p>";
}

// PHP 8.0以降の機能チェック
echo "<h3>PHP 8.0+ 機能チェック</h3>";

echo "<p><strong>match式:</strong> ";
if (version_compare($phpVersion, '8.0', '>=')) {
    echo "<span class='ok'>✅ 対応 (PHP 8.0+)</span>";
} else {
    echo "<span class='warning'>⚠️ 非対応 (switch文使用)</span>";
}
echo "</p>";

echo "<p><strong>型宣言:</strong> ";
if (version_compare($phpVersion, '7.0', '>=')) {
    echo "<span class='ok'>✅ 対応 (PHP 7.0+)</span>";
} else {
    echo "<span class='warning'>⚠️ 限定対応</span>";
}
echo "</p>";

// 推奨事項
echo "<h3>📋 対応状況</h3>";
if (version_compare($phpVersion, '8.0', '>=')) {
    echo "<p class='ok'>✅ <strong>PHP {$phpVersion}</strong> は最新機能に対応しています。</p>";
} elseif (version_compare($phpVersion, '7.0', '>=')) {
    echo "<p class='warning'>⚠️ <strong>PHP {$phpVersion}</strong> は基本機能に対応していますが、一部制限があります。</p>";
    echo "<p>• match式 → switch文に変換済み</p>";
    echo "<p>• 型宣言 → 削除済み</p>";
} else {
    echo "<p class='error'>❌ <strong>PHP {$phpVersion}</strong> は古いバージョンです。PHP 7.4以上を推奨します。</p>";
}

// サーバー情報
echo "<h3>サーバー情報</h3>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "</p>";

echo "<hr>";
echo "<p><a href='index.php'>← ダッシュボードに戻る</a> | ";
echo "<a href='check-deployment.php'>デプロイ確認ツール</a></p>";

echo "</body></html>";
?>