<?php
// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// シンプルなデータベース接続を使用
require_once __DIR__ . '/config/database/Connection-simple.php';

// 設定値を直接定義
$app_name = 'Kanho Ads Manager';

// データベース接続テスト
try {
    $connectionTest = Database::testConnection();
    if (!$connectionTest['success']) {
        throw new Exception($connectionTest['message']);
    }
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// POSTリクエストの処理
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                
                if ($name) {
                    try {
                        Database::insert('clients', [
                            'name' => $name,
                            'email' => $email ?: null,
                            'phone' => $phone ?: null,
                            'status' => 'active'
                        ]);
                        $message = 'クライアントを追加しました';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'エラー: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                } else {
                    $message = 'クライアント名は必須です';
                    $messageType = 'error';
                }
                break;
                
            case 'toggle_status':
                $id = (int)($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';
                
                if ($id && in_array($status, ['active', 'inactive'])) {
                    try {
                        Database::update('clients', 
                            ['status' => $status], 
                            'id = :id', 
                            ['id' => $id]
                        );
                        $message = 'ステータスを更新しました';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'エラー: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// クライアント一覧の取得
try {
    $clients = Database::select("
        SELECT c.*, 
               COUNT(aa.id) as account_count,
               COALESCE(SUM(dad.cost), 0) as total_cost
        FROM clients c 
        LEFT JOIN ad_accounts aa ON c.id = aa.client_id AND aa.status = 'active'
        LEFT JOIN daily_ad_data dad ON aa.id = dad.ad_account_id 
                                    AND dad.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
} catch (Exception $e) {
    $clients = [];
    $message = 'データの取得に失敗しました: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クライアント管理 - <?php echo htmlspecialchars($app_name); ?></title>
    <style>
        body { 
            font-family: system-ui, sans-serif; 
            margin: 0; padding: 20px; background: #f8f9fa; 
        }
        .container { 
            max-width: 1200px; margin: 0 auto; background: white; 
            padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 30px; }
        .nav { margin-bottom: 30px; }
        .nav a { 
            display: inline-block; padding: 8px 16px; margin-right: 10px; 
            background: #6c757d; color: white; text-decoration: none; 
            border-radius: 5px; font-size: 14px;
        }
        .nav a:hover { background: #5a6268; }
        .nav a.active { background: #007bff; }
        
        .message { 
            padding: 12px; margin: 20px 0; border-radius: 5px; 
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-section { 
            background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; 
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input { 
            width: 100%; max-width: 300px; padding: 8px 12px; 
            border: 1px solid #ddd; border-radius: 4px; 
        }
        .btn { 
            padding: 10px 20px; border: none; border-radius: 5px; 
            cursor: pointer; text-decoration: none; display: inline-block;
            font-size: 14px; transition: background 0.2s;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { 
            padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; 
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .stats { 
            display: flex; gap: 20px; margin: 20px 0; 
        }
        .stat-card { 
            flex: 1; background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; 
        }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { color: #6c757d; font-size: 14px; margin-top: 5px; }
        
        .connection-info {
            background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 20px;
            color: #155724; font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>クライアント管理</h1>
        
        <div class="nav">
            <a href="index.php">ホーム</a>
            <a href="clients-simple.php" class="active">クライアント管理</a>
            <a href="dashboard.php">ダッシュボード</a>
            <a href="test-db.php">DB テスト</a>
        </div>
        
        <div class="connection-info">
            ✅ データベース接続成功 (<?php echo $connectionTest['current_time']; ?>)
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($clients); ?></div>
                <div class="stat-label">総クライアント数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($clients, function($c) { return $c['status'] === 'active'; })); ?>
                </div>
                <div class="stat-label">アクティブ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo array_sum(array_column($clients, 'account_count')); ?>
                </div>
                <div class="stat-label">総広告アカウント数</div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>新規クライアント追加</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">クライアント名 *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label for="phone">電話番号</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <button type="submit" class="btn btn-primary">クライアントを追加</button>
            </form>
        </div>
        
        <h3>クライアント一覧</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>クライアント名</th>
                    <th>メールアドレス</th>
                    <th>電話番号</th>
                    <th>広告アカウント数</th>
                    <th>月間コスト(過去30日)</th>
                    <th>ステータス</th>
                    <th>登録日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo $client['id']; ?></td>
                        <td><?php echo htmlspecialchars($client['name']); ?></td>
                        <td><?php echo htmlspecialchars($client['email'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($client['phone'] ?: '-'); ?></td>
                        <td><?php echo $client['account_count']; ?></td>
                        <td>¥<?php echo number_format($client['total_cost']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $client['status']; ?>">
                                <?php echo $client['status'] === 'active' ? 'アクティブ' : '無効'; ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($client['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $client['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                <button type="submit" class="btn <?php echo $client['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $client['status'] === 'active' ? '無効化' : '有効化'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>