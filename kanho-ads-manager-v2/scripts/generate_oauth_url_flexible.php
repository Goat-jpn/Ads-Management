<?php
/**
 * Google Ads OAuth認証URL生成スクリプト (柔軟なリダイレクトURI版)
 * 複数のリダイレクトURIオプションから選択可能
 */

require_once __DIR__ . '/../app/Services/GoogleAdsService.php';

// 環境変数を読み込む
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $_ENV[$name] = $value;
        }
    }
}

$clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';

if (empty($clientId)) {
    echo "Error: GOOGLE_CLIENT_ID not found in .env file\n";
    exit(1);
}

// リダイレクトURIオプション
$redirectUriOptions = [
    1 => 'urn:ietf:wg:oauth:2.0:oob',           // Out-of-Band (推奨)
    2 => 'http://localhost/oauth2callback',      // localhost
    3 => 'http://localhost:8080/oauth2callback', // localhost:8080
    4 => 'http://127.0.0.1/oauth2callback',     // 127.0.0.1
    5 => 'https://localhost/oauth2callback',     // HTTPS localhost
];

echo "=== Google Ads OAuth認証URL生成ツール ===\n\n";
echo "利用可能なリダイレクトURIオプション:\n";
foreach ($redirectUriOptions as $key => $uri) {
    $description = '';
    if ($key === 1) $description = ' (推奨 - Google Cloud Consoleの設定不要)';
    echo "$key. $uri$description\n";
}

echo "\nリダイレクトURIを選択してください (1-5): ";
$handle = fopen("php://stdin", "r");
$selection = (int)trim(fgets($handle));
fclose($handle);

if (!isset($redirectUriOptions[$selection])) {
    echo "無効な選択です。デフォルト (Out-of-Band) を使用します。\n";
    $selection = 1;
}

$redirectUri = $redirectUriOptions[$selection];

// OAuth認証URLを生成
$scope = 'https://www.googleapis.com/auth/adwords';
$state = bin2hex(random_bytes(16));

$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'scope' => $scope,
    'response_type' => 'code',
    'access_type' => 'offline',
    'prompt' => 'consent',
    'state' => $state
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

echo "\n=== 生成された認証URL ===\n\n";
echo $authUrl . "\n\n";

if ($selection === 1) {
    echo "=== Out-of-Band フロー手順 ===\n";
    echo "1. 上記URLをブラウザで開いてください\n";
    echo "2. Googleアカウントでログインし、権限を許可してください\n";
    echo "3. 表示される認証コードをコピーしてください\n";
    echo "4. 次のコマンドで認証コードを使用してください:\n";
    echo "   php scripts/exchange_auth_code.php [認証コード]\n\n";
} else {
    echo "=== 通常フロー手順 ===\n";
    echo "1. 上記URLをブラウザで開いてください\n";
    echo "2. Googleアカウントでログインし、権限を許可してください\n";
    echo "3. リダイレクト後のURLから認証コードを取得してください\n";
    echo "4. 次のコマンドで認証コードを使用してください:\n";
    echo "   php scripts/exchange_auth_code.php [認証コード]\n\n";
    echo "注意: Google Cloud Consoleで以下のリダイレクトURIが設定されている必要があります:\n";
    echo "     $redirectUri\n\n";
}

echo "=== 設定情報 ===\n";
echo "Client ID: " . substr($clientId, 0, 20) . "...\n";
echo "Redirect URI: $redirectUri\n";
echo "Scope: $scope\n";
echo "State (CSRF): $state\n";
echo "Developer Token: " . ($_ENV['GOOGLE_DEVELOPER_TOKEN'] ?? 'NOT SET') . "\n";
echo "Login Customer ID: " . ($_ENV['GOOGLE_LOGIN_CUSTOMER_ID'] ?? 'NOT SET') . "\n";