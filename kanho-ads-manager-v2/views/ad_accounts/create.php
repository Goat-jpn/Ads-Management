<?php 
$title = '新規広告アカウント登録';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-plus me-2"></i>
                新規広告アカウント登録
            </h1>
            <a href="/ad-accounts" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                一覧に戻る
            </a>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body">
                <form method="POST" action="/ad-accounts/create" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <!-- 基本情報 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                基本情報
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">
                                クライアント <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">クライアントを選択してください</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>" <?= ($_SESSION['old_input']['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                    <?= h($client['company_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="platform" class="form-label">
                                プラットフォーム <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="platform" name="platform" required>
                                <option value="">プラットフォームを選択してください</option>
                                <option value="google" <?= ($_SESSION['old_input']['platform'] ?? '') === 'google' ? 'selected' : '' ?>>
                                    <i class="fab fa-google"></i> Google Ads
                                </option>
                                <option value="yahoo" <?= ($_SESSION['old_input']['platform'] ?? '') === 'yahoo' ? 'selected' : '' ?>>
                                    <i class="fab fa-yahoo"></i> Yahoo Ads
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Google Ads アカウント選択 -->
                    <div class="row mb-4" id="google-account-selector" style="display: none;">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="fab fa-google me-2"></i>
                                            Google Ads アカウント選択 <span class="text-danger">*</span>
                                        </h6>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="testGoogleConnection()">
                                                <i class="fas fa-plug me-1"></i>
                                                接続テスト
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadGoogleAccounts()" id="load-accounts-btn">
                                                <i class="fas fa-sync me-1"></i>
                                                アカウント読み込み
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="google-accounts-loading" style="display: none;">
                                        <div class="text-center py-3">
                                            <i class="fas fa-spinner fa-spin me-2"></i>
                                            Google Ads APIからアカウント情報を取得中...
                                        </div>
                                    </div>
                                    
                                    <div id="google-accounts-list" style="display: none;">
                                        <label class="form-label">利用可能なアカウント <span class="text-danger">*</span></label>
                                        <select class="form-select" id="google-account-select" name="google_account_select" required onchange="selectGoogleAccount()">
                                            <option value="">アカウントを選択してください</option>
                                        </select>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            アカウントを選択すると下記のフォームに自動入力されます
                                        </div>
                                    </div>
                                    
                                    <div id="google-accounts-error" style="display: none;" class="alert alert-danger mt-2">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- アカウント詳細 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-bullhorn me-2"></i>
                                アカウント詳細
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="account_id" class="form-label">
                                アカウントID <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="account_id" 
                                name="account_id" 
                                value="<?= h($_SESSION['old_input']['account_id'] ?? '') ?>"
                                placeholder="例: 123-456-7890"
                                required
                                autofocus
                            >
                            <div class="form-text">
                                プラットフォームから取得したアカウントIDを入力してください
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="account_name" class="form-label">
                                アカウント名 <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="account_name" 
                                name="account_name" 
                                value="<?= h($_SESSION['old_input']['account_name'] ?? '') ?>"
                                placeholder="例: 株式会社○○ - 検索広告"
                                required
                            >
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">通貨</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="JPY" <?= ($_SESSION['old_input']['currency'] ?? 'JPY') === 'JPY' ? 'selected' : '' ?>>
                                    JPY (日本円)
                                </option>
                                <option value="USD" <?= ($_SESSION['old_input']['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>
                                    USD (米ドル)
                                </option>
                                <option value="EUR" <?= ($_SESSION['old_input']['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>
                                    EUR (ユーロ)
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="timezone" class="form-label">タイムゾーン</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Asia/Tokyo" <?= ($_SESSION['old_input']['timezone'] ?? 'Asia/Tokyo') === 'Asia/Tokyo' ? 'selected' : '' ?>>
                                    Asia/Tokyo (JST)
                                </option>
                                <option value="America/New_York" <?= ($_SESSION['old_input']['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>
                                    America/New_York (EST)
                                </option>
                                <option value="Europe/London" <?= ($_SESSION['old_input']['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>
                                    Europe/London (GMT)
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- 設定 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-cogs me-2"></i>
                                設定
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">初期ステータス</label>
                            <select class="form-select" id="status" name="status">
                                <option value="inactive" <?= ($_SESSION['old_input']['status'] ?? 'inactive') === 'inactive' ? 'selected' : '' ?>>
                                    非アクティブ（API設定後に有効化）
                                </option>
                                <option value="active" <?= ($_SESSION['old_input']['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                                    アクティブ
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    id="sync_enabled" 
                                    name="sync_enabled"
                                    <?= isset($_SESSION['old_input']['sync_enabled']) ? 'checked' : 'checked' ?>
                                >
                                <label class="form-check-label" for="sync_enabled">
                                    <strong>自動同期を有効にする</strong>
                                </label>
                                <div class="form-text">
                                    定期的にプラットフォームからデータを自動取得します
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API認証情報の説明 -->
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle fa-lg"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="alert-heading">API認証について</h6>
                                <p class="mb-0">
                                    アカウント作成後、各プラットフォームのAPI認証設定を行ってください。<br>
                                    認証設定が完了するとデータの自動同期が可能になります。
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 送信ボタン -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="/ad-accounts" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    キャンセル
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    登録する
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// フォームバリデーション
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const clientId = document.getElementById('client_id');
    const platform = document.getElementById('platform');
    const accountId = document.getElementById('account_id');
    const accountName = document.getElementById('account_name');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // 必須フィールドのチェック
        [clientId, platform, accountId, accountName].forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Google Ads選択時の特別なバリデーション
        if (platform.value === 'google') {
            const googleSelect = document.getElementById('google-account-select');
            if (!googleSelect.value) {
                googleSelect.classList.add('is-invalid');
                showTemporaryMessage('danger', 'Google Adsアカウントを選択してください');
                isValid = false;
            } else {
                googleSelect.classList.remove('is-invalid');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // リアルタイムバリデーション
    [clientId, platform, accountId, accountName].forEach(field => {
        field.addEventListener('change', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // プラットフォーム選択時のヒント表示とGoogle Ads選択機能
    platform.addEventListener('change', function() {
        const accountIdInput = document.getElementById('account_id');
        const accountIdHelp = accountIdInput.nextElementSibling;
        const googleSelector = document.getElementById('google-account-selector');
        
        if (this.value === 'google') {
            accountIdInput.placeholder = '上記からアカウントを選択してください';
            accountIdInput.setAttribute('readonly', true);
            accountIdInput.classList.add('bg-light');
            accountIdHelp.innerHTML = '<strong class="text-primary"><i class="fas fa-arrow-up me-1"></i>上記のGoogle Ads アカウント選択からアカウントを選んでください</strong>';
            googleSelector.style.display = 'block';
            
            // Google Ads選択の必須化
            const googleSelect = document.getElementById('google-account-select');
            if (googleSelect) {
                googleSelect.setAttribute('required', true);
            }
            
            // 自動でアカウント読み込みを実行
            setTimeout(() => {
                const accountsList = document.getElementById('google-accounts-list');
                if (accountsList && accountsList.style.display === 'none') {
                    loadGoogleAccounts();
                }
            }, 500);
            
        } else if (this.value === 'yahoo') {
            accountIdInput.placeholder = '例: 1234567890';
            accountIdInput.removeAttribute('readonly');
            accountIdInput.classList.remove('bg-light');
            accountIdHelp.textContent = 'Yahoo広告のアカウントIDを入力してください';
            googleSelector.style.display = 'none';
            
            // Google Ads選択の必須を解除
            const googleSelect = document.getElementById('google-account-select');
            if (googleSelect) {
                googleSelect.removeAttribute('required');
            }
            
        } else {
            accountIdInput.placeholder = '例: 123-456-7890';
            accountIdInput.removeAttribute('readonly');
            accountIdInput.classList.remove('bg-light');
            accountIdHelp.textContent = 'プラットフォームから取得したアカウントIDを入力してください';
            googleSelector.style.display = 'none';
            
            // Google Ads選択の必須を解除
            const googleSelect = document.getElementById('google-account-select');
            if (googleSelect) {
                googleSelect.removeAttribute('required');
            }
        }
    });
});

// Google Ads API連携関数
let googleAccountsData = [];

// Google Adsアカウントを読み込む
async function loadGoogleAccounts() {
    const loadingDiv = document.getElementById('google-accounts-loading');
    const listDiv = document.getElementById('google-accounts-list');
    const errorDiv = document.getElementById('google-accounts-error');
    const loadButton = document.getElementById('load-accounts-btn');
    
    // UI状態をリセット
    loadingDiv.style.display = 'block';
    listDiv.style.display = 'none';
    errorDiv.style.display = 'none';
    if (loadButton) {
        loadButton.disabled = true;
        loadButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>読み込み中...';
    }
    
    try {
        const response = await fetch('/api/google-accounts');
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'APIエラーが発生しました');
        }
        
        if (data.success && data.accounts) {
            googleAccountsData = data.accounts;
            populateGoogleAccountsSelect(data.accounts);
            listDiv.style.display = 'block';
            
            // 成功メッセージを表示
            showTemporaryMessage('success', `${data.total}件のアカウントを読み込みました`);
        } else {
            throw new Error('アカウントデータの取得に失敗しました');
        }
        
    } catch (error) {
        errorDiv.innerHTML = `<strong>エラー:</strong> ${error.message}`;
        errorDiv.style.display = 'block';
        console.error('Google Ads API Error:', error);
    } finally {
        loadingDiv.style.display = 'none';
        if (loadButton) {
            loadButton.disabled = false;
            loadButton.innerHTML = '<i class="fas fa-sync me-1"></i>アカウント読み込み';
        }
    }
}

// セレクトボックスにアカウント一覧を追加
function populateGoogleAccountsSelect(accounts) {
    const select = document.getElementById('google-account-select');
    
    // 既存のオプションをクリア（最初のオプション以外）
    select.innerHTML = '<option value="">アカウントを選択してください</option>';
    
    accounts.forEach(account => {
        const option = document.createElement('option');
        option.value = account.id;
        option.textContent = `${account.name} (ID: ${account.id})`;
        option.dataset.currency = account.currency;
        option.dataset.timezone = account.timezone;
        option.dataset.status = account.status;
        select.appendChild(option);
    });
}

// Google Adsアカウントを選択
function selectGoogleAccount() {
    const select = document.getElementById('google-account-select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const accountId = selectedOption.value;
        const accountData = googleAccountsData.find(acc => acc.id === accountId);
        
        if (accountData) {
            // フォームに情報を自動入力
            document.getElementById('account_id').value = accountId;
            document.getElementById('account_name').value = accountData.name;
            document.getElementById('currency').value = accountData.currency;
            document.getElementById('timezone').value = accountData.timezone;
            
            // 成功メッセージを表示
            showTemporaryMessage('info', `アカウント "${accountData.name}" を選択しました`);
            
            // 入力フィールドのバリデーション状態をリセット
            document.getElementById('account_id').classList.remove('is-invalid');
            document.getElementById('account_name').classList.remove('is-invalid');
            select.classList.remove('is-invalid');
        }
    } else {
        // 選択解除時
        document.getElementById('account_id').value = '';
        document.getElementById('account_name').value = '';
    }
}

// 接続テスト機能
async function testGoogleConnection() {
    const testButton = event.target;
    const originalText = testButton.innerHTML;
    
    testButton.disabled = true;
    testButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>テスト中...';
    
    try {
        const response = await fetch('/api/google-test');
        const data = await response.json();
        
        if (data.success) {
            showTemporaryMessage('success', `${data.message} (${data.accounts_found}件のアカウントが見つかりました)`);
        } else {
            showTemporaryMessage('danger', `接続テスト失敗: ${data.message}`);
        }
        
    } catch (error) {
        showTemporaryMessage('danger', `接続テストエラー: ${error.message}`);
    } finally {
        testButton.disabled = false;
        testButton.innerHTML = originalText;
    }
}

// 一時的なメッセージを表示
function showTemporaryMessage(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show mt-2" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // メッセージ表示エリアを探す
    let container = document.querySelector('.col-lg-8 .card-body');
    if (!container) {
        container = document.querySelector('form');
    }
    
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // 5秒後に自動で消す
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert && typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
}
</script>

<?php 
$content = ob_get_clean();
unset($_SESSION['old_input']);
require_once __DIR__ . '/../layouts/app.php';
?>