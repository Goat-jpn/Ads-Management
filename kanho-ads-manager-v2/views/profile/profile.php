<?php 
$title = 'プロフィール管理';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-user me-2"></i>
                プロフィール管理
            </h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    基本情報
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/profile" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-user me-1"></i>
                                ユーザー名
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= h($_SESSION['user_name'] ?? 'Admin User') ?>" required>
                            <div class="invalid-feedback">
                                ユーザー名を入力してください。
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                メールアドレス
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= h($_SESSION['user_email'] ?? 'admin@kanho-ads.com') ?>" required>
                            <div class="invalid-feedback">
                                正しいメールアドレスを入力してください。
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                新しいパスワード
                            </label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text">
                                パスワードを変更する場合のみ入力してください。
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                パスワード確認
                            </label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/dashboard" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            戻る
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    アカウント情報
                </h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">ユーザーID:</dt>
                    <dd class="col-sm-7"><?= h($_SESSION['user_id'] ?? '1') ?></dd>
                    
                    <dt class="col-sm-5">権限:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-success">管理者</span>
                    </dd>
                    
                    <dt class="col-sm-5">最終ログイン:</dt>
                    <dd class="col-sm-7"><?= date('Y年m月d日 H:i') ?></dd>
                    
                    <dt class="col-sm-5">登録日:</dt>
                    <dd class="col-sm-7">2024年1月1日</dd>
                </dl>
                
                <hr>
                
                <h6 class="mb-2">
                    <i class="fas fa-shield-alt me-2"></i>
                    セキュリティ設定
                </h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning btn-sm" disabled>
                        <i class="fas fa-key me-2"></i>
                        二段階認証設定
                    </button>
                    <button class="btn btn-outline-info btn-sm" disabled>
                        <i class="fas fa-history me-2"></i>
                        ログイン履歴
                    </button>
                </div>
                
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    セキュリティ機能は今後実装予定です。
                </small>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>