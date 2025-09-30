<?php 
$title = 'ページが見つかりません';
ob_start();
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="text-center py-5">
                <div class="error-404 mb-4">
                    <h1 class="display-1 text-primary fw-bold">404</h1>
                    <h2 class="h4 text-muted mb-4">ページが見つかりません</h2>
                </div>
                
                <div class="mb-4">
                    <p class="text-muted">
                        申し訳ございませんが、お探しのページは見つかりませんでした。<br>
                        URLをご確認いただくか、以下のリンクから移動してください。
                    </p>
                </div>
                
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        ホームに戻る
                    </a>
                    
                    <?php if (isset($_SESSION['user'])): ?>
                    <a href="/dashboard" class="btn btn-outline-secondary">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        ダッシュボード
                    </a>
                    <?php endif; ?>
                    
                    <button onclick="history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        前のページに戻る
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-404 {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}
</style>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>