<header class="admin-header">
    <div class="header-left">
        <button type="button" class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-logo">
            <i class="fas fa-chart-bar"></i>
            <span>広告管理システム</span>
        </div>
    </div>
    
    <div class="header-center">
        <div class="breadcrumb">
            <span class="breadcrumb-item active"><?= $title ?? 'ダッシュボード' ?></span>
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-actions">
            <!-- 通知アイコン -->
            <div class="header-action" id="notificationIcon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
            </div>
            
            <!-- クイック統計 -->
            <div class="header-quick-stats" id="quickStats">
                <div class="quick-stat">
                    <span class="quick-stat-label">昨日の広告費</span>
                    <span class="quick-stat-value" id="yesterdayAdCost">-</span>
                </div>
            </div>
            
            <!-- ユーザーメニュー -->
            <div class="header-user-menu">
                <button type="button" class="user-menu-toggle" onclick="toggleUserMenu()">
                    <i class="fas fa-user-circle"></i>
                    <span class="user-name"><?= $_SESSION['admin_name'] ?? '管理者' ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-menu-dropdown" id="userMenuDropdown">
                    <a href="/profile" class="menu-item">
                        <i class="fas fa-user"></i> プロフィール
                    </a>
                    <a href="/settings" class="menu-item">
                        <i class="fas fa-cog"></i> 設定
                    </a>
                    <div class="menu-divider"></div>
                    <a href="/logout" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i> ログアウト
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// サイドバートグル
function toggleSidebar() {
    document.body.classList.toggle('sidebar-collapsed');
}

// ユーザーメニュートグル
function toggleUserMenu() {
    const dropdown = document.getElementById('userMenuDropdown');
    dropdown.classList.toggle('show');
}

// クリック外でメニューを閉じる
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.header-user-menu');
    const dropdown = document.getElementById('userMenuDropdown');
    
    if (!userMenu.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// クイック統計の読み込み
function loadQuickStats() {
    fetch('/api/dashboard/quick-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('yesterdayAdCost').textContent = 
                    '¥' + Number(data.data.yesterday_cost || 0).toLocaleString();
            }
        })
        .catch(error => console.error('クイック統計の読み込みエラー:', error));
}

// ページ読み込み時にクイック統計を取得
document.addEventListener('DOMContentLoaded', function() {
    loadQuickStats();
    
    // 5分おきに更新
    setInterval(loadQuickStats, 300000);
});
</script>