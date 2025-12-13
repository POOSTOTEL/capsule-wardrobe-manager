<?php
// app/Core/Database.php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            // Загружаем конфигурацию базы данных
            $config = require CONFIG_PATH . '/database.php';

            // Создаем DSN
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;',
                $config['host'],
                $config['port'],
                $config['database']
            );

            // Создаем соединение
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );

            // Устанавливаем атрибуты
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Устанавливаем кодировку
            $this->connection->exec("SET NAMES 'UTF8'");

        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    // Метод для получения экземпляра (Singleton)
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Получить соединение с базой данных
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // Подготовить запрос
    public function prepare(string $sql): \PDOStatement
    {
        return $this->connection->prepare($sql);
    }

    // Выполнить запрос
    public function query(string $sql, array $params = [])
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Начать транзакцию
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    // Зафиксировать транзакцию
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    // Откатить транзакцию
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    // Получить последний вставленный ID
    public function lastInsertId(?string $name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    // Закрыть соединение (в основном для тестирования)
    public function close(): void
    {
        $this->connection = null;
        self::$instance = null;
    }
}