<?php

namespace App\Models;

use Config\Database\Connection;
use PDO;
use PDOException;
use Exception;

abstract class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = array();
    protected $hidden = array();
    protected $casts = array();

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * 全レコードを取得
     */
    public function all($conditions = array(), $orderBy = array(), $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = array();

        if (!empty($conditions)) {
            $whereClause = $this->buildWhereClause($conditions, $params);
            $sql .= " WHERE {$whereClause}";
        }

        if (!empty($orderBy)) {
            $orderClause = $this->buildOrderClause($orderBy);
            $sql .= " ORDER BY {$orderClause}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->processResults($stmt->fetchAll());
    }

    /**
     * 条件に一致する全レコードを取得
     */
    public function getAll($conditions = array())
    {
        return $this->all($conditions);
    }

    /**
     * IDで単一レコードを取得
     */
    public function find($id)
    {
        if ($id === null) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($id));

        $result = $stmt->fetch();
        return $result ? $this->processResult($result) : null;
    }

    /**
     * 条件に一致する最初のレコードを取得
     */
    public function first($conditions = array())
    {
        $results = $this->all($conditions, array(), 1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * 新規レコードを作成
     */
    public function create($data)
    {
        $data = $this->filterFillable($data);
        $data = $this->castAttributes($data);

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->db->lastInsertId();
    }

    /**
     * レコードを更新
     */
    public function update($id, $data)
    {
        $data = $this->filterFillable($data);
        $data = $this->castAttributes($data);

        $setPairs = array();
        $params = array();

        foreach ($data as $column => $value) {
            $setPairs[] = "{$column} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setPairs) . " WHERE {$this->primaryKey} = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * レコードを削除
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array($id));
    }

    /**
     * レコード数を取得
     */
    public function count($conditions = array())
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = array();

        if (!empty($conditions)) {
            $whereClause = $this->buildWhereClause($conditions, $params);
            $sql .= " WHERE {$whereClause}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * ページネーション
     */
    public function paginate($page = 1, $perPage = 15, $conditions = array())
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        
        $sql = "SELECT * FROM {$this->table}";
        $params = array();

        if (!empty($conditions)) {
            $whereClause = $this->buildWhereClause($conditions, $params);
            $sql .= " WHERE {$whereClause}";
        }

        $sql .= " ORDER BY {$this->primaryKey} DESC LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array(
            'data' => $this->processResults($stmt->fetchAll()),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int)ceil($total / $perPage)
        );
    }

    /**
     * WHERE句を構築
     */
    protected function buildWhereClause($conditions, &$params)
    {
        $clauses = array();

        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                // IN句での検索
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $clauses[] = "{$key} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } elseif ($key === 'search') {
                // 検索キーワード
                $searchColumns = $this->getSearchableColumns();
                if (!empty($searchColumns)) {
                    $searchClauses = array();
                    foreach ($searchColumns as $column) {
                        $searchClauses[] = "{$column} LIKE ?";
                        $params[] = "%{$value}%";
                    }
                    $clauses[] = '(' . implode(' OR ', $searchClauses) . ')';
                }
            } else {
                $clauses[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        return implode(' AND ', $clauses);
    }

    /**
     * ORDER BY句を構築
     */
    protected function buildOrderClause($orderBy)
    {
        $clauses = array();

        foreach ($orderBy as $column => $direction) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $clauses[] = "{$column} {$direction}";
        }

        return implode(', ', $clauses);
    }

    /**
     * fillable属性でフィルタリング
     */
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * 属性をキャスト
     */
    protected function castAttributes($data)
    {
        foreach ($this->casts as $key => $type) {
            if (array_key_exists($key, $data)) {
                switch ($type) {
                    case 'int':
                    case 'integer':
                        $data[$key] = (int)$data[$key];
                        break;
                    case 'float':
                    case 'double':
                        $data[$key] = (float)$data[$key];
                        break;
                    case 'bool':
                    case 'boolean':
                        $data[$key] = (bool)$data[$key];
                        break;
                    case 'string':
                        $data[$key] = (string)$data[$key];
                        break;
                    case 'json':
                        $data[$key] = json_encode($data[$key]);
                        break;
                }
            }
        }

        return $data;
    }

    /**
     * 結果を処理（複数）
     */
    protected function processResults($results)
    {
        return array_map(array($this, 'processResult'), $results);
    }

    /**
     * 結果を処理（単一）
     */
    protected function processResult($result)
    {
        // hidden属性を除去
        foreach ($this->hidden as $key) {
            unset($result[$key]);
        }

        // キャスト処理
        foreach ($this->casts as $key => $type) {
            if (array_key_exists($key, $result)) {
                switch ($type) {
                    case 'int':
                    case 'integer':
                        $result[$key] = (int)$result[$key];
                        break;
                    case 'float':
                    case 'double':
                        $result[$key] = (float)$result[$key];
                        break;
                    case 'bool':
                    case 'boolean':
                        $result[$key] = (bool)$result[$key];
                        break;
                    case 'json':
                        $result[$key] = json_decode($result[$key], true);
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * 検索可能な列を取得
     */
    protected function getSearchableColumns()
    {
        // サブクラスでオーバーライドして検索対象列を定義
        return array();
    }

    /**
     * バリデーション
     */
    public function validate($data, $rules = array())
    {
        $errors = array();

        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? $data[$field] : null;

            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "{$field}は必須です";
                continue;
            }

            if (strpos($rule, 'email') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "{$field}は有効なメールアドレスではありません";
            }

            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = (int)$matches[1];
                if (!empty($value) && strlen($value) > $max) {
                    $errors[$field] = "{$field}は{$max}文字以内で入力してください";
                }
            }

            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                $min = (int)$matches[1];
                if (!empty($value) && strlen($value) < $min) {
                    $errors[$field] = "{$field}は{$min}文字以上で入力してください";
                }
            }
        }

        return $errors;
    }
}