#!/usr/bin/env node

/**
 * 広告管理システム デモサーバー
 * Node.js + SQLiteでPHPシステムの動作をデモンストレーション
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = process.env.PORT || 8080;

// デモデータ
const demoData = {
    clients: [
        {
            id: 1,
            company_name: '株式会社サンプル商事',
            contact_name: '田中太郎',
            email: 'tanaka@sample-corp.co.jp',
            phone: '03-1234-5678',
            contract_start_date: '2024-01-01',
            billing_day: 25,
            payment_terms: 30,
            is_active: true
        },
        {
            id: 2,
            company_name: '有限会社テスト工業',
            contact_name: '佐藤花子', 
            email: 'sato@test-industry.co.jp',
            phone: '06-9876-5432',
            contract_start_date: '2024-02-01',
            billing_day: 20,
            payment_terms: 30,
            is_active: true
        },
        {
            id: 3,
            company_name: 'エクサンプル株式会社',
            contact_name: '鈴木次郎',
            email: 'suzuki@example-inc.co.jp', 
            phone: '052-1111-2222',
            contract_start_date: '2024-03-01',
            billing_day: 25,
            payment_terms: 45,
            is_active: true
        }
    ],
    
    adAccounts: [
        { id: 1, client_id: 1, platform: 'google_ads', account_id: '123-456-7890', account_name: 'サンプル商事 Google広告' },
        { id: 2, client_id: 1, platform: 'yahoo_display', account_id: 'YDN-1234567890', account_name: 'サンプル商事 Yahoo!ディスプレイ広告' },
        { id: 3, client_id: 2, platform: 'google_ads', account_id: '987-654-3210', account_name: 'テスト工業 Google広告' },
        { id: 4, client_id: 2, platform: 'yahoo_display', account_id: 'YDN-0987654321', account_name: 'テスト工業 Yahoo!ディスプレイ広告' },
        { id: 5, client_id: 3, platform: 'google_ads', account_id: '555-666-7777', account_name: 'エクサンプル株式会社 Google広告' }
    ]
};

// デモパフォーマンスデータを生成
function generateDailyTrend(days = 30) {
    const trend = [];
    for (let i = days; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        
        const baseCost = 50000 + Math.random() * 100000;
        const impressions = Math.floor(8000 + Math.random() * 7000);
        const clicks = Math.floor(300 + Math.random() * 200);
        const conversions = Math.floor(10 + Math.random() * 15);
        
        trend.push({
            date_value: date.toISOString().split('T')[0],
            daily_cost: Math.floor(baseCost),
            daily_reported_cost: Math.floor(baseCost * 1.05),
            daily_impressions: impressions,
            daily_clicks: clicks,
            daily_conversions: conversions,
            daily_ctr: ((clicks / impressions) * 100).toFixed(4),
            daily_cpc: (baseCost / clicks).toFixed(2),
            daily_cpa: conversions > 0 ? (baseCost / conversions).toFixed(2) : 0
        });
    }
    return trend;
}

// クライアントパフォーマンスデータを生成
function generateClientPerformance() {
    return demoData.clients.map(client => {
        const accounts = demoData.adAccounts.filter(acc => acc.client_id === client.id);
        const totalCost = 500000 + Math.random() * 500000;
        const impressions = Math.floor(50000 + Math.random() * 50000);
        const clicks = Math.floor(2000 + Math.random() * 1000);
        const conversions = Math.floor(80 + Math.random() * 40);
        
        return {
            id: client.id,
            company_name: client.company_name,
            account_count: accounts.length,
            total_cost: Math.floor(totalCost),
            total_reported_cost: Math.floor(totalCost * 1.05),
            total_impressions: impressions,
            total_clicks: clicks,
            total_conversions: conversions,
            average_ctr: ((clicks / impressions) * 100).toFixed(4),
            average_cpc: (totalCost / clicks).toFixed(2),
            average_cpa: (totalCost / conversions).toFixed(2)
        };
    });
}

// プラットフォーム統計を生成
function generatePlatformStats() {
    return [
        {
            platform: 'google_ads',
            account_count: 3,
            total_cost: 1200000,
            total_reported_cost: 1260000,
            total_impressions: 150000,
            total_clicks: 5200,
            total_conversions: 180,
            average_ctr: 3.47,
            average_cpc: 230.77,
            average_cpa: 6666.67
        },
        {
            platform: 'yahoo_display',
            account_count: 2,
            total_cost: 800000,
            total_reported_cost: 824000,
            total_impressions: 120000,
            total_clicks: 2400,
            total_conversions: 95,
            average_ctr: 2.00,
            average_cpc: 333.33,
            average_cpa: 8421.05
        }
    ];
}

// サマリー統計を生成
function generateSummaryStats() {
    const totalCost = 2000000 + Math.random() * 500000;
    const totalImpressions = 270000 + Math.random() * 50000;
    const totalClicks = 7600 + Math.random() * 1000;
    const totalConversions = 275 + Math.random() * 50;
    
    return {
        active_clients: demoData.clients.filter(c => c.is_active).length,
        active_accounts: demoData.adAccounts.length,
        total_cost: Math.floor(totalCost),
        total_reported_cost: Math.floor(totalCost * 1.05),
        total_impressions: Math.floor(totalImpressions),
        total_clicks: Math.floor(totalClicks),
        total_conversions: Math.floor(totalConversions),
        average_ctr: ((totalClicks / totalImpressions) * 100).toFixed(4),
        average_cpc: (totalCost / totalClicks).toFixed(2),
        average_cpa: (totalCost / totalConversions).toFixed(2),
        cost_growth: (Math.random() * 20 - 10).toFixed(1),
        impressions_growth: (Math.random() * 15 - 5).toFixed(1),
        clicks_growth: (Math.random() * 25 - 10).toFixed(1),
        conversions_growth: (Math.random() * 30 - 15).toFixed(1)
    };
}

// アラートを生成
function generateAlerts() {
    const alerts = [];
    
    if (Math.random() > 0.5) {
        alerts.push({
            type: 'warning',
            title: '契約終了間近のクライアント',
            message: '2件のクライアントの契約が30日以内に終了します',
            count: 2
        });
    }
    
    if (Math.random() > 0.7) {
        alerts.push({
            type: 'info',
            title: '今月請求予定',
            message: '3件のクライアントが今月の請求対象です',
            count: 3
        });
    }
    
    return alerts;
}

// 同期ステータスを生成
function generateSyncStatus() {
    const recentLogs = [
        {
            account_name: 'サンプル商事 Google広告',
            platform: 'google_ads',
            sync_type: 'daily_data',
            status: 'completed',
            execution_time_ms: 1250,
            started_at: new Date(Date.now() - 300000).toISOString()
        },
        {
            account_name: 'テスト工業 Yahoo!ディスプレイ広告',
            platform: 'yahoo_display', 
            sync_type: 'daily_data',
            status: 'completed',
            execution_time_ms: 980,
            started_at: new Date(Date.now() - 600000).toISOString()
        },
        {
            account_name: 'エクサンプル株式会社 Google広告',
            platform: 'google_ads',
            sync_type: 'campaign_data',
            status: 'failed',
            execution_time_ms: null,
            started_at: new Date(Date.now() - 900000).toISOString()
        }
    ];
    
    return {
        recent_logs: recentLogs,
        accounts_needing_sync: 1,
        sync_stats: [
            { sync_type: 'daily_data', total_syncs: 45, successful_syncs: 43, failed_syncs: 2 },
            { sync_type: 'campaign_data', total_syncs: 12, successful_syncs: 11, failed_syncs: 1 }
        ]
    };
}

// HTTPサーバー
const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;
    
    // CORS設定
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }
    
    console.log(`${new Date().toISOString()} - ${req.method} ${pathname}`);
    
    try {
        // API エンドポイント
        if (pathname === '/api/dashboard/data') {
            res.setHeader('Content-Type', 'application/json');
            res.writeHead(200);
            
            const responseData = {
                success: true,
                data: {
                    summary: generateSummaryStats(),
                    client_performance: generateClientPerformance(),
                    platform_stats: generatePlatformStats(),
                    daily_trend: generateDailyTrend(30),
                    billing_stats: {
                        monthly_stats: { total_invoices: 5, paid_amount: 2500000, outstanding_amount: 800000 },
                        overdue_count: 1,
                        overdue_amount: 150000,
                        pending_fees: 320000
                    },
                    sync_status: generateSyncStatus(),
                    alerts: generateAlerts(),
                    date_range: {
                        start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                        end_date: new Date().toISOString().split('T')[0]
                    }
                }
            };
            
            res.end(JSON.stringify(responseData, null, 2));
            return;
        }
        
        if (pathname === '/api/dashboard/quick-stats') {
            res.setHeader('Content-Type', 'application/json');
            res.writeHead(200);
            
            const responseData = {
                success: true,
                data: {
                    active_clients: demoData.clients.length,
                    active_accounts: demoData.adAccounts.length,
                    yesterday_cost: Math.floor(80000 + Math.random() * 40000),
                    yesterday_impressions: Math.floor(12000 + Math.random() * 3000),
                    yesterday_clicks: Math.floor(400 + Math.random() * 100),
                    yesterday_conversions: Math.floor(15 + Math.random() * 10)
                }
            };
            
            res.end(JSON.stringify(responseData, null, 2));
            return;
        }
        
        if (pathname === '/api/clients') {
            res.setHeader('Content-Type', 'application/json');
            res.writeHead(200);
            
            const clientsWithPerformance = demoData.clients.map(client => {
                const performance = generateClientPerformance().find(p => p.id === client.id);
                return {
                    ...client,
                    ad_accounts_count: demoData.adAccounts.filter(acc => acc.client_id === client.id).length,
                    current_month_performance: {
                        total_cost: performance.total_cost,
                        total_conversions: performance.total_conversions,
                        average_cpa: performance.average_cpa
                    }
                };
            });
            
            const responseData = {
                success: true,
                data: {
                    clients: clientsWithPerformance,
                    pagination: {
                        current_page: 1,
                        per_page: 20,
                        total: clientsWithPerformance.length,
                        total_pages: 1
                    }
                }
            };
            
            res.end(JSON.stringify(responseData, null, 2));
            return;
        }
        
        // 静的ファイル配信
        const filePath = pathname === '/' || pathname === '/dashboard' 
            ? path.join(__dirname, 'public/admin/dashboard/index.php')
            : path.join(__dirname, 'public', pathname);
            
        if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
            const ext = path.extname(filePath).toLowerCase();
            const mimeTypes = {
                '.html': 'text/html',
                '.php': 'text/html', // PHPファイルをHTMLとして配信
                '.css': 'text/css',
                '.js': 'application/javascript',
                '.json': 'application/json',
                '.png': 'image/png',
                '.jpg': 'image/jpeg',
                '.gif': 'image/gif',
                '.svg': 'image/svg+xml',
                '.ico': 'image/x-icon'
            };
            
            const contentType = mimeTypes[ext] || 'text/plain';
            res.setHeader('Content-Type', contentType);
            res.writeHead(200);
            
            let content = fs.readFileSync(filePath, 'utf8');
            
            // PHP変数の簡易置換
            if (ext === '.php') {
                content = content.replace(/\<\?php[^?]*\?\>/g, ''); // PHP開始タグを削除
                content = content.replace(/\<\?=\s*\$title[^?]*\?\>/g, 'ダッシュボード'); // タイトル置換
                content = content.replace(/include[^;]*;/g, ''); // include文を削除
                
                // ヘッダーとサイドバーの内容を簡易的に追加
                if (content.includes('<?php include \'../layout/header.php\'; ?>')) {
                    const headerContent = fs.readFileSync(path.join(__dirname, 'public/admin/layout/header.php'), 'utf8');
                    content = content.replace('<?php include \'../layout/header.php\'; ?>', headerContent.replace(/\<\?php[^?]*\?\>/g, ''));
                }
                
                if (content.includes('<?php include \'../layout/sidebar.php\'; ?>')) {
                    const sidebarContent = fs.readFileSync(path.join(__dirname, 'public/admin/layout/sidebar.php'), 'utf8');
                    content = content.replace('<?php include \'../layout/sidebar.php\'; ?>', sidebarContent.replace(/\<\?php[^?]*\?\>/g, ''));
                }
            }
            
            res.end(content);
            return;
        }
        
        // 404 Not Found
        res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(`
            <!DOCTYPE html>
            <html lang="ja">
            <head>
                <meta charset="UTF-8">
                <title>404 - ページが見つかりません</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                    h1 { color: #dc3545; }
                </style>
            </head>
            <body>
                <h1>404 - ページが見つかりません</h1>
                <p>お探しのページは存在しません。</p>
                <a href="/">ダッシュボードに戻る</a>
            </body>
            </html>
        `);
        
    } catch (error) {
        console.error('Server error:', error);
        res.writeHead(500, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(`
            <!DOCTYPE html>
            <html lang="ja">
            <head>
                <meta charset="UTF-8">
                <title>500 - サーバーエラー</title>
            </head>
            <body>
                <h1>500 - 内部サーバーエラー</h1>
                <p>サーバーでエラーが発生しました。</p>
                <pre>${error.message}</pre>
            </body>
            </html>
        `);
    }
});

server.listen(PORT, () => {
    console.log('🚀 広告管理システム デモサーバーが起動しました！');
    console.log(`📱 ブラウザでアクセス: http://localhost:${PORT}`);
    console.log(`📊 ダッシュボード: http://localhost:${PORT}/dashboard`);
    console.log(`🔌 API: http://localhost:${PORT}/api/dashboard/data`);
    console.log('');
    console.log('⏹️  停止するには Ctrl+C を押してください');
});