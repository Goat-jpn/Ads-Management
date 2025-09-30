<?php 
$title = 'API認証設定 - ' . h($account['account_name']);
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-key me-2"></i>
                API認証設定
            </h1>
            <div class="d-flex gap-2">
                <a href="/ad-accounts/<?= h($account['id']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    詳細に戻る
                </a>
                <a href="/ad-accounts" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-2"></i>
                    一覧に戻る
                </a>
            </div>
        </div>
    </div>
</div>

<!-- アカウント情報 -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fab fa-<?= $account['platform'] === 'google' ? 'google' : 'yahoo' ?> fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1"><?= h($account['account_name']) ?></h5>
                    <div class="text-muted">
                        <?= \App\Models\AdAccount::getPlatformName($account['platform']) ?> | 
                        ID: <?= h($account['account_id']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>エラー:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($_SESSION['errors'] as $error): ?>
            <li><?= h($error) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['errors']); ?>
<?php endif; ?>

<div class="row">
    <!-- 認証フォーム -->
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    API認証情報
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/ad-accounts/<?= h($account['id']) ?>/auth" id="authForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <!-- 現在の認証状況 -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">現在の認証状況</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <?php if (!empty($account['access_token'])): ?>
                                            <i class="fas fa-check-circle text-success fa-2x"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-10">
                                        <?php if (!empty($account['access_token'])): ?>
                                            <div class="text-success font-weight-bold">認証済み</div>
                                            <div class="text-muted small">
                                                アクセストークン: <code>****<?= substr($account['access_token'], -8) ?></code>
                                                <?php if (!empty($account['token_expires_at'])): ?>
                                                    <br>有効期限: <?= date('Y年m月d日 H:i', strtotime($account['token_expires_at'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-danger font-weight-bold">未認証</div>
                                            <div class="text-muted small">APIアクセストークンが設定されていません</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 認証情報入力 -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">認証情報の設定</h6>
                        
                        <div class="mb-3">
                            <label for="access_token" class="form-label">
                                アクセストークン <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="access_token" name="access_token" 
                                      rows="3" required placeholder="<?= $account['platform'] === 'google' ? 'Google Ads API' : 'Yahoo Ads API' ?>のアクセストークンを入力してください"></textarea>
                            <div class="form-text">
                                APIの管理画面から取得したアクセストークンを貼り付けてください
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="refresh_token" class="form-label">
                                リフレッシュトークン
                            </label>
                            <textarea class="form-control" id="refresh_token" name="refresh_token" 
                                      rows="2" placeholder="リフレッシュトークン（オプション）"></textarea>
                            <div class="form-text">
                                トークンの自動更新が必要な場合は設定してください
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="expires_at" class="form-label">
                                トークン有効期限
                            </label>
                            <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                            <div class="form-text">
                                トークンの有効期限がわかる場合は設定してください
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/ad-accounts/<?= h($account['id']) ?>" class="btn btn-secondary">
                            キャンセル
                        </a>
                        <button type="button" class="btn btn-outline-primary" onclick="testConnection()">
                            <i class="fas fa-link me-2"></i>
                            接続テスト
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>
                            保存
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ヘルプ情報 -->
    <div class="col-lg-4">
        <!-- プラットフォーム固有の設定ガイド -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fab fa-<?= $account['platform'] === 'google' ? 'google' : 'yahoo' ?> me-2"></i>
                    <?= \App\Models\AdAccount::getPlatformName($account['platform']) ?> 設定ガイド
                </h5>
            </div>
            <div class="card-body">
                <?php if ($account['platform'] === 'google'): ?>
                <!-- Google Ads設定ガイド -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Google Ads API</h6>
                    <ol class="small mb-0">
                        <li><a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a>でプロジェクトを作成</li>
                        <li>Google Ads APIを有効化</li>
                        <li>OAuth 2.0認証情報を作成</li>
                        <li>アクセストークンを取得</li>
                        <li>下記フォームに貼り付け</li>
                    </ol>
                </div>

                <div class="mt-3">
                    <h6><i class="fas fa-key me-2"></i>必要な権限</h6>
                    <ul class="small text-muted mb-0">
                        <li>https://www.googleapis.com/auth/adwords</li>
                        <li>アカウント読み取り権限</li>
                        <li>キャンペーン管理権限</li>
                    </ul>
                </div>

                <?php else: ?>
                <!-- Yahoo Ads設定ガイド -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Yahoo Ads API</h6>
                    <ol class="small mb-0">
                        <li><a href="https://developer.yahoo.co.jp/" target="_blank">Yahoo Developer Network</a>にアクセス</li>
                        <li>アプリケーションを登録</li>
                        <li>Yahoo Ads APIの利用申請</li>
                        <li>アクセストークンを取得</li>
                        <li>下記フォームに貼り付け</li>
                    </ol>
                </div>

                <div class="mt-3">
                    <h6><i class="fas fa-key me-2"></i>必要な権限</h6>
                    <ul class="small text-muted mb-0">
                        <li>アカウント情報取得</li>
                        <li>キャンペーン情報取得</li>
                        <li>レポート取得</li>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i>
                        認証情報は暗号化されて安全に保存されます
                    </small>
                </div>
            </div>
        </div>

        <!-- トラブルシューティング -->
        <div class="card shadow mt-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    トラブルシューティング
                </h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="troubleshootAccordion">
                    <div class="accordion-item">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                認証エラーが発生する場合
                            </button>
                        </h6>
                        <div id="collapse1" class="accordion-collapse collapse">
                            <div class="accordion-body small">
                                <ul class="mb-0">
                                    <li>トークンの有効期限を確認</li>
                                    <li>APIキーの権限設定を確認</li>
                                    <li>アカウントIDが正しいか確認</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                データ同期が失敗する場合
                            </button>
                        </h6>
                        <div id="collapse2" class="accordion-collapse collapse">
                            <div class="accordion-body small">
                                <ul class="mb-0">
                                    <li>API利用制限に達していないか確認</li>
                                    <li>アカウントが有効か確認</li>
                                    <li>ネットワーク接続を確認</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// フォーム送信時の処理
document.getElementById('authForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>保存中...';
});

// 接続テスト機能（ダミー実装）
function testConnection() {
    const accessToken = document.getElementById('access_token').value.trim();
    
    if (!accessToken) {
        alert('アクセストークンを入力してください。');
        return;
    }
    
    // ダミーの接続テスト
    const testBtn = event.target;
    const originalText = testBtn.innerHTML;
    
    testBtn.disabled = true;
    testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>テスト中...';
    
    setTimeout(() => {
        testBtn.disabled = false;
        testBtn.innerHTML = originalText;
        
        // ランダムに成功/失敗を決定（実際の実装では実際のAPIテストを行う）
        const isSuccess = Math.random() > 0.3;
        
        if (isSuccess) {
            showAlert('success', '接続テストが成功しました！APIにアクセスできます。');
        } else {
            showAlert('danger', '接続テストに失敗しました。トークンや設定を確認してください。');
        }
    }, 2000);
}

// アラート表示ヘルパー
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.querySelector('.col-12');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // 5秒後に自動で消す
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// 未保存の変更がある場合の離脱確認
let hasUnsavedChanges = false;
document.querySelectorAll('input, textarea').forEach(element => {
    element.addEventListener('input', function() {
        hasUnsavedChanges = true;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '未保存の変更があります。本当に離脱しますか？';
    }
});

document.getElementById('authForm').addEventListener('submit', function() {
    hasUnsavedChanges = false;
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>