<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - 広告費・手数料管理システム</title>
    <link href="/admin/assets/css/admin.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../layout/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../layout/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-tachometer-alt"></i> ダッシュボード</h1>
                <div class="content-actions">
                    <div class="date-range-selector">
                        <input type="date" id="startDate" class="form-control">
                        <span>〜</span>
                        <input type="date" id="endDate" class="form-control">
                        <button type="button" id="applyDateRange" class="btn btn-primary">
                            <i class="fas fa-search"></i> 適用
                        </button>
                    </div>
                </div>
            </div>

            <!-- アラート表示エリア -->
            <div id="alertsContainer" class="alerts-container" style="display: none;">
                <!-- アラートがここに動的に表示される -->
            </div>

            <!-- 概要統計カード -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">アクティブクライアント</div>
                        <div class="stat-value" id="activeClients">-</div>
                        <div class="stat-change" id="clientsChange">-</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-yen-sign text-success"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">総広告費</div>
                        <div class="stat-value" id="totalCost">-</div>
                        <div class="stat-change" id="costChange">-</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-mouse-pointer text-info"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">総クリック数</div>
                        <div class="stat-value" id="totalClicks">-</div>
                        <div class="stat-change" id="clicksChange">-</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bullseye text-warning"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">総コンバージョン数</div>
                        <div class="stat-value" id="totalConversions">-</div>
                        <div class="stat-change" id="conversionsChange">-</div>
                    </div>
                </div>
            </div>

            <!-- チャートエリア -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> 日別トレンド</h3>
                        <div class="chart-controls">
                            <select id="trendMetric" class="form-control">
                                <option value="cost">広告費</option>
                                <option value="clicks">クリック数</option>
                                <option value="conversions">コンバージョン数</option>
                                <option value="ctr">CTR</option>
                                <option value="cpc">CPC</option>
                                <option value="cpa">CPA</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="trendChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> プラットフォーム別割合</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="platformChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- データテーブルエリア -->
            <div class="tables-grid">
                <div class="table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-building"></i> クライアント別パフォーマンス（TOP10）</h3>
                    </div>
                    <div class="table-body">
                        <div class="table-responsive">
                            <table class="table" id="clientPerformanceTable">
                                <thead>
                                    <tr>
                                        <th>クライアント名</th>
                                        <th>アカウント数</th>
                                        <th>広告費</th>
                                        <th>インプレッション</th>
                                        <th>クリック数</th>
                                        <th>コンバージョン数</th>
                                        <th>CTR</th>
                                        <th>CPC</th>
                                        <th>CPA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- データがここに動的に表示される -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-sync-alt"></i> 最新同期ステータス</h3>
                    </div>
                    <div class="table-body">
                        <div class="table-responsive">
                            <table class="table" id="syncStatusTable">
                                <thead>
                                    <tr>
                                        <th>アカウント</th>
                                        <th>プラットフォーム</th>
                                        <th>同期タイプ</th>
                                        <th>ステータス</th>
                                        <th>実行時間</th>
                                        <th>最終同期</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- データがここに動的に表示される -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/admin/assets/js/dashboard.js"></script>
</body>
</html>