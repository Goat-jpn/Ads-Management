<?php 
$title = 'クライアント管理';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>
                クライアント管理
            </h1>
            <a href="/clients/create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                新規登録
            </a>
        </div>
    </div>
</div>

<!-- 検索・フィルタ -->
<div class="row mb-4">
    <div class="col-md-8">
        <form method="GET" action="/clients" class="d-flex">
            <input type="search" class="form-control me-2" name="search" 
                   placeholder="会社名、担当者、メールアドレスで検索..." 
                   value="<?= h($_GET['search'] ?? '') ?>">
            <button type="submit" class="btn btn-outline-secondary">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="col-md-4">
        <select class="form-select" onchange="filterByStatus(this.value)">
            <option value="">全てのステータス</option>
            <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>アクティブ</option>
            <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>非アクティブ</option>
        </select>
    </div>
</div>

<!-- クライアント一覧テーブル -->
<div class="card shadow">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>会社名</th>
                        <th>担当者</th>
                        <th>連絡先</th>
                        <th>タグ</th>
                        <th>ステータス</th>
                        <th>登録日</th>
                        <th width="120">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-users fa-2x mb-3"></i><br>
                            <?php if (!empty($_GET['search'])): ?>
                                「<?= h($_GET['search']) ?>」に一致するクライアントが見つかりません
                            <?php else: ?>
                                まだクライアントが登録されていません
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= h($client['id']) ?></td>
                        <td>
                            <a href="/clients/<?= h($client['id']) ?>" class="text-decoration-none">
                                <strong><?= h($client['company_name']) ?></strong>
                            </a>
                        </td>
                        <td><?= h($client['contact_person'] ?? '-') ?></td>
                        <td>
                            <div class="small">
                                <?php if (!empty($client['email'])): ?>
                                    <div><i class="fas fa-envelope me-1"></i> <a href="mailto:<?= h($client['email']) ?>"><?= h($client['email']) ?></a></div>
                                <?php endif; ?>
                                <?php if (!empty($client['phone'])): ?>
                                    <div><i class="fas fa-phone me-1"></i> <a href="tel:<?= h($client['phone']) ?>"><?= h($client['phone']) ?></a></div>
                                <?php endif; ?>
                                <?php if (empty($client['email']) && empty($client['phone'])): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?= format_tags($client['tags'] ?? '') ?>
                        </td>
                        <td>
                            <?php 
                            $statusClass = $client['status'] === 'active' ? 'success' : 'secondary';
                            $statusText = $client['status'] === 'active' ? 'アクティブ' : '非アクティブ';
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                        </td>
                        <td>
                            <?= date('Y/m/d', strtotime($client['created_at'])) ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="/clients/<?= h($client['id']) ?>" 
                                   class="btn btn-outline-primary" title="詳細">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/clients/<?= h($client['id']) ?>/edit" 
                                   class="btn btn-outline-secondary" title="編集">
                                    <i class="fas fa-edit"></i>
                                </a>
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

<!-- ページネーション -->
<?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
<nav aria-label="ページネーション" class="mt-4">
    <ul class="pagination justify-content-center">
        <?php if ($pagination['has_prev']): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <?php 
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
        ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
        
        <?php if ($pagination['has_next']): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <div class="text-center text-muted">
        <?= $pagination['total_count'] ?>件中 
        <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?>-<?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count']) ?>件を表示
    </div>
</nav>
<?php endif; ?>

<script>
function filterByStatus(status) {
    const url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('page');
    window.location = url.toString();
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>