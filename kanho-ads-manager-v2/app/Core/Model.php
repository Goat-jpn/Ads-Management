<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected $db;
    protected $table;
    protected $fillable = [];
    protected $hidden = [];
    
    public function __construct()
    {
        $this->db = \Database::getInstance();
    }
    
    public function all($orderBy = 'id', $order = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$order}";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $this->hideColumns([$result])[0] : null;
    }
    
    public function create($data)
    {
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            return false;
        }
        
        $columns = array_keys($filteredData);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        
        if ($stmt->execute(array_values($filteredData))) {
            return $this->db->getConnection()->lastInsertId();
        }
        
        return false;
    }
    
    public function update($id, $data)
    {
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            return false;
        }
        
        $columns = array_keys($filteredData);
        $setClause = implode(' = ?, ', $columns) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause}, updated_at = NOW() WHERE id = ?";
        
        $values = array_values($filteredData);
        $values[] = $id;
        
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute($values);
    }
    
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$id]);
    }
    
    public function paginate($page = 1, $perPage = 15, $orderBy = 'id', $order = 'DESC')
    {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table}";
        $countStmt = $this->db->getConnection()->prepare($countSql);
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY {$orderBy} {$order} 
                LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        $data = $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    public function where($column, $operator, $value = null, $orderBy = 'id', $order = 'ASC')
    {
        // If only 2 arguments provided, assume equals operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} ? ORDER BY {$orderBy} {$order}";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$value]);
        
        return $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function whereIn($column, $values, $orderBy = 'id', $order = 'ASC')
    {
        if (empty($values)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $sql = "SELECT * FROM {$this->table} WHERE {$column} IN ({$placeholders}) ORDER BY {$orderBy} {$order}";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($values);
        
        return $this->hideColumns($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function exists($id)
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch() !== false;
    }
    
    public function count($column = '*', $where = null)
    {
        $sql = "SELECT COUNT({$column}) as count FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    protected function hideColumns($data)
    {
        if (empty($this->hidden) || empty($data)) {
            return $data;
        }
        
        foreach ($data as &$row) {
            foreach ($this->hidden as $column) {
                unset($row[$column]);
            }
        }
        
        return $data;
    }
    
    public function beginTransaction()
    {
        return $this->db->getConnection()->beginTransaction();
    }
    
    public function commit()
    {
        return $this->db->getConnection()->commit();
    }
    
    public function rollback()
    {
        return $this->db->getConnection()->rollback();
    }
    
    public function query($sql, $params = [])
    {
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function execute($sql, $params = [])
    {
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute($params);
    }
}