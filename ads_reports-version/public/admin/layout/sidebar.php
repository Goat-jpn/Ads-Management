<nav class="admin-sidebar">
    <div class="sidebar-content">
        <!-- メインメニュー -->
        <ul class="sidebar-menu">
            <li class="menu-item <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">
                <a href="/dashboard" class="menu-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">ダッシュボード</span>
                </a>
            </li>
            
            <li class="menu-item <?= strpos($_SERVER['REQUEST_URI'], 'clients') !== false ? 'active' : '' ?>">
                <a href="/clients" class="menu-link">
                    <i class="fas fa-building"></i>
                    <span class="menu-text">クライアント管理</span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="/accounts" class="menu-link">
                    <i class="fas fa-bullhorn"></i>
                    <span class="menu-text">広告アカウント</span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="/performance" class="menu-link">
                    <i class="fas fa-chart-line"></i>
                    <span class="menu-text">パフォーマンス</span>
                </a>
            </li>
        </ul>
        
        <!-- 請求・手数料セクション -->
        <div class="menu-section">
            <div class="menu-section-title">請求・手数料</div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="/fee-settings" class="menu-link">
                        <i class="fas fa-percentage"></i>
                        <span class="menu-text">手数料設定</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="/cost-markups" class="menu-link">
                        <i class="fas fa-plus-circle"></i>
                        <span class="menu-text">費用上乗せ</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="/invoices" class="menu-link">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="menu-text">請求書管理</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- レポートセクション -->
        <div class="menu-section">
            <div class="menu-section-title">レポート</div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="/reports/monthly" class="menu-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="menu-text">月次レポート</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="/reports/custom" class="menu-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="menu-text">カスタムレポート</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="/reports/export" class="menu-link">
                        <i class="fas fa-download"></i>
                        <span class="menu-text">データエクスポート</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- 設定セクション -->
        <div class="menu-section">
            <div class="menu-section-title">設定</div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="/sync" class="menu-link">
                        <i class="fas fa-sync-alt"></i>
                        <span class="menu-text">データ同期</span>
                        <span class="menu-badge" id="syncBadge" style="display: none;">!</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="/api-settings" class="menu-link">
                        <i class="fas fa-plug"></i>
                        <span class="menu-text">API設定</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="/system-settings" class="menu-link">
                        <i class="fas fa-cogs"></i>
                        <span class="menu-text">システム設定</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="/users" class="menu-link">
                        <i class="fas fa-users-cog"></i>
                        <span class="menu-text">ユーザー管理</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- サイドバーフッター -->
    <div class="sidebar-footer">
        <div class="sync-status" id="syncStatus">
            <div class="sync-status-icon">
                <i class="fas fa-circle text-success"></i>
            </div>
            <div class="sync-status-text">
                <div class="sync-status-title">同期ステータス</div>
                <div class="sync-status-time">最終同期: <span id="lastSyncTime">-</span></div>
            </div>
        </div>
        
        <div class="sidebar-version">
            <small class="text-muted">v1.0.0</small>
        </div>
    </div>
</nav>

<script>
// 同期ステータスの更新
function updateSyncStatus() {
    fetch('/api/sync/status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statusIcon = document.querySelector('.sync-status-icon i');
                const lastSyncTime = document.getElementById('lastSyncTime');
                const syncBadge = document.getElementById('syncBadge');
                
                if (data.data.needs_sync > 0) {
                    statusIcon.className = 'fas fa-circle text-warning';
                    syncBadge.style.display = 'inline';
                    syncBadge.textContent = data.data.needs_sync;
                } else if (data.data.failed_syncs > 0) {
                    statusIcon.className = 'fas fa-circle text-danger';
                    syncBadge.style.display = 'inline';
                    syncBadge.textContent = '!';
                } else {
                    statusIcon.className = 'fas fa-circle text-success';
                    syncBadge.style.display = 'none';
                }
                
                if (data.data.last_sync) {
                    const lastSync = new Date(data.data.last_sync);
                    lastSyncTime.textContent = lastSync.toLocaleString('ja-JP');
                }
            }
        })
        .catch(error => {
            console.error('同期ステータス取得エラー:', error);
            const statusIcon = document.querySelector('.sync-status-icon i');
            statusIcon.className = 'fas fa-circle text-muted';
        });
}

// ページ読み込み時と定期実行
document.addEventListener('DOMContentLoaded', function() {
    updateSyncStatus();
    setInterval(updateSyncStatus, 60000); // 1分おきに更新
});

// アクティブメニューの設定
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        const link = item.querySelector('.menu-link');
        const href = link.getAttribute('href');
        
        if (currentPath === href || (href !== '/' && currentPath.startsWith(href))) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
});
</script>