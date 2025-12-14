<?php
// app/Models/BaseModel.php

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

    // Получить все записи
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

    // Найти запись по ID
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

    // Создать запись
    public function create(array $data): int
    {
        // Фильтруем данные по fillable
        $filteredData = array_intersect_key($data, array_flip($this->fillable));

        $columns = implode(', ', array_keys($filteredData));
        $placeholders = ':' . implode(', :', array_keys($filteredData));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($filteredData);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Обновить запись
    public function update(int $id, array $data): bool
    {
        // Фильтруем данные по fillable
        $filteredData = array_intersect_key($data, array_flip($this->fillable));

        $setClause = implode(', ', array_map(
            fn($key) => "{$key} = :{$key}",
            array_keys($filteredData)
        ));

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $filteredData['id'] = $id;

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($filteredData);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Удалить запись
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

    // Получить записи с условием WHERE
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

    // Получить первую запись с условием
    public function firstWhere(string $column, $value, string $operator = '='): ?array
    {
        $results = $this->where($column, $value, $operator);
        return $results[0] ?? null;
    }

    // Получить все записи с сортировкой
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

    // Получить записи в виде массива для select (id => name)
    public function getForSelect(string $valueField = 'name', string $keyField = 'id'): array
    {
        $items = $this->all();
        $result = [];

        foreach ($items as $item) {
            $result[$item[$keyField]] = $item[$valueField];
        }

        return $result;
    }

    // Получить количество записей
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