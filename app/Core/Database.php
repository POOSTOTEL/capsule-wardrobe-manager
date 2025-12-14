<?php


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
            
            $config = require CONFIG_PATH . '/database.php';

            
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;',
                $config['host'],
                $config['port'],
                $config['database']
            );

            
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );

            
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            
            $this->connection->exec("SET NAMES 'UTF8'");

        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    
    public function prepare(string $sql): \PDOStatement
    {
        return $this->connection->prepare($sql);
    }

    
    public function query(string $sql, array $params = [])
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    
    public function lastInsertId(?string $name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    
    public function close(): void
    {
        $this->connection = null;
        self::$instance = null;
    }
}