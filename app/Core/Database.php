<?php
// app/Core/Database.php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    // Получение подключения
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }

        return self::$connection;
    }

    // Установка подключения
    private static function connect(): void
    {
        $config = Config::get('database', []);

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'] ?? 'pgsql',
            $config['host'] ?? 'postgres',
            $config['port'] ?? 5432,
            $config['database'] ?? 'capsule_wardrobe',
            $config['charset'] ?? 'utf8'
        );

        $options = array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], $config['options'] ?? []);

        try {
            self::$connection = new PDO(
                $dsn,
                $config['username'] ?? 'capsule_user',
                $config['password'] ?? 'capsule_password',
                $options
            );

            // Для PostgreSQL устанавливаем схему
            if ($config['driver'] === 'pgsql') {
                self::$connection->exec('SET search_path TO public');
            }

        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    // Выполнение запроса
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Получение одной записи
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        return self::query($sql, $params)->fetch() ?: null;
    }

    // Получение всех записей
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    // Вставка записи и возврат ID
    public static function insert(string $table, array $data): ?int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        self::query($sql, $data);

        return (int) self::getConnection()->lastInsertId();
    }

    // Обновление записи
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }

        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $stmt = self::query($sql, array_merge($data, $whereParams));
        return $stmt->rowCount();
    }

    // Удаление записи
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    // Начало транзакции
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    // Фиксация транзакции
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    // Откат транзакции
    public static function rollBack(): bool
    {
        return self::getConnection()->rollBack();
    }
}