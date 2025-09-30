<?php 
$title = '新規クライアント登録';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-user-plus me-2"></i>
                新規クライアント登録
            </h1>
            <a href="/clients" class="btn btn-outline-secondary">
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
                <form method="POST" action="/clients/create" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <!-- 基本情報 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-building me-2"></i>
                                基本情報
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">
                                会社名 <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="company_name" 
                                name="company_name" 
                                value="<?= h($_SESSION['old_input']['company_name'] ?? '') ?>"
                                required
                                autofocus
                            >
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">担当者名</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="contact_person" 
                                name="contact_person" 
                                value="<?= h($_SESSION['old_input']['contact_person'] ?? '') ?>"
                            >
                        </div>
                    </div>
                    
                    <!-- 連絡先情報 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-address-book me-2"></i>
                                連絡先情報
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">メールアドレス</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                value="<?= h($_SESSION['old_input']['email'] ?? '') ?>"
                            >
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">電話番号</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="phone" 
                                name="phone" 
                                value="<?= h($_SESSION['old_input']['phone'] ?? '') ?>"
                            >
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">住所</label>
                            <textarea 
                                class="form-control" 
                                id="address" 
                                name="address" 
                                rows="3"
                            ><?= h($_SESSION['old_input']['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- 事業情報 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-briefcase me-2"></i>
                                事業情報
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tax_number" class="form-label">法人番号・税務番号</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="tax_number" 
                                name="tax_number" 
                                value="<?= h($_SESSION['old_input']['tax_number'] ?? '') ?>"
                            >
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">ステータス</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= ($_SESSION['old_input']['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                    アクティブ
                                </option>
                                <option value="inactive" <?= ($_SESSION['old_input']['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                    非アクティブ
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="tags" class="form-label">タグ</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="tags" 
                                name="tags" 
                                value="<?= h($_SESSION['old_input']['tags'] ?? '') ?>"
                                placeholder="例: EC, 飲食, 小売 (カンマ区切り)"
                            >
                            <div class="form-text">カンマ区切りで複数のタグを入力できます</div>
                        </div>
                    </div>
                    
                    <!-- メモ -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-sticky-note me-2"></i>
                                メモ
                            </h5>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="notes" class="form-label">備考・メモ</label>
                            <textarea 
                                class="form-control" 
                                id="notes" 
                                name="notes" 
                                rows="4"
                                placeholder="クライアントに関する特記事項やメモを入力..."
                            ><?= h($_SESSION['old_input']['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- 送信ボタン -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="/clients" class="btn btn-secondary">
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
    const companyName = document.getElementById('company_name');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // 会社名の必須チェック
        if (!companyName.value.trim()) {
            companyName.classList.add('is-invalid');
            isValid = false;
        } else {
            companyName.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // リアルタイムバリデーション
    companyName.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
unset($_SESSION['old_input']);
require_once __DIR__ . '/../layouts/app.php';
?>