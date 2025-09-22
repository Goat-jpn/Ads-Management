// ダッシュボードJavaScript

class Dashboard {
    constructor() {
        this.charts = {};
        this.data = null;
        this.dateRange = {
            start_date: this.getDefaultStartDate(),
            end_date: this.getDefaultEndDate()
        };
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setupDateRange();
        this.loadDashboardData();
    }
    
    setupEventListeners() {
        // 日付範囲適用ボタン
        document.getElementById('applyDateRange').addEventListener('click', () => {
            this.updateDateRange();
            this.loadDashboardData();
        });
        
        // トレンドメトリック変更
        document.getElementById('trendMetric').addEventListener('change', () => {
            this.updateTrendChart();
        });
        
        // ページの可視性変更時の自動更新
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadDashboardData();
            }
        });
    }
    
    setupDateRange() {
        document.getElementById('startDate').value = this.dateRange.start_date;
        document.getElementById('endDate').value = this.dateRange.end_date;
    }
    
    getDefaultStartDate() {
        const date = new Date();
        date.setDate(1); // 月初
        return date.toISOString().split('T')[0];
    }
    
    getDefaultEndDate() {
        return new Date().toISOString().split('T')[0];
    }
    
    updateDateRange() {
        this.dateRange = {
            start_date: document.getElementById('startDate').value,
            end_date: document.getElementById('endDate').value
        };
    }
    
    async loadDashboardData() {
        try {
            this.showLoading();
            
            const params = new URLSearchParams(this.dateRange);
            const response = await fetch(`/api/dashboard/data?${params}`);
            const result = await response.json();
            
            if (result.success) {
                this.data = result.data;
                this.updateSummaryStats();
                this.updateCharts();
                this.updateTables();
                this.updateAlerts();
            } else {
                this.showError('データの読み込みに失敗しました');
            }
        } catch (error) {
            console.error('Dashboard data loading error:', error);
            this.showError('データの読み込み中にエラーが発生しました');
        } finally {
            this.hideLoading();
        }
    }
    
    updateSummaryStats() {
        const summary = this.data.summary;
        
        // 値の更新
        document.getElementById('activeClients').textContent = summary.active_clients || 0;
        document.getElementById('totalCost').textContent = this.formatCurrency(summary.total_cost || 0);
        document.getElementById('totalClicks').textContent = this.formatNumber(summary.total_clicks || 0);
        document.getElementById('totalConversions').textContent = this.formatNumber(summary.total_conversions || 0);
        
        // 成長率の更新
        this.updateGrowthIndicator('clientsChange', 0); // クライアント数は通常変動しない
        this.updateGrowthIndicator('costChange', summary.cost_growth);
        this.updateGrowthIndicator('clicksChange', summary.clicks_growth);
        this.updateGrowthIndicator('conversionsChange', summary.conversions_growth);
    }
    
    updateGrowthIndicator(elementId, growth) {
        const element = document.getElementById(elementId);
        const value = parseFloat(growth) || 0;
        
        if (value > 0) {
            element.textContent = `+${value.toFixed(1)}%`;
            element.className = 'stat-change positive';
        } else if (value < 0) {
            element.textContent = `${value.toFixed(1)}%`;
            element.className = 'stat-change negative';
        } else {
            element.textContent = '±0.0%';
            element.className = 'stat-change';
        }
    }
    
    updateCharts() {
        this.updateTrendChart();
        this.updatePlatformChart();
    }
    
    updateTrendChart() {
        const canvas = document.getElementById('trendChart');
        const ctx = canvas.getContext('2d');
        
        if (this.charts.trend) {
            this.charts.trend.destroy();
        }
        
        const metric = document.getElementById('trendMetric').value;
        const data = this.prepareTrendData(metric);
        
        this.charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: this.getMetricLabel(metric),
                    data: data.values,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => {
                                if (metric === 'cost') {
                                    return this.formatCurrency(value);
                                } else if (metric === 'ctr' || metric === 'conversion_rate') {
                                    return value.toFixed(2) + '%';
                                } else if (metric === 'cpc' || metric === 'cpa') {
                                    return '¥' + value.toFixed(0);
                                }
                                return this.formatNumber(value);
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 10
                        }
                    }
                }
            }
        });
    }
    
    updatePlatformChart() {
        const canvas = document.getElementById('platformChart');
        const ctx = canvas.getContext('2d');
        
        if (this.charts.platform) {
            this.charts.platform.destroy();
        }
        
        const platformData = this.data.platform_stats || [];
        const labels = platformData.map(item => this.getPlatformLabel(item.platform));
        const values = platformData.map(item => parseFloat(item.total_cost) || 0);
        const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8'];
        
        this.charts.platform = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label;
                                const value = this.formatCurrency(context.raw);
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    updateTables() {
        this.updateClientPerformanceTable();
        this.updateSyncStatusTable();
    }
    
    updateClientPerformanceTable() {
        const tbody = document.querySelector('#clientPerformanceTable tbody');
        tbody.innerHTML = '';
        
        const clients = this.data.client_performance || [];
        
        clients.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${this.escapeHtml(client.company_name)}</strong></td>
                <td><span class="badge badge-info">${client.account_count}</span></td>
                <td>${this.formatCurrency(client.total_cost)}</td>
                <td>${this.formatNumber(client.total_impressions)}</td>
                <td>${this.formatNumber(client.total_clicks)}</td>
                <td>${this.formatNumber(client.total_conversions)}</td>
                <td>${this.formatPercentage(client.average_ctr)}</td>
                <td>${this.formatCurrency(client.average_cpc)}</td>
                <td>${this.formatCurrency(client.average_cpa)}</td>
            `;
            tbody.appendChild(row);
        });
        
        if (clients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">データがありません</td></tr>';
        }
    }
    
    updateSyncStatusTable() {
        const tbody = document.querySelector('#syncStatusTable tbody');
        tbody.innerHTML = '';
        
        const logs = this.data.sync_status?.recent_logs || [];
        
        logs.forEach(log => {
            const row = document.createElement('tr');
            const statusClass = this.getSyncStatusClass(log.status);
            const executionTime = log.execution_time_ms ? `${log.execution_time_ms}ms` : '-';
            
            row.innerHTML = `
                <td>${this.escapeHtml(log.account_name)}</td>
                <td><span class="badge badge-secondary">${this.getPlatformLabel(log.platform)}</span></td>
                <td>${this.escapeHtml(log.sync_type)}</td>
                <td><span class="badge ${statusClass}">${this.escapeHtml(log.status)}</span></td>
                <td>${executionTime}</td>
                <td>${this.formatDateTime(log.started_at)}</td>
            `;
            tbody.appendChild(row);
        });
        
        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">データがありません</td></tr>';
        }
    }
    
    updateAlerts() {
        const container = document.getElementById('alertsContainer');
        container.innerHTML = '';
        
        const alerts = this.data.alerts || [];
        
        if (alerts.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        
        alerts.forEach(alert => {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${alert.type}`;
            alertDiv.innerHTML = `
                <strong>${this.escapeHtml(alert.title)}</strong>
                ${this.escapeHtml(alert.message)}
                ${alert.count ? `<span class="badge badge-${alert.type}">${alert.count}</span>` : ''}
            `;
            container.appendChild(alertDiv);
        });
    }
    
    // ヘルパーメソッド
    prepareTrendData(metric) {
        const dailyTrend = this.data.daily_trend || [];
        
        const labels = dailyTrend.map(item => {
            const date = new Date(item.date_value);
            return date.toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' });
        });
        
        const values = dailyTrend.map(item => {
            const value = parseFloat(item[`daily_${metric}`]) || 0;
            return metric === 'ctr' || metric === 'conversion_rate' ? value * 100 : value;
        });
        
        return { labels, values };
    }
    
    getMetricLabel(metric) {
        const labels = {
            cost: '広告費',
            clicks: 'クリック数',
            conversions: 'コンバージョン数',
            ctr: 'CTR (%)',
            cpc: 'CPC (¥)',
            cpa: 'CPA (¥)'
        };
        return labels[metric] || metric;
    }
    
    getPlatformLabel(platform) {
        const labels = {
            google_ads: 'Google広告',
            yahoo_display: 'Yahoo!ディスプレイ',
            yahoo_search: 'Yahoo!検索'
        };
        return labels[platform] || platform;
    }
    
    getSyncStatusClass(status) {
        const classes = {
            completed: 'badge-success',
            failed: 'badge-danger',
            started: 'badge-warning'
        };
        return classes[status] || 'badge-secondary';
    }
    
    formatCurrency(value) {
        const num = parseFloat(value) || 0;
        return '¥' + num.toLocaleString();
    }
    
    formatNumber(value) {
        const num = parseInt(value) || 0;
        return num.toLocaleString();
    }
    
    formatPercentage(value) {
        const num = parseFloat(value) || 0;
        return num.toFixed(2) + '%';
    }
    
    formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('ja-JP');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showLoading() {
        // ローディング表示の実装
        console.log('Loading...');
    }
    
    hideLoading() {
        // ローディング非表示の実装
        console.log('Loading complete');
    }
    
    showError(message) {
        // エラー表示の実装
        console.error(message);
        alert(message);
    }
}

// DOM読み込み完了時に初期化
document.addEventListener('DOMContentLoaded', function() {
    new Dashboard();
});

// ユーティリティ関数（グローバル）
window.formatCurrency = function(value) {
    const num = parseFloat(value) || 0;
    return '¥' + num.toLocaleString();
};

window.formatNumber = function(value) {
    const num = parseInt(value) || 0;
    return num.toLocaleString();
};