<?php 
$title = '広告アカウント詳細 - ' . h($account['account_name']);
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-bullhorn me-2"></i>
                広告アカウント詳細
            </h1>
            <div class="d-flex gap-2">
                <a href="/ad-accounts" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    一覧に戻る
                </a>
                <a href="/ad-accounts/<?= h($account['id']) ?>/edit" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>
                    編集
                </a>
                <?php if ($account['status'] === 'active'): ?>
                <a href="/ad-accounts/<?= h($account['id']) ?>/sync" class="btn btn-success">
                    <i class="fas fa-sync me-2"></i>
                    データ同期
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 基本情報カード -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    基本情報
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">アカウント名:</dt>
                            <dd class="col-sm-8"><?= h($account['account_name']) ?></dd>
                            
                            <dt class="col-sm-4">アカウントID:</dt>
                            <dd class="col-sm-8"><?= h($account['account_id']) ?></dd>
                            
                            <dt class="col-sm-4">プラットフォーム:</dt>
                            <dd class="col-sm-8">
                                <div class="d-flex align-items-center">
                                    <i class="fab fa-<?= $account['platform'] === 'google' ? 'google' : 'yahoo' ?> me-2"></i>
                                    <?= \App\Models\AdAccount::getPlatformName($account['platform']) ?>
                                </div>
                            </dd>
                            
                            <dt class="col-sm-4">ステータス:</dt>
                            <dd class="col-sm-8">
                                <?php 
                                $statusClass = [
                                    'active' => 'success',
                                    'inactive' => 'secondary', 
                                    'suspended' => 'warning'
                                ][$account['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $statusClass ?>">
                                    <?= \App\Models\AdAccount::getStatusName($account['status']) ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">通貨:</dt>
                            <dd class="col-sm-8"><?= h($account['currency'] ?? 'JPY') ?></dd>
                            
                            <dt class="col-sm-4">タイムゾーン:</dt>
                            <dd class="col-sm-8"><?= h($account['timezone'] ?? 'Asia/Tokyo') ?></dd>
                            
                            <dt class="col-sm-4">自動同期:</dt>
                            <dd class="col-sm-8">
                                <?php if ($account['sync_enabled']): ?>
                                    <span class="badge bg-success">有効</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">無効</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-4">作成日:</dt>
                            <dd class="col-sm-8"><?= date('Y年m月d日 H:i', strtotime($account['created_at'])) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- クライアント情報カード -->
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2"></i>
                    クライアント情報
                </h5>
            </div>
            <div class="card-body">
                <?php if ($client): ?>
                <dl class="mb-0">
                    <dt>会社名:</dt>
                    <dd class="mb-2">
                        <a href="/clients/<?= $client['id'] ?>" class="text-decoration-none">
                            <?= h($client['company_name']) ?>
                        </a>
                    </dd>
                    
                    <dt>担当者:</dt>
                    <dd class="mb-2"><?= h($client['contact_name'] ?? '未設定') ?></dd>
                    
                    <dt>メール:</dt>
                    <dd class="mb-2">
                        <a href="mailto:<?= h($client['email']) ?>">
                            <?= h($client['email']) ?>
                        </a>
                    </dd>
                    
                    <?php if (!empty($client['phone'])): ?>
                    <dt>電話番号:</dt>
                    <dd class="mb-0"><?= h($client['phone']) ?></dd>
                    <?php endif; ?>
                </dl>
                <?php else: ?>
                <p class="text-muted mb-0">クライアント情報が見つかりません。</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 同期情報カード -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-sync me-2"></i>
                    同期情報
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-primary text-white me-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    最終同期
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php if ($account['last_sync']): ?>
                                        <?= date('m/d H:i', strtotime($account['last_sync'])) ?>
                                    <?php else: ?>
                                        未実行
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-<?= ($account['sync_status'] ?? 'pending') === 'success' ? 'success' : (($account['sync_status'] ?? 'pending') === 'error' ? 'danger' : 'secondary') ?> text-white me-3">
                                <i class="fas fa-<?= ($account['sync_status'] ?? 'pending') === 'success' ? 'check' : (($account['sync_status'] ?? 'pending') === 'error' ? 'exclamation-triangle' : 'question') ?>"></i>
                            </div>
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    ステータス
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php 
                                    $statusText = [
                                        'success' => '正常',
                                        'error' => 'エラー',
                                        'pending' => '待機中'
                                    ][$account['sync_status'] ?? 'pending'] ?? '不明';
                                    echo $statusText;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($account['sync_error_message'])): ?>
                        <div class="alert alert-danger mb-0">
                            <small><strong>エラー詳細:</strong> <?= h($account['sync_error_message']) ?></small>
                        </div>
                        <?php else: ?>
                        <div class="text-muted">
                            <small>エラーはありません</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- API認証情報カード -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        API認証情報
                    </h5>
                    <a href="/ad-accounts/<?= h($account['id']) ?>/auth" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cog me-1"></i>
                        設定
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <?php if (!empty($account['access_token'])): ?>
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <div class="text-success font-weight-bold">認証済み</div>
                                <small class="text-muted">APIトークン設定済み</small>
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                                <div class="text-warning font-weight-bold">未認証</div>
                                <small class="text-muted">APIトークンが必要です</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <?php if (!empty($account['access_token'])): ?>
                        <dl class="row mb-0">
                            <dt class="col-sm-3">トークン:</dt>
                            <dd class="col-sm-9"><code>****<?= substr($account['access_token'], -8) ?></code></dd>
                            
                            <?php if (!empty($account['token_expires_at'])): ?>
                            <dt class="col-sm-3">有効期限:</dt>
                            <dd class="col-sm-9"><?= date('Y年m月d日 H:i', strtotime($account['token_expires_at'])) ?></dd>
                            <?php endif; ?>
                        </dl>
                        <?php else: ?>
                        <p class="text-muted mb-0">
                            API認証情報が設定されていません。<br>
                            データ同期を行うには、適切なAPIトークンの設定が必要です。
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 今後の拡張予定エリア -->
<div class="row">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    パフォーマンス概要
                </h5>
            </div>
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <p class="mb-0">キャンペーンデータ取得後に表示されます</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    同期履歴
                </h5>
            </div>
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-history fa-3x mb-3"></i>
                <p class="mb-0">同期履歴機能は開発中です</p>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>