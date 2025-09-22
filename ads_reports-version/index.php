<?php
/**
 * 広告管理システム - メインダッシュボード
 * Xbizサーバー用PHP版
 */

// エラー報告設定（本番環境では無効化）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッション開始
session_start();

// オートロード
require_once 'bootstrap.php';

// 環境変数読み込み
$env = new Environment();

// データベース接続確認
try {
    $connection = Connection::getInstance();
    $dbConnected = true;
} catch (Exception $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// 基本統計データ取得
$dashboardData = [];
if ($dbConnected) {
    try {
        // ダッシュボードコントローラー使用
        $dashboardController = new DashboardController();
        $dashboardData = $dashboardController->getDashboardData();
    } catch (Exception $e) {
        $dashboardData = ['error' => $e->getMessage()];
    }
}

$pageTitle = 'ダッシュボード - 広告費・手数料管理システム';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .dashboard-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/ads_reports/">
                <i class="fas fa-chart-line me-2"></i>
                広告管理システム
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/ads_reports/">
                            <i class="fas fa-home me-1"></i>ダッシュボード
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ads_reports/clients">
                            <i class="fas fa-users me-1"></i>クライアント
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ads_reports/ad-accounts">
                            <i class="fas fa-ad me-1"></i>広告アカウント
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ads_reports/invoices">
                            <i class="fas fa-file-invoice me-1"></i>請求管理
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="fas fa-database me-1"></i>
                            <?php echo $dbConnected ? '<span class="text-success">DB接続OK</span>' : '<span class="text-danger">DB接続エラー</span>'; ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <?php if (!$dbConnected): ?>
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>データベース接続エラー</h4>
            <p><?php echo htmlspecialchars($dbError); ?></p>
        </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">アクティブクライアント</h6>
                                <div class="metric-value" id="active-clients">
                                    <?php echo isset($dashboardData['summary']['active_clients']) ? number_format($dashboardData['summary']['active_clients']) : '0'; ?>
                                </div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">総広告費（今月）</h6>
                                <div class="metric-value" id="total-cost">
                                    ¥<?php echo isset($dashboardData['summary']['total_cost']) ? number_format($dashboardData['summary']['total_cost']) : '0'; ?>
                                </div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-yen-sign fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">総インプレッション</h6>
                                <div class="metric-value" id="total-impressions">
                                    <?php echo isset($dashboardData['summary']['total_impressions']) ? number_format($dashboardData['summary']['total_impressions']) : '0'; ?>
                                </div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-eye fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">平均CPA</h6>
                                <div class="metric-value" id="average-cpa">
                                    ¥<?php echo isset($dashboardData['summary']['average_cpa']) ? number_format($dashboardData['summary']['average_cpa'], 0) : '0'; ?>
                                </div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-target fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            月次パフォーマンス推移
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            クライアント別費用配分
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="clientDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Performance Table -->
        <div class="row">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>
                            クライアント別パフォーマンス
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="clientPerformanceTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>クライアント名</th>
                                        <th>アカウント数</th>
                                        <th>広告費</th>
                                        <th>インプレッション</th>
                                        <th>クリック</th>
                                        <th>コンバージョン</th>
                                        <th>CPA</th>
                                        <th>CTR</th>
                                    </tr>
                                </thead>
                                <tbody id="clientPerformanceBody">
                                    <?php if (isset($dashboardData['client_performance'])): ?>
                                    <?php foreach ($dashboardData['client_performance'] as $client): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($client['company_name']); ?></strong></td>
                                        <td><?php echo number_format($client['account_count']); ?></td>
                                        <td>¥<?php echo number_format($client['total_cost']); ?></td>
                                        <td><?php echo number_format($client['total_impressions']); ?></td>
                                        <td><?php echo number_format($client['total_clicks']); ?></td>
                                        <td><?php echo number_format($client['total_conversions']); ?></td>
                                        <td>¥<?php echo number_format($client['total_cost'] / max($client['total_conversions'], 1), 0); ?></td>
                                        <td><?php echo number_format($client['average_ctr'], 2); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">データを読み込み中...</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Charts JavaScript -->
    <script>
        // Dashboard data from PHP
        const dashboardData = <?php echo json_encode($dashboardData, JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        function initializeCharts() {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月'],
                    datasets: [{
                        label: '広告費 (万円)',
                        data: [120, 150, 180, 200, 170, 190, 220, 210, 240],
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'コンバージョン',
                        data: [45, 55, 70, 80, 65, 75, 90, 85, 95],
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });

            // Client Distribution Chart
            const clientCtx = document.getElementById('clientDistributionChart').getContext('2d');
            new Chart(clientCtx, {
                type: 'doughnut',
                data: {
                    labels: ['サンプル商事', 'テクノロジー', 'マーケティング'],
                    datasets: [{
                        data: [45, 30, 25],
                        backgroundColor: [
                            '#0d6efd',
                            '#198754',
                            '#ffc107'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>