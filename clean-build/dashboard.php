<?php
// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// 必要なファイルの読み込み
require_once __DIR__ . '/app/utils/Environment.php';
require_once __DIR__ . '/config/database/Connection.php';

try {
    Environment::load();
    $pdo = Database::getInstance();
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
}

// ダッシュボードデータの取得
try {
    // 基本統計
    $stats = Database::selectOne("
        SELECT 
            COUNT(DISTINCT c.id) as total_clients,
            COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.id END) as active_clients,
            COUNT(DISTINCT aa.id) as total_accounts,
            COUNT(DISTINCT CASE WHEN aa.status = 'active' THEN aa.id END) as active_accounts
        FROM clients c
        LEFT JOIN ad_accounts aa ON c.id = aa.client_id
    ");
    
    // 今月のデータ
    $monthly_data = Database::selectOne("
        SELECT 
            COALESCE(SUM(dad.cost), 0) as total_cost,
            COALESCE(SUM(dad.clicks), 0) as total_clicks,
            COALESCE(SUM(dad.impressions), 0) as total_impressions,
            COALESCE(SUM(dad.conversions), 0) as total_conversions
        FROM daily_ad_data dad
        WHERE dad.date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    
    // 過去30日のトレンド
    $trend_data = Database::select("
        SELECT 
            dad.date,
            SUM(dad.cost) as daily_cost,
            SUM(dad.clicks) as daily_clicks,
            SUM(dad.conversions) as daily_conversions
        FROM daily_ad_data dad
        WHERE dad.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY dad.date
        ORDER BY dad.date ASC
    ");
    
    // クライアント別パフォーマンス
    $client_performance = Database::select("
        SELECT 
            c.id,
            c.name,
            COUNT(DISTINCT aa.id) as account_count,
            COALESCE(SUM(dad.cost), 0) as total_cost,
            COALESCE(SUM(dad.clicks), 0) as total_clicks,
            COALESCE(SUM(dad.conversions), 0) as total_conversions,
            CASE 
                WHEN SUM(dad.clicks) > 0 THEN ROUND(SUM(dad.cost) / SUM(dad.clicks), 2)
                ELSE 0 
            END as cpc,
            CASE 
                WHEN SUM(dad.conversions) > 0 THEN ROUND(SUM(dad.cost) / SUM(dad.conversions), 2)
                ELSE 0 
            END as cpa
        FROM clients c
        LEFT JOIN ad_accounts aa ON c.id = aa.client_id AND aa.status = 'active'
        LEFT JOIN daily_ad_data dad ON aa.id = dad.ad_account_id 
                                    AND dad.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        WHERE c.status = 'active'
        GROUP BY c.id, c.name
        HAVING account_count > 0
        ORDER BY total_cost DESC
        LIMIT 10
    ");
    
} catch (Exception $e) {
    $error_message = 'データの取得に失敗しました: ' . $e->getMessage();
    $stats = ['total_clients' => 0, 'active_clients' => 0, 'total_accounts' => 0, 'active_accounts' => 0];
    $monthly_data = ['total_cost' => 0, 'total_clicks' => 0, 'total_impressions' => 0, 'total_conversions' => 0];
    $trend_data = [];
    $client_performance = [];
}

$app_name = Environment::get('APP_NAME', 'Ads Manager');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - <?php echo htmlspecialchars($app_name); ?></title>
    <style>
        body { 
            font-family: system-ui, sans-serif; 
            margin: 0; padding: 20px; background: #f8f9fa; 
        }
        .container { 
            max-width: 1400px; margin: 0 auto; background: white; 
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
        
        .error { 
            background: #f8d7da; color: #721c24; padding: 12px; 
            border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0; 
        }
        
        .stats-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; margin-bottom: 30px; 
        }
        .stat-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 25px; border-radius: 10px; text-align: center;
        }
        .stat-card.green { 
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); 
        }
        .stat-card.orange { 
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); 
        }
        .stat-card.blue { 
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); 
        }
        .stat-number { font-size: 36px; font-weight: bold; margin-bottom: 10px; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        
        .dashboard-grid { 
            display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px; 
        }
        .chart-section, .performance-section { 
            background: #f8f9fa; padding: 25px; border-radius: 10px; 
        }
        .chart-section h3, .performance-section h3 { 
            margin-top: 0; color: #333; 
        }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: white; font-weight: 600; font-size: 13px; }
        tr:hover { background: rgba(0,123,255,0.05); }
        td { font-size: 14px; }
        
        .trend-chart { 
            background: white; border-radius: 8px; padding: 20px; 
            border: 1px solid #e9ecef; margin-top: 15px;
        }
        .trend-item { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 8px 0; border-bottom: 1px solid #f0f0f0; 
        }
        .trend-item:last-child { border-bottom: none; }
        .trend-date { font-size: 13px; color: #6c757d; }
        .trend-value { font-weight: 600; color: #007bff; }
        
        .no-data { 
            text-align: center; color: #6c757d; padding: 40px; 
            background: white; border-radius: 8px; margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 ダッシュボード</h1>
        
        <div class="nav">
            <a href="index.php">ホーム</a>
            <a href="clients.php">クライアント管理</a>
            <a href="dashboard.php" class="active">ダッシュボード</a>
            <a href="test-db.php">DB テスト</a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['active_clients']); ?></div>
                <div class="stat-label">アクティブクライアント</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo number_format($stats['active_accounts']); ?></div>
                <div class="stat-label">アクティブ広告アカウント</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-number">¥<?php echo number_format($monthly_data['total_cost']); ?></div>
                <div class="stat-label">今月の総広告費</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-number"><?php echo number_format($monthly_data['total_conversions']); ?></div>
                <div class="stat-label">今月のコンバージョン数</div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="chart-section">
                <h3>📈 過去30日間のトレンド</h3>
                <?php if (count($trend_data) > 0): ?>
                    <div class="trend-chart">
                        <?php foreach (array_slice(array_reverse($trend_data), 0, 10) as $trend): ?>
                            <div class="trend-item">
                                <span class="trend-date"><?php echo date('m/d', strtotime($trend['date'])); ?></span>
                                <span class="trend-value">¥<?php echo number_format($trend['daily_cost']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        📊 データがありません<br>
                        <small>広告データを追加すると、ここにトレンドが表示されます</small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="performance-section">
                <h3>🏆 クライアントパフォーマンス</h3>
                <?php if (count($client_performance) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>クライアント</th>
                                <th>広告費</th>
                                <th>CPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($client_performance as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($client['name'], 0, 20, '...')); ?></td>
                                    <td>¥<?php echo number_format($client['total_cost']); ?></td>
                                    <td>
                                        <?php if ($client['cpa'] > 0): ?>
                                            ¥<?php echo number_format($client['cpa']); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        🎯 データがありません<br>
                        <small>クライアントと広告データを追加してください</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: #1565c0;">📋 今月の概要</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; font-size: 14px;">
                <div><strong>インプレッション:</strong><br><?php echo number_format($monthly_data['total_impressions']); ?></div>
                <div><strong>クリック数:</strong><br><?php echo number_format($monthly_data['total_clicks']); ?></div>
                <div><strong>コンバージョン:</strong><br><?php echo number_format($monthly_data['total_conversions']); ?></div>
                <div><strong>CTR:</strong><br>
                    <?php 
                    $ctr = $monthly_data['total_impressions'] > 0 
                        ? round(($monthly_data['total_clicks'] / $monthly_data['total_impressions']) * 100, 2) 
                        : 0;
                    echo $ctr . '%';
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>