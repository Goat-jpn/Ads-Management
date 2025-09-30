<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? h($title) . ' - ' : '' ?><?= config('app.name') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="/assets/css/app.css" rel="stylesheet">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body>
    <!-- Navigation -->
    <?php if (is_logged_in()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-bullhorn me-2"></i>
                <?= config('app.name') ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i> ダッシュボード
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/clients">
                            <i class="fas fa-users me-1"></i> クライアント
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ad-accounts">
                            <i class="fas fa-ad me-1"></i> 広告アカウント
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/campaigns">
                            <i class="fas fa-bullhorn me-1"></i> キャンペーン
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/billing">
                            <i class="fas fa-file-invoice me-1"></i> 請求管理
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?= h($_SESSION['user_name'] ?? 'ユーザー') ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile">
                                <i class="fas fa-user-edit me-2"></i> プロフィール
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">
                                <i class="fas fa-sign-out-alt me-2"></i> ログアウト
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php 
        $flashTypes = ['success', 'error', 'warning', 'info'];
        foreach ($flashTypes as $type):
            if (isset($_SESSION["flash_{$type}"])): 
                $bootstrapType = $type === 'error' ? 'danger' : $type;
        ?>
            <div class="alert alert-<?= $bootstrapType ?> alert-dismissible fade show" role="alert">
                <?= h($_SESSION["flash_{$type}"]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php 
                unset($_SESSION["flash_{$type}"]);
            endif; 
        endforeach; 
        ?>
        
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php 
            unset($_SESSION['errors']);
        endif; 
        ?>
    </div>
    
    <!-- Main Content -->
    <main class="container mt-4">
        <?= $content ?? '' ?>
    </main>
    
    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <div class="container">
            <small>&copy; <?= date('Y') ?> <?= config('app.name') ?>. All rights reserved.</small>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/app.js"></script>
    
    <!-- Page-specific scripts -->
    <?= $scripts ?? '' ?>
</body>
</html>