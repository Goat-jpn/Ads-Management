<?php

namespace App\Controllers;

use App\Models\Client;

class ClientController
{
    private $clientModel;
    
    public function __construct()
    {
        $this->clientModel = new Client();
    }
    
    public function index()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        // 検索・フィルタリング
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        
        // クライアント一覧取得
        if (!empty($search)) {
            $clients = $this->clientModel->searchClients($search, $_SESSION['user_id']);
            $totalCount = count($clients);
        } elseif (!empty($status)) {
            $clients = $this->clientModel->getClientsByStatus($status, $_SESSION['user_id']);
            $totalCount = count($clients);
        } else {
            $result = $this->clientModel->paginate($page, $perPage, 'created_at', 'DESC');
            $clients = $result['data'];
            $totalCount = $result['pagination']['total'];
        }
        
        // ページネーション情報
        $totalPages = ceil($totalCount / $perPage);
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages
        ];
        
        require_once __DIR__ . '/../../views/clients/index.php';
    }
    
    public function show($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $client = $this->clientModel->find($id);
        
        if (!$client) {
            flash('error', 'クライアントが見つかりませんでした。');
            redirect('/clients');
            return;
        }
        
        // クライアント統計情報
        $stats = $this->clientModel->getClientStats($id);
        
        require_once __DIR__ . '/../../views/clients/show.php';
    }
    
    public function create()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        require_once __DIR__ . '/../../views/clients/create.php';
    }
    
    public function store()
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        // CSRF チェック - 現在は無効化（開発中）
        // if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        //     flash('error', 'セキュリティエラーが発生しました。');
        //     redirect('/clients/create');
        //     return;
        // }
        
        // バリデーション
        $errors = $this->validateClientData($_POST);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect('/clients/create');
            return;
        }
        
        // タグの処理（カンマ区切り文字列をJSONに変換）
        $tagsInput = trim($_POST['tags'] ?? '');
        $tags = [];
        if (!empty($tagsInput)) {
            $tags = array_map('trim', explode(',', $tagsInput));
            $tags = array_filter($tags); // 空の要素を除去
        }
        
        // クライアント作成
        $clientData = [
            'user_id' => $_SESSION['user_id'],
            'company_name' => trim($_POST['company_name']),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'tax_number' => trim($_POST['tax_number'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'tags' => !empty($tags) ? json_encode($tags) : null,
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        $clientId = $this->clientModel->create($clientData);
        
        if ($clientId) {
            flash('success', 'クライアントを登録しました。');
            redirect("/clients/{$clientId}");
        } else {
            flash('error', 'クライアントの登録に失敗しました。');
            $_SESSION['old_input'] = $_POST;
            redirect('/clients/create');
        }
    }
    
    public function edit($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $client = $this->clientModel->find($id);
        
        if (!$client) {
            flash('error', 'クライアントが見つかりませんでした。');
            redirect('/clients');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
        require_once __DIR__ . '/../../views/clients/edit.php';
    }
    
    public function update($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $client = $this->clientModel->find($id);
        
        if (!$client) {
            flash('error', 'クライアントが見つかりませんでした。');
            redirect('/clients');
            return;
        }
        
        // CSRF チェック
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'セキュリティエラーが発生しました。');
            redirect("/clients/{$id}/edit");
            return;
        }
        
        // バリデーション
        $errors = $this->validateClientData($_POST, $id);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect("/clients/{$id}/edit");
            return;
        }
        
        // クライアント更新
        $clientData = [
            'company_name' => trim($_POST['company_name']),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'tax_number' => trim($_POST['tax_number'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'tags' => trim($_POST['tags'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        if ($this->clientModel->update($id, $clientData)) {
            flash('success', 'クライアント情報を更新しました。');
            redirect("/clients/{$id}");
        } else {
            flash('error', 'クライアント情報の更新に失敗しました。');
            $_SESSION['old_input'] = $_POST;
            redirect("/clients/{$id}/edit");
        }
    }
    
    public function destroy($id)
    {
        if (!is_logged_in()) {
            redirect('/login');
            return;
        }
        
        $client = $this->clientModel->find($id);
        
        if (!$client) {
            flash('error', 'クライアントが見つかりませんでした。');
            redirect('/clients');
            return;
        }
        
        // CSRF チェック (DELETE リクエスト)
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'セキュリティエラーが発生しました。');
            redirect("/clients/{$id}");
            return;
        }
        
        if ($this->clientModel->delete($id)) {
            flash('success', 'クライアントを削除しました。');
        } else {
            flash('error', 'クライアントの削除に失敗しました。');
        }
        
        redirect('/clients');
    }
    
    private function validateClientData($data, $clientId = null)
    {
        $errors = [];
        
        // 必須項目チェック
        if (empty($data['company_name'])) {
            $errors[] = '会社名は必須です。';
        } elseif (strlen($data['company_name']) > 255) {
            $errors[] = '会社名は255文字以内で入力してください。';
        }
        
        // メールアドレスチェック
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = '正しいメールアドレスを入力してください。';
            }
        }
        
        // 電話番号チェック
        if (!empty($data['phone'])) {
            if (strlen($data['phone']) > 50) {
                $errors[] = '電話番号は50文字以内で入力してください。';
            }
        }
        
        return $errors;
    }
}