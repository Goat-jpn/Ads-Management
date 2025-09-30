<?php 
$title = '新しいパスワード設定';
$token = $_GET['token'] ?? '';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header text-center bg-warning text-dark">
                <h4 class="mb-0">
                    <i class="fas fa-lock me-2"></i>
                    新しいパスワード設定
                </h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/reset-password" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="token" value="<?= h($token) ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required
                            autofocus
                        >
                        <div class="form-text">
                            パスワードは8文字以上で、大文字・小文字・数字を含む必要があります
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">パスワード（確認） <span class="text-danger">*</span></label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password_confirm" 
                            name="password_confirm" 
                            required
                        >
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>
                            パスワードを更新
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="/login" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>
                        ログインに戻る
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');
    
    function validatePassword() {
        if (password.value !== passwordConfirm.value) {
            passwordConfirm.setCustomValidity('パスワードが一致しません');
        } else {
            passwordConfirm.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    passwordConfirm.addEventListener('keyup', validatePassword);
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>