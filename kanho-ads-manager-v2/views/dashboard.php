<?php 
$title = 'ダッシュボード';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>
                ダッシュボード
            </h1>
            <div class="text-muted">
                <?= date('Y年n月j日 (D)', strtotime('now')) ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            総クライアント数
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $clientCount ?? 0 ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            アクティブキャンペーン
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $activeCampaigns ?? 0 ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            今月の広告費
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ¥<?= number_format($monthlyAdSpend ?? 0) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-yen-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            未請求額
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ¥<?= number_format($unpaidAmount ?? 0) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Performance Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-area me-2"></i>
                    パフォーマンス推移
                </h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        過去30日
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">過去7日</a></li>
                        <li><a class="dropdown-item" href="#">過去30日</a></li>
                        <li><a class="dropdown-item" href="#">過去90日</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Distribution -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-pie me-2"></i>
                    プラットフォーム別配分
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="platformChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2">
                        <i class="fas fa-circle text-primary"></i> Google Ads
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-success"></i> Yahoo広告
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Alerts -->
<div class="row">
    <!-- Recent Clients -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users me-2"></i>
                    最近のクライアント
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($recentClients)): ?>
                    <?php foreach ($recentClients as $client): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar bg-primary text-white rounded-circle me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <?= strtoupper(substr($client['name'], 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?= h($client['name']) ?></div>
                            <div class="text-muted small"><?= h($client['email']) ?></div>
                        </div>
                        <div class="text-muted small">
                            <?= date('n/j', strtotime($client['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="text-center">
                        <a href="/clients" class="btn btn-sm btn-outline-primary">すべて表示</a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                        <p>クライアントがまだ登録されていません</p>
                        <a href="/clients/create" class="btn btn-primary">クライアントを追加</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alerts & Notifications -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bell me-2"></i>
                    アラート・通知
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>期限切れ間近:</strong> 3件の契約が今月末に期限切れ
                </div>
                <div class="alert alert-info mb-3" role="alert">
                    <i class="fas fa-sync-alt me-2"></i>
                    <strong>同期完了:</strong> Google Ads データが更新されました
                </div>
                <div class="alert alert-success mb-0" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>請求処理:</strong> 2件の請求書が正常に送信されました
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-rocket me-2"></i>
                    クイックアクション
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="/clients/create" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            クライアント追加
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="/campaigns/create" class="btn btn-outline-success w-100">
                            <i class="fas fa-bullhorn me-2"></i>
                            キャンペーン作成
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="/billing/generate" class="btn btn-outline-info w-100">
                            <i class="fas fa-file-invoice me-2"></i>
                            請求書生成
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="/reports" class="btn btn-outline-warning w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            レポート表示
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dashboard charts (placeholder)
document.addEventListener('DOMContentLoaded', function() {
    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    // Platform Chart  
    const platformCtx = document.getElementById('platformChart').getContext('2d');
    
    // Note: Chart.js implementation would go here
    // For now, just showing placeholder text
    performanceCtx.font = "16px Arial";
    performanceCtx.textAlign = "center";
    performanceCtx.fillText("パフォーマンスチャート（Chart.js実装予定）", 
        performanceCtx.canvas.width/2, performanceCtx.canvas.height/2);
    
    platformCtx.font = "14px Arial";
    platformCtx.textAlign = "center"; 
    platformCtx.fillText("プラットフォームチャート", 
        platformCtx.canvas.width/2, platformCtx.canvas.height/2);
});
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.chart-area, .chart-pie {
    position: relative;
    height: 200px;
}
</style>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/layouts/app.php';
?>