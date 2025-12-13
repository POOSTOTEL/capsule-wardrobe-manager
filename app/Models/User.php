<?php
// app/Models/User.php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class User
{
    protected $db;
    protected $table = 'users';

    public function __construct()
    {
        // Получаем экземпляр Database через Singleton
        $database = Database::getInstance();
        $this->db = $database->getConnection();
    }

    // Регистрация нового пользователя
    public function register(array $data): ?int
    {
        try {
            // Проверяем, существует ли пользователь с таким email
            if ($this->findByEmail($data['email'])) {
                return null;
            }

            // Хэшируем пароль
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $sql = "INSERT INTO {$this->table} (email, username, password_hash, full_name, created_at) 
                    VALUES (:email, :username, :password_hash, :full_name, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':email' => $data['email'],
                ':username' => $data['username'] ?? $data['email'],
                ':password_hash' => $hashedPassword,
                ':full_name' => $data['full_name'] ?? ''
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('User registration error: ' . $e->getMessage());
            return null;
        }
    }

    // Поиск пользователя по email
    public function findByEmail(string $email): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log('Find user by email error: ' . $e->getMessage());
            return null;
        }
    }

    // Поиск пользователя по ID
    public function findById(int $id): ?array
    {
        try {
            $sql = "SELECT id, email, username, full_name, created_at, updated_at 
                    FROM {$this->table} 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log('Find user by ID error: ' . $e->getMessage());
            return null;
        }
    }

    // Проверка учетных данных
    public function verifyCredentials(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Убираем пароль из возвращаемых данных
        unset($user['password_hash']);
        return $user;
    }

    // Обновление профиля пользователя
    public function updateProfile(int $userId, array $data): bool
    {
        try {
            $fields = [];
            $params = [':id' => $userId];

            if (isset($data['username'])) {
                $fields[] = 'username = :username';
                $params[':username'] = $data['username'];
            }

            if (isset($data['full_name'])) {
                $fields[] = 'full_name = :full_name';
                $params[':full_name'] = $data['full_name'];
            }

            if (isset($data['email'])) {
                // Проверяем, не занят ли email другим пользователем
                $existing = $this->findByEmail($data['email']);
                if ($existing && $existing['id'] != $userId) {
                    return false;
                }
                $fields[] = 'email = :email';
                $params[':email'] = $data['email'];
            }

            if (isset($data['password'])) {
                $fields[] = 'password_hash = :password_hash';
                $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($fields)) {
                return false;
            }

            $fields[] = 'updated_at = NOW()';

            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Update profile error: ' . $e->getMessage());
            return false;
        }
    }

    // Проверка существования пользователя
    public function exists(int $userId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $userId]);

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Check user exists error: ' . $e->getMessage());
            return false;
        }
    }

    // Получение статистики пользователя
    public function getStats(int $userId): array
    {
        try {
            $stats = [
                'total_items' => 0,
                'total_outfits' => 0,
                'total_capsules' => 0
            ];

            // Количество вещей
            $sql = "SELECT COUNT(*) FROM items WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $stats['total_items'] = (int)$stmt->fetchColumn();

            // Количество образов
            $sql = "SELECT COUNT(*) FROM outfits WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $stats['total_outfits'] = (int)$stmt->fetchColumn();

            // Количество капсул
            $sql = "SELECT COUNT(*) FROM capsules WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $stats['total_capsules'] = (int)$stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            error_log('Get user stats error: ' . $e->getMessage());
            return [];
        }
    }

    // Проверка, может ли пользователь получить доступ к ресурсу
    public function canAccessResource(int $userId, int $resourceId, string $table): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$table} WHERE id = :resource_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':resource_id' => $resourceId,
                ':user_id' => $userId
            ]);

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Check resource access error: ' . $e->getMessage());
            return false;
        }
    }
}