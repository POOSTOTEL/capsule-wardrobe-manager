<?php


namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

abstract class BaseModel
{
    protected $table;
    protected $primaryKey = 'id';
    protected $db;
    protected $fillable = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    
    public function all(array $columns = ['*']): array
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} ORDER BY id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function find(int $id, array $columns = ['*']): ?array
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function create(array $data): int
    {
        
        $filteredData = array_intersect_key($data, array_flip($this->fillable));

        if (empty($filteredData)) {
            throw new \RuntimeException("No fillable fields provided for insert");
        }

        
        foreach ($filteredData as $key => $value) {
            if (is_bool($value)) {
                $filteredData[$key] = $value;
            } elseif ($value === '' && in_array($key, ['is_favorite', 'is_active'])) {
                
                $filteredData[$key] = false;
            } elseif (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0', ''])) {
                
                $filteredData[$key] = in_array(strtolower($value), ['true', '1']);
            }
        }

        $columns = implode(', ', array_keys($filteredData));
        $placeholders = ':' . implode(', :', array_keys($filteredData));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders}) RETURNING {$this->primaryKey}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($filteredData);
            
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result[$this->primaryKey])) {
                return (int) $result[$this->primaryKey];
            }
            
            
            
            $sequenceName = $this->table . '_' . $this->primaryKey . '_seq';
            $lastId = $this->db->lastInsertId($sequenceName);
            return $lastId ? (int) $lastId : 0;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function update(int $id, array $data): bool
    {
        
        $filteredData = array_intersect_key($data, array_flip($this->fillable));

        if (empty($filteredData)) {
            throw new \RuntimeException("No fillable fields provided for update");
        }

        $setClause = implode(', ', array_map(
            fn($key) => "{$key} = :{$key}",
            array_keys($filteredData)
        ));

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $filteredData['id'] = $id;

        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($filteredData);
            
            
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function where(string $column, $value, string $operator = '='): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} :value";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['value' => $value]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function firstWhere(string $column, $value, string $operator = '='): ?array
    {
        $results = $this->where($column, $value, $operator);
        return $results[0] ?? null;
    }

    
    public function allOrdered(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        $items = $this->all();
        $result = [];

        foreach ($items as $item) {
            $result[$item[$keyField]] = $item[$valueField];
        }

        return $result;
    }

    
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }
}