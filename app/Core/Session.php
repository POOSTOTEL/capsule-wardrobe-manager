<?php
// app/Core/Session.php

namespace App\Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Установка значения
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    // Получение значения
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    // Удаление значения
    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // Проверка существования ключа
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    // Установка флеш-сообщения
    public function setFlash(string $type, string $message): void
    {
        if (!isset($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [];
        }
        $_SESSION['flash'][$type][] = $message;
    }

    // Получение флеш-сообщений
    public function getFlash(string $type): array
    {
        $messages = $_SESSION['flash'][$type] ?? [];
        unset($_SESSION['flash'][$type]);
        return $messages;
    }

    // Получение всех флеш-сообщений
    public function getAllFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    // Проверка наличия флеш-сообщений определенного типа
    public function hasFlash(string $type): bool
    {
        return !empty($_SESSION['flash'][$type]);
    }

    // Очистка всех данных сессии
    public function clear(): void
    {
        $_SESSION = [];
    }

    // Уничтожение сессии
    public function destroy(): void
    {
        $this->clear();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // Регенерация ID сессии
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    // Получение всех данных сессии
    public function all(): array
    {
        return $_SESSION;
    }
}