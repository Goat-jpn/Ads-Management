<?php 
$title = 'キャンペーン管理';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-bullhorn me-2"></i>
                キャンペーン管理
            </h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" disabled>
                    <i class="fas fa-sync me-2"></i>
                    データ同期
                </button>
                <button class="btn btn-primary" disabled>
                    <i class="fas fa-plus me-2"></i>
                    新しいキャンペーン
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 統計サマリー -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">アクティブキャンペーン</h6>
                        <h2 class="mb-0">0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-play-circle fa-2x opacity-75"></i>
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
                        <h6 class="card-title">今月のインプレッション</h6>
                        <h2 class="mb-0">0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-eye fa-2x opacity-75"></i>
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
                        <h6 class="card-title">今月のクリック</h6>
                        <h2 class="mb-0">0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-mouse-pointer fa-2x opacity-75"></i>
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
                        <h6 class="card-title">今月の広告費</h6>
                        <h2 class="mb-0">¥0</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-yen-sign fa-2x opacity-75"></i>
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
                        <label for="platform_filter" class="form-label">プラットフォーム</label>
                        <select class="form-select" id="platform_filter">
                            <option value="">すべて</option>
                            <option value="google">Google Ads</option>
                            <option value="yahoo">Yahoo!広告</option>
                            <option value="meta">Meta広告</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label">ステータス</label>
                        <select class="form-select" id="status_filter">
                            <option value="">すべて</option>
                            <option value="enabled">有効</option>
                            <option value="paused">一時停止</option>
                            <option value="removed">削除</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">キャンペーン名検索</label>
                        <input type="text" class="form-control" id="search" placeholder="キャンペーン名を入力...">
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

<!-- キャンペーン一覧 -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    キャンペーン一覧
                </h5>
            </div>
            <div class="card-body">
                <!-- データが同期されていない場合の表示 -->
                <div class="text-center py-5">
                    <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">キャンペーンデータがありません</h5>
                    <p class="text-muted mb-4">
                        広告アカウントを同期して、キャンペーンデータを取得してください。
                    </p>
                    <div class="d-flex flex-column align-items-center gap-3">
                        <a href="/ad-accounts" class="btn btn-primary">
                            <i class="fas fa-cog me-2"></i>
                            広告アカウント管理
                        </a>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            広告アカウントが同期されると、ここにキャンペーンが表示されます
                        </small>
                    </div>
                </div>
                
                <!-- 将来的なキャンペーン一覧テーブル（非表示） -->
                <div class="table-responsive d-none">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>キャンペーン名</th>
                                <th>プラットフォーム</th>
                                <th>ステータス</th>
                                <th>インプレッション</th>
                                <th>クリック</th>
                                <th>CTR</th>
                                <th>費用</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- キャンペーンデータをここに表示 -->
                        </tbody>
                    </table>
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