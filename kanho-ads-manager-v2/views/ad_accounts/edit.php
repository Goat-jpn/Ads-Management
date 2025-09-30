<?php 
$title = '広告アカウント編集 - ' . h($account['account_name']);
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-edit me-2"></i>
                広告アカウント編集
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

<?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>入力エラーがあります:</strong>
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
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    アカウント情報
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/ad-accounts/<?= h($account['id']) ?>/edit" id="editAccountForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="row">
                        <!-- 基本情報 -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">基本情報</h6>
                            
                            <div class="mb-3">
                                <label for="client_id" class="form-label">クライアント <span class="text-danger">*</span></label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">選択してください</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>" 
                                                <?= ($account['client_id'] == $client['id'] || (old('client_id') == $client['id'])) ? 'selected' : '' ?>>
                                            <?= h($client['company_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="platform" class="form-label">プラットフォーム <span class="text-danger">*</span></label>
                                <select class="form-select" id="platform" name="platform" required onchange="updatePlatformHints()">
                                    <option value="">選択してください</option>
                                    <option value="google" <?= ($account['platform'] === 'google' || old('platform') === 'google') ? 'selected' : '' ?>>
                                        Google Ads
                                    </option>
                                    <option value="yahoo" <?= ($account['platform'] === 'yahoo' || old('platform') === 'yahoo') ? 'selected' : '' ?>>
                                        Yahoo Ads
                                    </option>
                                </select>
                                <div class="form-text">
                                    <span id="platform-hint">プラットフォームを選択してください</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="account_id" class="form-label">アカウントID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_id" name="account_id" 
                                       value="<?= h(old('account_id', $account['account_id'])) ?>" 
                                       required maxlength="100"
                                       placeholder="例: 123-456-7890">
                                <div class="form-text">
                                    <span id="account-id-hint">プラットフォーム固有のアカウントIDを入力してください</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="account_name" class="form-label">アカウント名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_name" name="account_name" 
                                       value="<?= h(old('account_name', $account['account_name'])) ?>" 
                                       required maxlength="255"
                                       placeholder="例: 株式会社ABC - 検索広告">
                                <div class="form-text">識別しやすいアカウント名を入力してください</div>
                            </div>
                        </div>

                        <!-- 設定情報 -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">設定情報</h6>
                            
                            <div class="mb-3">
                                <label for="currency" class="form-label">通貨</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="JPY" <?= ($account['currency'] === 'JPY' || old('currency') === 'JPY') ? 'selected' : '' ?>>JPY (日本円)</option>
                                    <option value="USD" <?= ($account['currency'] === 'USD' || old('currency') === 'USD') ? 'selected' : '' ?>>USD (米ドル)</option>
                                    <option value="EUR" <?= ($account['currency'] === 'EUR' || old('currency') === 'EUR') ? 'selected' : '' ?>>EUR (ユーロ)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="timezone" class="form-label">タイムゾーン</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="Asia/Tokyo" <?= ($account['timezone'] === 'Asia/Tokyo' || old('timezone') === 'Asia/Tokyo') ? 'selected' : '' ?>>Asia/Tokyo (JST)</option>
                                    <option value="UTC" <?= ($account['timezone'] === 'UTC' || old('timezone') === 'UTC') ? 'selected' : '' ?>>UTC</option>
                                    <option value="America/New_York" <?= ($account['timezone'] === 'America/New_York' || old('timezone') === 'America/New_York') ? 'selected' : '' ?>>America/New_York (EST)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">ステータス</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= ($account['status'] === 'active' || old('status') === 'active') ? 'selected' : '' ?>>アクティブ</option>
                                    <option value="inactive" <?= ($account['status'] === 'inactive' || old('status') === 'inactive') ? 'selected' : '' ?>>非アクティブ</option>
                                    <option value="suspended" <?= ($account['status'] === 'suspended' || old('status') === 'suspended') ? 'selected' : '' ?>>停止中</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sync_enabled" name="sync_enabled" 
                                           value="1" <?= ($account['sync_enabled'] || old('sync_enabled')) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="sync_enabled">
                                        自動データ同期を有効にする
                                    </label>
                                </div>
                                <div class="form-text">有効にすると、定期的にプラットフォームからデータを同期します</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                    <i class="fas fa-trash me-2"></i>
                                    アカウント削除
                                </button>
                                <div class="d-flex gap-2">
                                    <a href="/ad-accounts/<?= h($account['id']) ?>" class="btn btn-secondary">
                                        キャンセル
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-save me-2"></i>
                                        保存
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- サイドバー：ヘルプ情報 -->
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    編集のヒント
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-lightbulb me-2"></i>注意事項</h6>
                    <ul class="mb-0 small">
                        <li>アカウントIDの変更は慎重に行ってください</li>
                        <li>プラットフォームの変更は推奨されません</li>
                        <li>API認証情報は別途設定が必要です</li>
                        <li>自動同期を有効にするとパフォーマンスに影響する場合があります</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <h6><i class="fas fa-key me-2"></i>API認証</h6>
                    <p class="small text-muted">
                        データ同期を行うには、API認証情報の設定が必要です。
                    </p>
                    <a href="/ad-accounts/<?= h($account['id']) ?>/auth" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cog me-1"></i>
                        API認証設定
                    </a>
                </div>
            </div>
        </div>

        <!-- 現在の認証状況 -->
        <div class="card shadow mt-3">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    認証状況
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <?php if (!empty($account['access_token'])): ?>
                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                        <div class="text-success font-weight-bold">認証済み</div>
                        <small class="text-muted">
                            最終更新: <?= $account['updated_at'] ? date('m/d H:i', strtotime($account['updated_at'])) : '不明' ?>
                        </small>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                        <div class="text-warning font-weight-bold">未認証</div>
                        <small class="text-muted">API認証が必要です</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    アカウント削除の確認
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>本当に以下のアカウントを削除しますか？</strong></p>
                <div class="alert alert-warning">
                    <div><strong>アカウント名:</strong> <?= h($account['account_name']) ?></div>
                    <div><strong>アカウントID:</strong> <?= h($account['account_id']) ?></div>
                    <div><strong>プラットフォーム:</strong> <?= \App\Models\AdAccount::getPlatformName($account['platform']) ?></div>
                </div>
                <p class="text-danger small">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    この操作は取り消すことができません。関連するキャンペーンデータも削除されます。
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form method="POST" action="/ad-accounts/<?= h($account['id']) ?>/delete" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>
                        削除実行
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// プラットフォーム別のヒント表示
function updatePlatformHints() {
    const platform = document.getElementById('platform').value;
    const platformHint = document.getElementById('platform-hint');
    const accountIdHint = document.getElementById('account-id-hint');
    
    switch (platform) {
        case 'google':
            platformHint.innerHTML = '<i class="fab fa-google me-1"></i>Google Ads API連携';
            accountIdHint.innerHTML = 'Google Ads のカスタマーID（例: 123-456-7890）';
            break;
        case 'yahoo':
            platformHint.innerHTML = '<i class="fab fa-yahoo me-1"></i>Yahoo Ads API連携';
            accountIdHint.innerHTML = 'Yahoo Ads のアカウントID（例: 1234567890）';
            break;
        default:
            platformHint.innerHTML = 'プラットフォームを選択してください';
            accountIdHint.innerHTML = 'プラットフォーム固有のアカウントIDを入力してください';
    }
}

// フォーム送信時のバリデーション
document.getElementById('editAccountForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>保存中...';
});

// 削除確認モーダル表示
function confirmDelete() {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// ページ読み込み時にヒントを更新
document.addEventListener('DOMContentLoaded', function() {
    updatePlatformHints();
});

// 未保存の変更がある場合の離脱確認
let hasUnsavedChanges = false;
document.querySelectorAll('input, select, textarea').forEach(element => {
    element.addEventListener('change', function() {
        hasUnsavedChanges = true;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '未保存の変更があります。本当に離脱しますか？';
    }
});

document.getElementById('editAccountForm').addEventListener('submit', function() {
    hasUnsavedChanges = false;
});
</script>

<?php 
// セッションのold_inputをクリア
unset($_SESSION['old_input']);
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>