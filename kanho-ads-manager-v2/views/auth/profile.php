<?php 
$title = 'プロフィール';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    プロフィール設定
                </h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/profile" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <!-- Basic Information -->
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        基本情報
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">お名前 <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="name" 
                                name="name" 
                                value="<?= h($user['name'] ?? '') ?>"
                                required
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                value="<?= h($user['email'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">役割</label>
                            <input 
                                type="text" 
                                class="form-control-plaintext" 
                                value="<?= h($user['role'] ?? '') ?>"
                                readonly
                            >
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">登録日</label>
                            <input 
                                type="text" 
                                class="form-control-plaintext" 
                                value="<?= h($user['created_at'] ? date('Y年n月j日', strtotime($user['created_at'])) : '') ?>"
                                readonly
                            >
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Password Change -->
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-key me-2"></i>
                        パスワード変更
                        <small class="text-muted fs-6">（変更する場合のみ入力）</small>
                    </h5>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">現在のパスワード</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="current_password" 
                            name="current_password"
                        >
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">新しいパスワード</label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="new_password" 
                                name="new_password"
                            >
                            <div class="form-text">
                                8文字以上で、大文字・小文字・数字を含む
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="new_password_confirm" class="form-label">新しいパスワード（確認）</label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="new_password_confirm" 
                                name="new_password_confirm"
                            >
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/dashboard" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-times me-2"></i>
                            キャンセル
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            更新する
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Account Information -->
        <div class="card shadow mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    アカウント情報
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>ユーザーID:</strong> <?= h($user['id'] ?? '') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>最終ログイン:</strong> 
                        <?= h($user['last_login_at'] ? date('Y年n月j日 H:i', strtotime($user['last_login_at'])) : '未記録') ?>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <strong>アカウント状態:</strong> 
                        <span class="badge bg-<?= ($user['is_active'] ?? 0) ? 'success' : 'danger' ?>">
                            <?= ($user['is_active'] ?? 0) ? 'アクティブ' : '無効' ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>メール認証:</strong> 
                        <span class="badge bg-<?= ($user['email_verified_at'] ?? null) ? 'success' : 'warning' ?>">
                            <?= ($user['email_verified_at'] ?? null) ? '認証済み' : '未認証' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const newPasswordConfirm = document.getElementById('new_password_confirm');
    
    function validatePassword() {
        if (newPassword.value !== newPasswordConfirm.value && newPassword.value !== '') {
            newPasswordConfirm.setCustomValidity('パスワードが一致しません');
        } else {
            newPasswordConfirm.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('change', validatePassword);
    newPasswordConfirm.addEventListener('keyup', validatePassword);
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>