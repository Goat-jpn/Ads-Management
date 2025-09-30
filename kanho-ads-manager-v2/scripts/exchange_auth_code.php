<?php
/**
 * Google OAuth認証コードをリフレッシュトークンに交換するスクリプト
 */

if ($argc < 2) {
    echo "Usage: php exchange_auth_code.php [認証コード]\n";
    echo "Example: php exchange_auth_code.php 4/0AX4XfWh...\n";
    exit(1);
}

$authCode = $argv[1];

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
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? 'urn:ietf:wg:oauth:2.0:oob'; // OOBフローに合わせる

if (empty($clientId) || empty($clientSecret)) {
    echo "Error: OAuth credentials not found in .env file\n";
    exit(1);
}

echo "=== リフレッシュトークン取得中 ===\n\n";

// 認証コードをトークンに交換
$tokenData = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'code' => $authCode,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirectUri
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v4/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "CURL Error: $curlError\n";
    exit(1);
}

echo "HTTP Response Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode !== 200) {
    echo "Error: Failed to exchange auth code\n";
    $errorData = json_decode($response, true);
    if ($errorData) {
        echo "Error details: " . json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
    }
    exit(1);
}

$tokenResponse = json_decode($response, true);

if (!$tokenResponse || !isset($tokenResponse['refresh_token'])) {
    echo "Error: No refresh token received\n";
    echo "Response: " . json_encode($tokenResponse, JSON_PRETTY_PRINT) . "\n";
    echo "\nNote: リフレッシュトークンが含まれていない場合は、prompt=consentで再度認証を行ってください\n";
    exit(1);
}

$refreshToken = $tokenResponse['refresh_token'];
$accessToken = $tokenResponse['access_token'];
$expiresIn = $tokenResponse['expires_in'];

echo "✅ 成功! リフレッシュトークンを取得しました\n\n";
echo "=== 取得したトークン ===\n";
echo "Access Token: " . substr($accessToken, 0, 20) . "...\n";
echo "Refresh Token: " . substr($refreshToken, 0, 20) . "...\n";
echo "Expires In: {$expiresIn} seconds\n\n";

echo "=== .env ファイル更新 ===\n";
echo "以下の設定で .env ファイルを更新してください:\n\n";
echo "GOOGLE_REFRESH_TOKEN=$refreshToken\n\n";

// .env ファイルを自動更新
$envContent = file_get_contents($envFile);
$envContent = preg_replace(
    '/GOOGLE_REFRESH_TOKEN=.*/',
    "GOOGLE_REFRESH_TOKEN=$refreshToken",
    $envContent
);

if (file_put_contents($envFile, $envContent)) {
    echo "✅ .env ファイルが自動更新されました!\n\n";
} else {
    echo "⚠️ .env ファイルの自動更新に失敗しました。手動で更新してください。\n\n";
}

// 接続テストを実行
echo "=== 接続テスト実行中 ===\n";
require_once __DIR__ . '/../app/Services/GoogleAdsService.php';
use App\Services\GoogleAdsService;

try {
    $service = new GoogleAdsService();
    $result = $service->testConnection();
    
    if ($result['success']) {
        echo "✅ Google Ads API接続成功!\n";
        echo "見つかったアカウント数: " . $result['accounts_found'] . "\n";
        if (isset($result['sample_accounts'])) {
            echo "サンプルアカウントID: " . implode(', ', $result['sample_accounts']) . "\n";
        }
    } else {
        echo "❌ 接続テスト失敗: " . $result['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ 接続テストエラー: " . $e->getMessage() . "\n";
}

echo "\n=== 完了 ===\n";
echo "ブラウザでアプリケーションをテストできます。\n";