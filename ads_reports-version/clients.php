<?php
/**
 * クライアント管理ページ - PHP版
 */

// セッション開始
session_start();

// オートロード
require_once 'bootstrap.php';

// データベース接続確認
try {
    $connection = Connection::getInstance();
    $dbConnected = true;
} catch (Exception $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

$pageTitle = 'クライアント管理 - 広告費・手数料管理システム';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .client-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .client-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .performance-metric {
            font-size: 1.2rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .btn-group-actions {
            white-space: nowrap;
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
                        <a class="nav-link" href="/ads_reports/">
                            <i class="fas fa-home me-1"></i>ダッシュボード
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/ads_reports/clients">
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

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>
                        <i class="fas fa-users me-2"></i>
                        クライアント管理
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientModal" onclick="openClientModal()">
                        <i class="fas fa-plus me-2"></i>
                        新規クライアント登録
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="searchInput" placeholder="会社名、担当者名、メールアドレスで検索...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">すべてのステータス</option>
                    <option value="1">アクティブ</option>
                    <option value="0">非アクティブ</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-secondary" onclick="refreshClientList()">
                    <i class="fas fa-sync-alt me-1"></i>更新
                </button>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="row">
            <div class="col-12">
                <div class="card client-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>
                            クライアント一覧
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="clientsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>会社名</th>
                                        <th>担当者</th>
                                        <th>連絡先</th>
                                        <th>契約期間</th>
                                        <th>請求日</th>
                                        <th>今月の広告費</th>
                                        <th>ステータス</th>
                                        <th class="text-center">操作</th>
                                    </tr>
                                </thead>
                                <tbody id="clientsTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                                                <div class="spinner-border text-primary me-2" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                データを読み込み中...
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Modal -->
    <div class="modal fade" id="clientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientModalTitle">
                        <i class="fas fa-user-plus me-2"></i>
                        新規クライアント登録
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="clientForm">
                        <input type="hidden" id="clientId" name="id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="companyName" class="form-label">会社名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="companyName" name="company_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contactName" class="form-label">担当者名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contactName" name="contact_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">電話番号</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">住所</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contractStartDate" class="form-label">契約開始日</label>
                                <input type="date" class="form-control" id="contractStartDate" name="contract_start_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contractEndDate" class="form-label">契約終了日</label>
                                <input type="date" class="form-control" id="contractEndDate" name="contract_end_date">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="billingDay" class="form-label">請求日</label>
                                <select class="form-select" id="billingDay" name="billing_day">
                                    <?php for ($i = 1; $i <= 31; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == 25 ? 'selected' : ''; ?>><?php echo $i; ?>日</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="paymentTerms" class="form-label">支払条件（日数）</label>
                                <input type="number" class="form-control" id="paymentTerms" name="payment_terms" min="0" max="365" value="30">
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="isActive" name="is_active" checked>
                            <label class="form-check-label" for="isActive">アクティブ</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="button" class="btn btn-primary" onclick="saveClient()">
                        <i class="fas fa-save me-1"></i>保存
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let clientsTable;
        let editingClientId = null;

        // ページ読み込み時の初期化
        document.addEventListener('DOMContentLoaded', function() {
            loadClientsList();
        });

        // クライアント一覧を読み込み
        function loadClientsList() {
            fetch('./api/clients/')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderClientsTable(data.data);
                    } else {
                        console.error('Error loading clients:', data.error);
                        showAlert('クライアントデータの読み込みに失敗しました', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('ネットワークエラーが発生しました', 'danger');
                });
        }

        // テーブルレンダリング
        function renderClientsTable(clients) {
            const tbody = document.getElementById('clientsTableBody');
            tbody.innerHTML = '';

            clients.forEach(client => {
                const row = `
                    <tr>
                        <td><strong>${escapeHtml(client.company_name)}</strong></td>
                        <td>${escapeHtml(client.contact_name)}</td>
                        <td>
                            <div>${escapeHtml(client.email)}</div>
                            ${client.phone ? `<small class="text-muted">${escapeHtml(client.phone)}</small>` : ''}
                        </td>
                        <td>
                            <div>${client.contract_start_date || '-'}</div>
                            <small class="text-muted">${client.contract_end_date || '-'}</small>
                        </td>
                        <td>${client.billing_day}日</td>
                        <td class="performance-metric">¥${formatNumber(client.performance?.total_cost || 0)}</td>
                        <td>
                            <span class="badge ${client.is_active ? 'bg-success' : 'bg-secondary'} status-badge">
                                ${client.is_active ? 'アクティブ' : '非アクティブ'}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm btn-group-actions" role="group">
                                <button class="btn btn-outline-primary" onclick="editClient(${client.id})" title="編集">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="viewClientDetails(${client.id})" title="詳細">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteClient(${client.id})" title="削除">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // 新規クライアントモーダルを開く
        function openClientModal(clientId = null) {
            editingClientId = clientId;
            const modal = document.getElementById('clientModal');
            const form = document.getElementById('clientForm');
            
            form.reset();
            
            if (clientId) {
                document.getElementById('clientModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>クライアント編集';
                // クライアントデータを取得して設定
                loadClientForEdit(clientId);
            } else {
                document.getElementById('clientModalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>新規クライアント登録';
            }
        }

        // 編集用クライアントデータ読み込み
        function loadClientForEdit(clientId) {
            fetch(`./api/clients/?id=${clientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const client = data.data;
                        document.getElementById('clientId').value = client.id;
                        document.getElementById('companyName').value = client.company_name;
                        document.getElementById('contactName').value = client.contact_name;
                        document.getElementById('email').value = client.email;
                        document.getElementById('phone').value = client.phone || '';
                        document.getElementById('address').value = client.address || '';
                        document.getElementById('contractStartDate').value = client.contract_start_date || '';
                        document.getElementById('contractEndDate').value = client.contract_end_date || '';
                        document.getElementById('billingDay').value = client.billing_day || 25;
                        document.getElementById('paymentTerms').value = client.payment_terms || 30;
                        document.getElementById('isActive').checked = client.is_active == 1;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('クライアントデータの取得に失敗しました', 'danger');
                });
        }

        // クライアント保存
        function saveClient() {
            const form = document.getElementById('clientForm');
            const formData = new FormData(form);
            
            const clientData = {
                company_name: formData.get('company_name'),
                contact_name: formData.get('contact_name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                address: formData.get('address'),
                contract_start_date: formData.get('contract_start_date'),
                contract_end_date: formData.get('contract_end_date'),
                billing_day: parseInt(formData.get('billing_day')),
                payment_terms: parseInt(formData.get('payment_terms')),
                is_active: document.getElementById('isActive').checked ? 1 : 0
            };

            const method = editingClientId ? 'PUT' : 'POST';
            const url = editingClientId ? `./api/clients/?id=${editingClientId}` : './api/clients/';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(clientData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('clientModal')).hide();
                    loadClientsList();
                } else {
                    showAlert(data.error.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('保存に失敗しました', 'danger');
            });
        }

        // クライアント編集
        function editClient(clientId) {
            openClientModal(clientId);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('clientModal')).show();
        }

        // クライアント詳細表示
        function viewClientDetails(clientId) {
            // 詳細モーダルの実装
            alert('詳細表示機能は実装予定です');
        }

        // クライアント削除
        function deleteClient(clientId) {
            if (confirm('このクライアントを削除してもよろしいですか？')) {
                fetch(`./api/clients/?id=${clientId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadClientsList();
                    } else {
                        showAlert(data.error.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('削除に失敗しました', 'danger');
                });
            }
        }

        // リスト更新
        function refreshClientList() {
            loadClientsList();
        }

        // ユーティリティ関数
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('ja-JP').format(num);
        }

        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertAdjacentHTML('afterbegin', alertHtml);
        }
    </script>
</body>
</html>