<?php 
$title = 'パスワードリセット';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header text-center bg-info text-white">
                <h4 class="mb-0">
                    <i class="fas fa-key me-2"></i>
                    パスワードリセット
                </h4>
            </div>
            <div class="card-body p-4">
                <p class="text-muted text-center mb-4">
                    登録したメールアドレスを入力してください。<br>
                    パスワードリセットのリンクをお送りします。
                </p>
                
                <form method="POST" action="/forgot-password" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            value="<?= h($_SESSION['old_input']['email'] ?? '') ?>"
                            required
                            autofocus
                        >
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane me-2"></i>
                            リセットリンクを送信
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

<?php 
$content = ob_get_clean();
unset($_SESSION['old_input']);
require_once __DIR__ . '/../layouts/app.php';
?>