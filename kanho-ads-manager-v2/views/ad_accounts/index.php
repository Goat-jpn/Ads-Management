<?php 
$title = '広告アカウント管理';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-bullhorn me-2"></i>
                広告アカウント管理
            </h1>
            <div class="d-flex gap-2">
                <a href="/ad-accounts/sync" class="btn btn-outline-success">
                    <i class="fas fa-sync me-2"></i>
                    全同期実行
                </a>
                <a href="/ad-accounts/create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    新規追加
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 統計サマリー -->
<div class="row mb-4">
    <?php foreach (['google' => 'Google Ads', 'yahoo' => 'Yahoo Ads'] as $platform => $displayName): ?>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            <?= $displayName ?>
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= ($platformStats[$platform]['active'] ?? 0) ?> / <?= ($platformStats[$platform]['total'] ?? 0) ?>
                        </div>
                        <small class="text-muted">アクティブ / 総数</small>
                    </div>
                    <div class="col-auto">
                        <i class="fab fa-<?= $platform === 'google' ? 'google' : 'yahoo' ?> fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- 検索・フィルタ -->
<div class="row mb-4">
    <div class="col-md-4">
        <form method="GET" action="/ad-accounts" class="d-flex">
            <input type="search" class="form-control me-2" name="search" 
                   placeholder="アカウント名・IDで検索..." 
                   value="<?= h($_GET['search'] ?? '') ?>">
            <button type="submit" class="btn btn-outline-secondary">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="col-md-3">
        <select class="form-select" onchange="filterBy('platform', this.value)">
            <option value="">全てのプラットフォーム</option>
            <option value="google" <?= ($_GET['platform'] ?? '') === 'google' ? 'selected' : '' ?>>Google Ads</option>
            <option value="yahoo" <?= ($_GET['platform'] ?? '') === 'yahoo' ? 'selected' : '' ?>>Yahoo Ads</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" onchange="filterBy('status', this.value)">
            <option value="">全てのステータス</option>
            <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>アクティブ</option>
            <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>非アクティブ</option>
            <option value="suspended" <?= ($_GET['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>停止中</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" onchange="filterBy('client_id', this.value)">
            <option value="">全てのクライアント</option>
            <?php foreach ($clients as $client): ?>
            <option value="<?= $client['id'] ?>" <?= ($_GET['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                <?= h($client['company_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- 広告アカウント一覧テーブル -->
<div class="card shadow">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>アカウント情報</th>
                        <th>クライアント</th>
                        <th>プラットフォーム</th>
                        <th>ステータス</th>
                        <th>最終同期</th>
                        <th>同期設定</th>
                        <th width="150">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-bullhorn fa-2x mb-3"></i><br>
                            <?php if (!empty($_GET['search'])): ?>
                                「<?= h($_GET['search']) ?>」に一致する広告アカウントが見つかりません
                            <?php else: ?>
                                まだ広告アカウントが登録されていません
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?= h($account['id']) ?></td>
                        <td>
                            <div>
                                <strong><?= h($account['account_name']) ?></strong>
                            </div>
                            <small class="text-muted">ID: <?= h($account['account_id']) ?></small>
                            <?php if (!empty($account['currency'])): ?>
                                <br><small class="text-muted">通貨: <?= h($account['currency']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($account['company_name'])): ?>
                                <a href="/clients/<?= $account['client_id'] ?>" class="text-decoration-none">
                                    <?= h($account['company_name']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">クライアント情報なし</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="fab fa-<?= $account['platform'] === 'google' ? 'google' : 'yahoo' ?> me-2"></i>
                                <?= \App\Models\AdAccount::getPlatformName($account['platform']) ?>
                            </div>
                        </td>
                        <td>
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
                        </td>
                        <td>
                            <?php if ($account['last_sync']): ?>
                                <div><?= date('Y/m/d H:i', strtotime($account['last_sync'])) ?></div>
                            <?php else: ?>
                                <span class="text-muted">未実行</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($account['sync_enabled']): ?>
                                <i class="fas fa-check-circle text-success" title="自動同期有効"></i>
                            <?php else: ?>
                                <i class="fas fa-pause-circle text-muted" title="自動同期無効"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="/ad-accounts/<?= h($account['id']) ?>" 
                                   class="btn btn-outline-primary" title="詳細">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/ad-accounts/<?= h($account['id']) ?>/edit" 
                                   class="btn btn-outline-secondary" title="編集">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($account['status'] === 'active'): ?>
                                <a href="/ad-accounts/<?= h($account['id']) ?>/sync" 
                                   class="btn btn-outline-success" title="同期実行">
                                    <i class="fas fa-sync"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterBy(parameter, value) {
    const url = new URL(window.location);
    if (value) {
        url.searchParams.set(parameter, value);
    } else {
        url.searchParams.delete(parameter);
    }
    url.searchParams.delete('page');
    window.location = url.toString();
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>