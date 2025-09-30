<?php 
$title = '請求管理';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-file-invoice me-2"></i>
                請求管理
            </h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success" disabled>
                    <i class="fas fa-download me-2"></i>
                    請求書出力
                </button>
                <button class="btn btn-primary" disabled>
                    <i class="fas fa-plus me-2"></i>
                    新しい請求書
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 請求サマリー -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">今月の請求額</h6>
                        <h2 class="mb-0">¥0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-file-invoice-dollar fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">未払い金額</h6>
                        <h2 class="mb-0">¥0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">支払い済み</h6>
                        <h2 class="mb-0">¥0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">アクティブ顧客</h6>
                        <h2 class="mb-0">0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- フィルター -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label">ステータス</label>
                        <select class="form-select" id="status_filter">
                            <option value="">すべて</option>
                            <option value="draft">下書き</option>
                            <option value="sent">送信済み</option>
                            <option value="paid">支払い済み</option>
                            <option value="overdue">期限切れ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="client_filter" class="form-label">クライアント</label>
                        <select class="form-select" id="client_filter">
                            <option value="">すべてのクライアント</option>
                            <!-- クライアント一覧をここに表示 -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="date_range" class="form-label">期間</label>
                        <input type="date" class="form-control" id="date_range">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button class="btn btn-outline-primary" type="button">
                                <i class="fas fa-search me-2"></i>検索
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 請求書一覧 -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    請求書一覧
                </h5>
            </div>
            <div class="card-body">
                <!-- 請求書データがない場合の表示 -->
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">請求書がありません</h5>
                    <p class="text-muted mb-4">
                        まだ請求書が作成されていません。<br>
                        クライアントと広告運用実績に基づいて請求書を作成してください。
                    </p>
                    <div class="d-flex flex-column align-items-center gap-3">
                        <button class="btn btn-primary" disabled>
                            <i class="fas fa-plus me-2"></i>
                            最初の請求書を作成
                        </button>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            請求書機能は今後実装予定です
                        </small>
                    </div>
                </div>
                
                <!-- 将来的な請求書一覧テーブル（非表示） -->
                <div class="table-responsive d-none">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>請求書番号</th>
                                <th>クライアント</th>
                                <th>請求日</th>
                                <th>支払期限</th>
                                <th>金額</th>
                                <th>ステータス</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 請求書データをここに表示 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 最近の支払い -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    最近の支払い履歴
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <p>支払い履歴はありません</p>
                    <small>支払い情報は請求書機能の実装後に表示されます</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.opacity-75 {
    opacity: 0.75;
}
</style>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>