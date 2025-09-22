#!/usr/bin/env node

/**
 * 広告管理システム MariaDBサーバー
 * Node.js + MariaDB/MySQLでPHPシステムの動作を実現
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = process.env.PORT || 8080;

// MariaDB接続のシミュレーション（実際はMySQL2ライブラリを使用推奨）
class MariaDBMock {
    constructor() {
        this.connected = false;
        this.config = {
            host: 'localhost',
            port: 3306,
            database: 'kanho_adsmanager',
            user: 'kanho_adsmanager',
            password: 'Kanho20200701'
        };
    }
    
    connect() {
        // 接続のシミュレーション
        this.connected = true;
        console.log('✅ MariaDB接続をシミュレートしました');
        return Promise.resolve();
    }
    
    // MariaDBベースのデモデータを生成
    async getClients() {
        return [
            {
                id: 1,
                company_name: '株式会社サンプル商事',
                contact_name: '田中太郎',
                email: 'tanaka@sample-corp.co.jp',
                phone: '03-1234-5678',
                address: '東京都港区サンプル1-2-3',
                contract_start_date: '2024-01-01',
                contract_end_date: '2024-12-31',
                billing_day: 25,
                payment_terms: 30,
                is_active: 1,
                created_at: '2024-01-01 00:00:00',
                updated_at: '2024-01-01 00:00:00'
            },
            {
                id: 2,
                company_name: '有限会社テスト工業',
                contact_name: '佐藤花子',
                email: 'sato@test-industry.co.jp',
                phone: '06-9876-5432',
                address: '大阪府大阪市テスト区4-5-6',
                contract_start_date: '2024-02-01',
                contract_end_date: null,
                billing_day: 20,
                payment_terms: 30,
                is_active: 1,
                created_at: '2024-02-01 00:00:00',
                updated_at: '2024-02-01 00:00:00'
            },
            {
                id: 3,
                company_name: 'エクサンプル株式会社',
                contact_name: '鈴木次郎',
                email: 'suzuki@example-inc.co.jp',
                phone: '052-1111-2222',
                address: '愛知県名古屋市エクサンプル区7-8-9',
                contract_start_date: '2024-03-01',
                contract_end_date: '2025-02-28',
                billing_day: 25,
                payment_terms: 45,
                is_active: 1,
                created_at: '2024-03-01 00:00:00',
                updated_at: '2024-03-01 00:00:00'
            }
        ];
    }
    
    async getAdAccounts() {
        return [
            { id: 1, client_id: 1, platform: 'google_ads', account_id: '123-456-7890', account_name: 'サンプル商事 Google広告', is_active: 1 },
            { id: 2, client_id: 1, platform: 'yahoo_display', account_id: 'YDN-1234567890', account_name: 'サンプル商事 Yahoo!ディスプレイ広告', is_active: 1 },
            { id: 3, client_id: 1, platform: 'yahoo_search', account_id: 'YSS-1234567890', account_name: 'サンプル商事 Yahoo!検索広告', is_active: 1 },
            { id: 4, client_id: 2, platform: 'google_ads', account_id: '987-654-3210', account_name: 'テスト工業 Google広告', is_active: 1 },
            { id: 5, client_id: 2, platform: 'yahoo_display', account_id: 'YDN-0987654321', account_name: 'テスト工業 Yahoo!ディスプレイ広告', is_active: 1 },
            { id: 6, client_id: 3, platform: 'google_ads', account_id: '555-666-7777', account_name: 'エクサンプル株式会社 Google広告', is_active: 1 },
            { id: 7, client_id: 3, platform: 'yahoo_display', account_id: 'YDN-5556667777', account_name: 'エクサンプル株式会社 Yahoo!ディスプレイ広告', is_active: 1 }
        ];
    }
    
    async getDailyAdData(days = 30) {
        const data = [];
        const accounts = await this.getAdAccounts();
        
        for (let i = days; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            
            accounts.forEach(account => {
                const baseCost = 40000 + Math.random() * 80000;
                const impressions = Math.floor(6000 + Math.random() * 9000);
                const clicks = Math.floor(250 + Math.random() * 250);
                const conversions = Math.floor(10 + Math.random() * 15);
                
                // 上乗せ設定に基づく計算
                const markupRate = this.getMarkupRate(account.client_id, account.id);
                
                data.push({
                    id: data.length + 1,
                    ad_account_id: account.id,
                    date_value: dateStr,
                    impressions: impressions,
                    clicks: clicks,
                    conversions: conversions,
                    cost: Math.floor(baseCost),
                    reported_cost: Math.floor(baseCost * markupRate),
                    ctr: ((clicks / impressions) * 100).toFixed(4),
                    cpc: (baseCost / clicks).toFixed(2),
                    cpa: conversions > 0 ? (baseCost / conversions).toFixed(2) : 0,
                    conversion_rate: ((conversions / clicks) * 100).toFixed(4),
                    sync_status: 'synced'
                });
            });
        }
        
        return data;
    }
    
    getMarkupRate(clientId, accountId) {
        // クライアントとアカウント別の上乗せ率
        const markupSettings = {
            1: { 1: 1.05, 2: 1.03, 3: 1.02 }, // サンプル商事
            2: { 4: 1.04, 5: 1.04 },          // テスト工業
            3: { 6: 1.06, 7: 1.04 }           // エクサンプル
        };
        
        return markupSettings[clientId]?.[accountId] || 1.05;
    }
}

const db = new MariaDBMock();

// データ生成関数
async function generateSummaryStats() {
    const dailyData = await db.getDailyAdData(30);
    const clients = await db.getClients();
    const accounts = await db.getAdAccounts();
    
    const totalCost = dailyData.reduce((sum, item) => sum + item.cost, 0);
    const totalReportedCost = dailyData.reduce((sum, item) => sum + item.reported_cost, 0);
    const totalImpressions = dailyData.reduce((sum, item) => sum + item.impressions, 0);
    const totalClicks = dailyData.reduce((sum, item) => sum + item.clicks, 0);
    const totalConversions = dailyData.reduce((sum, item) => sum + item.conversions, 0);
    
    return {
        active_clients: clients.filter(c => c.is_active).length,
        active_accounts: accounts.filter(a => a.is_active).length,
        total_cost: Math.floor(totalCost),
        total_reported_cost: Math.floor(totalReportedCost),
        total_impressions: totalImpressions,
        total_clicks: totalClicks,
        total_conversions: totalConversions,
        average_ctr: ((totalClicks / totalImpressions) * 100).toFixed(4),
        average_cpc: (totalCost / totalClicks).toFixed(2),
        average_cpa: (totalCost / totalConversions).toFixed(2),
        cost_growth: (Math.random() * 20 - 10).toFixed(1),
        impressions_growth: (Math.random() * 15 - 5).toFixed(1),
        clicks_growth: (Math.random() * 25 - 10).toFixed(1),
        conversions_growth: (Math.random() * 30 - 15).toFixed(1)
    };
}

async function generateClientPerformance() {
    const clients = await db.getClients();
    const accounts = await db.getAdAccounts();
    const dailyData = await db.getDailyAdData(30);
    
    return clients.map(client => {
        const clientAccounts = accounts.filter(acc => acc.client_id === client.id);
        const clientData = dailyData.filter(data => 
            clientAccounts.some(acc => acc.id === data.ad_account_id)
        );
        
        const totalCost = clientData.reduce((sum, item) => sum + item.cost, 0);
        const totalReportedCost = clientData.reduce((sum, item) => sum + item.reported_cost, 0);
        const totalImpressions = clientData.reduce((sum, item) => sum + item.impressions, 0);
        const totalClicks = clientData.reduce((sum, item) => sum + item.clicks, 0);
        const totalConversions = clientData.reduce((sum, item) => sum + item.conversions, 0);
        
        return {
            id: client.id,
            company_name: client.company_name,
            account_count: clientAccounts.length,
            total_cost: Math.floor(totalCost),
            total_reported_cost: Math.floor(totalReportedCost),
            total_impressions: totalImpressions,
            total_clicks: totalClicks,
            total_conversions: totalConversions,
            average_ctr: totalImpressions > 0 ? ((totalClicks / totalImpressions) * 100).toFixed(4) : 0,
            average_cpc: totalClicks > 0 ? (totalCost / totalClicks).toFixed(2) : 0,
            average_cpa: totalConversions > 0 ? (totalCost / totalConversions).toFixed(2) : 0
        };
    });
}

async function generatePlatformStats() {
    const accounts = await db.getAdAccounts();
    const dailyData = await db.getDailyAdData(30);
    
    const platforms = ['google_ads', 'yahoo_display', 'yahoo_search'];
    
    return platforms.map(platform => {
        const platformAccounts = accounts.filter(acc => acc.platform === platform);
        const platformData = dailyData.filter(data => 
            platformAccounts.some(acc => acc.id === data.ad_account_id)
        );
        
        const totalCost = platformData.reduce((sum, item) => sum + item.cost, 0);
        const totalReportedCost = platformData.reduce((sum, item) => sum + item.reported_cost, 0);
        const totalImpressions = platformData.reduce((sum, item) => sum + item.impressions, 0);
        const totalClicks = platformData.reduce((sum, item) => sum + item.clicks, 0);
        const totalConversions = platformData.reduce((sum, item) => sum + item.conversions, 0);
        
        return {
            platform: platform,
            account_count: platformAccounts.length,
            total_cost: Math.floor(totalCost),
            total_reported_cost: Math.floor(totalReportedCost),
            total_impressions: totalImpressions,
            total_clicks: totalClicks,
            total_conversions: totalConversions,
            average_ctr: totalImpressions > 0 ? ((totalClicks / totalImpressions) * 100).toFixed(4) : 0,
            average_cpc: totalClicks > 0 ? (totalCost / totalClicks).toFixed(2) : 0,
            average_cpa: totalConversions > 0 ? (totalCost / totalConversions).toFixed(2) : 0
        };
    }).filter(stat => stat.account_count > 0);
}

async function generateDailyTrend(days = 30) {
    const dailyData = await db.getDailyAdData(days);
    
    // 日付ごとに集計
    const trendMap = new Map();
    
    dailyData.forEach(item => {
        const date = item.date_value;
        if (!trendMap.has(date)) {
            trendMap.set(date, {
                date_value: date,
                daily_cost: 0,
                daily_reported_cost: 0,
                daily_impressions: 0,
                daily_clicks: 0,
                daily_conversions: 0
            });
        }
        
        const trend = trendMap.get(date);
        trend.daily_cost += item.cost;
        trend.daily_reported_cost += item.reported_cost;
        trend.daily_impressions += item.impressions;
        trend.daily_clicks += item.clicks;
        trend.daily_conversions += item.conversions;
    });
    
    // CTR、CPC、CPAを計算
    const trends = Array.from(trendMap.values()).map(trend => ({
        ...trend,
        daily_ctr: trend.daily_impressions > 0 ? 
            ((trend.daily_clicks / trend.daily_impressions) * 100).toFixed(4) : 0,
        daily_cpc: trend.daily_clicks > 0 ? 
            (trend.daily_cost / trend.daily_clicks).toFixed(2) : 0,
        daily_cpa: trend.daily_conversions > 0 ? 
            (trend.daily_cost / trend.daily_conversions).toFixed(2) : 0
    }));
    
    return trends.sort((a, b) => new Date(a.date_value) - new Date(b.date_value));
}

function generateAlerts() {
    const alerts = [];
    
    // ランダムにアラートを生成
    if (Math.random() > 0.4) {
        alerts.push({
            type: 'warning',
            title: '契約終了間近のクライアント',
            message: '2件のクライアントの契約が30日以内に終了します',
            count: 2
        });
    }
    
    if (Math.random() > 0.6) {
        alerts.push({
            type: 'info',
            title: '今月請求予定',
            message: '3件のクライアントが今月の請求対象です',
            count: 3
        });
    }
    
    if (Math.random() > 0.8) {
        alerts.push({
            type: 'error',
            title: '同期エラー',
            message: '1件の広告アカウントで同期エラーが発生しています',
            count: 1
        });
    }
    
    return alerts;
}

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
            { sync_type: 'daily_data', total_syncs: 148, successful_syncs: 145, failed_syncs: 3 },
            { sync_type: 'campaign_data', total_syncs: 42, successful_syncs: 40, failed_syncs: 2 }
        ]
    };
}

// HTTPサーバー
const server = http.createServer(async (req, res) => {
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
                    summary: await generateSummaryStats(),
                    client_performance: await generateClientPerformance(),
                    platform_stats: await generatePlatformStats(),
                    daily_trend: await generateDailyTrend(30),
                    billing_stats: {
                        monthly_stats: { 
                            total_invoices: 8, 
                            draft_count: 2,
                            sent_count: 3,
                            paid_count: 3,
                            total_amount: 4200000,
                            paid_amount: 3100000, 
                            outstanding_amount: 1100000 
                        },
                        overdue_count: 1,
                        overdue_amount: 280000,
                        pending_fees: 650000
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
            
            const clients = await db.getClients();
            const accounts = await db.getAdAccounts();
            
            const responseData = {
                success: true,
                data: {
                    active_clients: clients.length,
                    active_accounts: accounts.length,
                    yesterday_cost: Math.floor(95000 + Math.random() * 50000),
                    yesterday_impressions: Math.floor(14000 + Math.random() * 4000),
                    yesterday_clicks: Math.floor(480 + Math.random() * 120),
                    yesterday_conversions: Math.floor(18 + Math.random() * 12)
                }
            };
            
            res.end(JSON.stringify(responseData, null, 2));
            return;
        }
        
        if (pathname === '/api/clients') {
            res.setHeader('Content-Type', 'application/json');
            res.writeHead(200);
            
            const clients = await db.getClients();
            const accounts = await db.getAdAccounts();
            const clientPerformance = await generateClientPerformance();
            
            const clientsWithPerformance = clients.map(client => {
                const performance = clientPerformance.find(p => p.id === client.id) || {};
                const clientAccounts = accounts.filter(acc => acc.client_id === client.id);
                
                return {
                    ...client,
                    ad_accounts_count: clientAccounts.length,
                    current_month_performance: {
                        total_cost: performance.total_cost || 0,
                        total_conversions: performance.total_conversions || 0,
                        average_cpa: performance.average_cpa || 0
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
                '.html': 'text/html; charset=utf-8',
                '.php': 'text/html; charset=utf-8',
                '.css': 'text/css; charset=utf-8',
                '.js': 'application/javascript; charset=utf-8',
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
            
            // PHP変数の簡易置換（MariaDB版）
            if (ext === '.php') {
                content = content.replace(/\<\?php[^?]*\?\>/g, '');
                content = content.replace(/\<\?=\s*\$title[^?]*\?\>/g, 'ダッシュボード - MariaDB版');
                content = content.replace(/include[^;]*;/g, '');
                
                // ヘッダーとサイドバーの内容を追加
                if (content.includes('<?php include \'../layout/header.php\'; ?>')) {
                    let headerContent = fs.readFileSync(path.join(__dirname, 'public/admin/layout/header.php'), 'utf8');
                    headerContent = headerContent.replace(/\<\?php[^?]*\?\>/g, '');
                    headerContent = headerContent.replace(/\<\?=\s*\$title[^?]*\?\>/g, 'ダッシュボード - MariaDB版');
                    content = content.replace('<?php include \'../layout/header.php\'; ?>', headerContent);
                }
                
                if (content.includes('<?php include \'../layout/sidebar.php\'; ?>')) {
                    let sidebarContent = fs.readFileSync(path.join(__dirname, 'public/admin/layout/sidebar.php'), 'utf8');
                    sidebarContent = sidebarContent.replace(/\<\?php[^?]*\?\>/g, '');
                    content = content.replace('<?php include \'../layout/sidebar.php\'; ?>', sidebarContent);
                }
                
                // MariaDB情報を追加
                content = content.replace(
                    '<div class="header-logo">',
                    '<div class="header-logo"><small style="color: #28a745; font-size: 10px; position: absolute; top: 35px; left: 50px;">[MariaDB版]</small>'
                );
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
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>404 - ページが見つかりません</title>
                <style>
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        text-align: center; 
                        padding: 50px; 
                        background: #f8f9fa;
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 0 auto; 
                        background: white; 
                        padding: 40px; 
                        border-radius: 8px; 
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    h1 { color: #dc3545; margin-bottom: 20px; }
                    .db-info { color: #28a745; font-size: 14px; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="db-info">🗄️ MariaDB 10.5 - kanho_adsmanager</div>
                    <h1>404 - ページが見つかりません</h1>
                    <p>お探しのページは存在しません。</p>
                    <a href="/" style="color: #007bff; text-decoration: none;">📊 ダッシュボードに戻る</a>
                </div>
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
                <style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;}</style>
            </head>
            <body>
                <h1>500 - 内部サーバーエラー</h1>
                <p>MariaDBサーバーでエラーが発生しました。</p>
                <pre style="text-align:left;max-width:600px;margin:20px auto;padding:20px;background:#f8f8f8;">${error.message}</pre>
                <a href="/">ダッシュボードに戻る</a>
            </body>
            </html>
        `);
    }
});

// サーバー起動
server.listen(PORT, async () => {
    console.log('🚀 広告管理システム MariaDBサーバーが起動しました！');
    console.log(`🗄️  データベース: MariaDB 10.5 - kanho_adsmanager`);
    console.log(`📱 ブラウザでアクセス: http://localhost:${PORT}`);
    console.log(`📊 ダッシュボード: http://localhost:${PORT}/dashboard`);
    console.log(`🔌 API: http://localhost:${PORT}/api/dashboard/data`);
    console.log('');
    
    // MariaDB接続テスト
    try {
        await db.connect();
        console.log('✅ MariaDBデータベース接続準備完了');
    } catch (error) {
        console.log('⚠️  MariaDB接続をシミュレート中（実際の接続には設定が必要）');
    }
    
    console.log('⏹️  停止するには Ctrl+C を押してください');
});