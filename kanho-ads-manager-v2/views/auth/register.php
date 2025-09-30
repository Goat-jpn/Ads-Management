<?php 
$title = '新規登録';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header text-center bg-success text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    新規登録
                </h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/register" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">お名前 <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="name" 
                            name="name" 
                            value="<?= h($_SESSION['old_input']['name'] ?? '') ?>"
                            required
                            autofocus
                        >
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            value="<?= h($_SESSION['old_input']['email'] ?? '') ?>"
                            required
                        >
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード <span class="text-danger">*</span></label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required
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
                    
                    <div class="mb-3 form-check">
                        <input 
                            type="checkbox" 
                            class="form-check-input" 
                            id="terms" 
                            required
                        >
                        <label class="form-check-label" for="terms">
                            <a href="/terms" target="_blank">利用規約</a>と
                            <a href="/privacy" target="_blank">プライバシーポリシー</a>に同意します
                            <span class="text-danger">*</span>
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>
                            アカウントを作成
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <span class="text-muted">既にアカウントをお持ちですか？</span>
                    <a href="/login" class="text-decoration-none">
                        ログイン
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
unset($_SESSION['old_input']);
require_once '../views/layouts/app.php';
?>