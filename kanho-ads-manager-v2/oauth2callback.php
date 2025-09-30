<?php
/**
 * Google OAuth2 Callback Handler
 * Google Ads APIのOAuth認証後のコールバックを処理
 */

// 認証コードまたはエラーを取得
$code = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;
$state = $_GET['state'] ?? null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth認証完了 - Kanho Ads Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .code-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">認証エラー</h4>
                        <p>OAuth認証中にエラーが発生しました：</p>
                        <code><?php echo htmlspecialchars($error); ?></code>
                    </div>
                <?php elseif ($code): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading">認証成功！</h4>
                        <p>認証コードが正常に取得されました。下記の手順でリフレッシュトークンを生成してください。</p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>認証コード</h5>
                        </div>
                        <div class="card-body">
                            <div class="code-box" id="authCode">
                                <?php echo htmlspecialchars($code); ?>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm mt-2" onclick="copyCode()">
                                コードをコピー
                            </button>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>次の手順</h5>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li>上記の認証コードをコピーしてください</li>
                                <li>ターミナルで下記のコマンドを実行してください：</li>
                                <div class="code-box mt-2 mb-2">
                                    <code>cd /home/user/webapp/kanho-ads-manager-v2 && php scripts/exchange_auth_code.php "<?php echo htmlspecialchars($code); ?>"</code>
                                </div>
                                <li>リフレッシュトークンが自動的に.envファイルに保存されます</li>
                                <li>Google Ads APIの接続をテストしてください</li>
                            </ol>
                        </div>
                    </div>
                    
                    <?php if ($state): ?>
                    <div class="alert alert-info mt-3">
                        <small><strong>State:</strong> <?php echo htmlspecialchars($state); ?></small>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-warning">
                        <h4 class="alert-heading">パラメータが不足しています</h4>
                        <p>認証コードまたはエラー情報が見つかりませんでした。</p>
                        <p>OAuth認証プロセスを最初からやり直してください。</p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="/kanho-ads-manager-v2/" class="btn btn-primary">アプリケーションに戻る</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyCode() {
            const codeElement = document.getElementById('authCode');
            const textArea = document.createElement('textarea');
            textArea.value = codeElement.textContent;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            // フィードバック表示
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'コピーしました！';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }
    </script>
</body>
</html>