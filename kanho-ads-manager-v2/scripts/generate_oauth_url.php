<?php
/**
 * Google Ads OAuth認証URL生成スクリプト
 * 新しいリフレッシュトークンを取得するためのOAuth認証URLを生成
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
// 一般的なリダイレクトURIオプション
$redirectUriOptions = [
    'https://8000-iuw1rzlgvr4jfgkzvsvpj-6532622b.e2b.dev/oauth2callback',
    'http://localhost/oauth2callback',
    'http://localhost:8080/oauth2callback',
    'http://127.0.0.1/oauth2callback',
    'urn:ietf:wg:oauth:2.0:oob', // Out-of-band (OOB) フロー用
];

$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? $redirectUriOptions[4]; // デフォルトでOOBを使用（最も確実）

if (empty($clientId)) {
    echo "Error: GOOGLE_CLIENT_ID not found in .env file\n";
    exit(1);
}

// OAuth認証URLを生成
$scope = 'https://www.googleapis.com/auth/adwords';
$state = bin2hex(random_bytes(16)); // CSRF保護用の状態

$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'scope' => $scope,
    'response_type' => 'code',
    'access_type' => 'offline', // リフレッシュトークンを取得するため
    'prompt' => 'consent',      // 同意画面を強制表示
    'state' => $state
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

echo "=== Google Ads OAuth認証 ===\n\n";
echo "1. 下記のURLをブラウザで開いてください:\n\n";
echo $authUrl . "\n\n";
echo "2. Googleアカウントでログインし、権限を許可してください\n";
echo "3. リダイレクト後のURLから認証コードを取得してください\n";
echo "4. 取得した認証コードで次のスクリプトを実行してください:\n";
echo "   php scripts/exchange_auth_code.php [認証コード]\n\n";
echo "注意: 認証コードは1回限りの使用で、数分で期限切れになります\n";
echo "State (CSRF保護): $state\n\n";

// OAuth設定情報を表示
echo "=== 設定情報確認 ===\n";
echo "Client ID: " . substr($clientId, 0, 20) . "...\n";
echo "Current Redirect URI: $redirectUri\n";
echo "\n=== 利用可能なリダイレクトURIオプション ===\n";
foreach ($redirectUriOptions as $index => $uri) {
    echo ($index + 1) . ". $uri\n";
}
echo "\n使用中: " . ($redirectUri === 'urn:ietf:wg:oauth:2.0:oob' ? 'Out-of-Band (OOB) フロー' : $redirectUri) . "\n";
echo "Scope: $scope\n";
echo "Developer Token: " . ($_ENV['GOOGLE_DEVELOPER_TOKEN'] ?? 'NOT SET') . "\n";
echo "Login Customer ID: " . ($_ENV['GOOGLE_LOGIN_CUSTOMER_ID'] ?? 'NOT SET') . "\n\n";

if ($redirectUri === 'urn:ietf:wg:oauth:2.0:oob') {
    echo "=== Out-of-Band (OOB) フロー手順 ===\n";
    echo "1. 上記URLをブラウザで開く\n";
    echo "2. Googleアカウントでログインし、権限を許可\n";
    echo "3. 表示される認証コードをコピー\n";
    echo "4. 次のコマンドで認証コードを使用:\n";
    echo "   php scripts/exchange_auth_code.php [認証コード]\n\n";
}