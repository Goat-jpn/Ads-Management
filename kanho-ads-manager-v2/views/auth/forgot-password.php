<?php 
$title = 'パスワード忘れ';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header text-center bg-warning text-dark">
                <h4 class="mb-0">
                    <i class="fas fa-key me-2"></i>
                    パスワード忘れ
                </h4>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <p class="text-muted">
                        登録したメールアドレスを入力してください。<br>
                        パスワードリセット用のリンクをお送りします。
                    </p>
                </div>
                
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
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane me-2"></i>
                            リセットリンクを送信
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <span class="text-muted">パスワードを思い出しましたか？</span>
                    <a href="/login" class="text-decoration-none">
                        ログイン
                    </a>
                </div>
                
                <div class="text-center mt-3">
                    <span class="text-muted">アカウントをお持ちでないですか？</span>
                    <a href="/register" class="text-decoration-none">
                        新規登録
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