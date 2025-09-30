<?php 
$title = 'ログイン';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header text-center bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    ログイン
                </h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/login" novalidate>
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
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required
                        >
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input 
                            type="checkbox" 
                            class="form-check-input" 
                            id="remember" 
                            name="remember"
                        >
                        <label class="form-check-label" for="remember">
                            ログイン状態を保持する
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            ログイン
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="/forgot-password" class="text-decoration-none">
                        パスワードを忘れましたか？
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
        
        <!-- Demo Credentials -->
        <div class="card mt-3 border-info">
            <div class="card-body bg-light">
                <h6 class="card-title text-info">
                    <i class="fas fa-info-circle me-1"></i>
                    デモ環境
                </h6>
                <p class="card-text small mb-1">
                    <strong>管理者:</strong> admin@kanho-ads.com / admin123
                </p>
                <p class="card-text small mb-0">
                    <strong>ユーザー:</strong> user@kanho-ads.com / user123
                </p>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
unset($_SESSION['old_input']);
require_once __DIR__ . '/../layouts/app.php';
?>