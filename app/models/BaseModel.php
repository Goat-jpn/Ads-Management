<?php

namespace App\Models;

use Config\Database\Connection;
use PDO;
use PDOException;
use Exception;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * 全レコードを取得
     */
    public function all(array $conditions = [], array $orderBy = [], int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

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
     * IDで単一レコードを取得
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $result = $stmt->fetch();
        return $result ? $this->processResult($result) : null;
    }

    /**
     * 条件で単一レコードを取得
     */
    public function findBy(array $conditions): ?array
    {
        $params = [];
        $whereClause = $this->buildWhereClause($conditions, $params);
        
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();
        return $result ? $this->processResult($result) : null;
    }

    /**
     * 条件で複数レコードを取得
     */
    public function findAllBy(array $conditions, array $orderBy = [], int $limit = null): array
    {
        return $this->all($conditions, $orderBy, $limit);
    }

    /**
     * 新しいレコードを作成
     */
    public function create(array $data): array
    {
        $data = $this->filterFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        $id = $this->db->lastInsertId();
        return $this->find($id);
    }

    /**
     * レコードを更新
     */
    public function update(int $id, array $data): ?array
    {
        $data = $this->filterFillable($data);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $setPairs = [];
        foreach ($data as $column => $value) {
            $setPairs[] = "{$column} = ?";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setPairs) . " 
                WHERE {$this->primaryKey} = ?";

        $params = array_values($data);
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->find($id);
    }

    /**
     * レコードを削除
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 論理削除（is_activeカラムがある場合）
     */
    public function softDelete(int $id): ?array
    {
        return $this->update($id, ['is_active' => false]);
    }

    /**
     * レコード数を取得
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = $this->buildWhereClause($conditions, $params);
            $sql .= " WHERE {$whereClause}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * 存在チェック
     */
    public function exists(array $conditions): bool
    {
        return $this->count($conditions) > 0;
    }

    /**
     * ページネーション
     */
    public function paginate(int $page = 1, int $perPage = 20, array $conditions = [], array $orderBy = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table}";
        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = $this->buildWhereClause($conditions, $params);
            $sql .= " WHERE {$whereClause}";
            $countSql .= " WHERE {$whereClause}";
        }

        if (!empty($orderBy)) {
            $orderClause = $this->buildOrderClause($orderBy);
            $sql .= " ORDER BY {$orderClause}";
        }

        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        // データ取得
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $this->processResults($stmt->fetchAll());

        // 総件数取得
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => ($page * $perPage) < $total,
                'has_prev' => $page > 1
            ]
        ];
    }

    /**
     * WHERE句を構築
     */
    protected function buildWhereClause(array $conditions, array &$params): string
    {
        $whereConditions = [];

        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                // IN句の処理
                $placeholders = array_fill(0, count($value), '?');
                $whereConditions[] = "{$key} IN (" . implode(', ', $placeholders) . ")";
                $params = array_merge($params, $value);
            } elseif (is_null($value)) {
                $whereConditions[] = "{$key} IS NULL";
            } else {
                $whereConditions[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        return implode(' AND ', $whereConditions);
    }

    /**
     * ORDER BY句を構築
     */
    protected function buildOrderClause(array $orderBy): string
    {
        $orderConditions = [];

        foreach ($orderBy as $column => $direction) {
            if (is_numeric($column)) {
                // 単純なカラム名の場合
                $orderConditions[] = "{$direction} ASC";
            } else {
                $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $orderConditions[] = "{$column} {$direction}";
            }
        }

        return implode(', ', $orderConditions);
    }

    /**
     * fillableプロパティに基づいてデータをフィルタリング
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * 結果を処理（複数行）
     */
    protected function processResults(array $results): array
    {
        return array_map([$this, 'processResult'], $results);
    }

    /**
     * 結果を処理（単一行）
     */
    protected function processResult(array $result): array
    {
        // hiddenプロパティの除去
        if (!empty($this->hidden)) {
            foreach ($this->hidden as $hiddenField) {
                unset($result[$hiddenField]);
            }
        }

        // 型キャスト
        if (!empty($this->casts)) {
            foreach ($this->casts as $field => $type) {
                if (isset($result[$field])) {
                    $result[$field] = $this->castValue($result[$field], $type);
                }
            }
        }

        return $result;
    }

    /**
     * 値の型キャスト
     */
    protected function castValue($value, string $type)
    {
        return match ($type) {
            'int', 'integer' => (int)$value,
            'float', 'double' => (float)$value,
            'bool', 'boolean' => (bool)$value,
            'json' => json_decode($value, true),
            'array' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => new \DateTime($value),
            default => $value
        };
    }

    /**
     * トランザクション実行
     */
    public function transaction(callable $callback)
    {
        return Connection::transaction($callback);
    }

    /**
     * 生のSQLクエリを実行
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * テーブル名を取得
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * 主キー名を取得
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }
}