<?php


namespace App\Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    
    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    
    public function setFlash(string $type, string $message): void
    {
        if (!isset($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [];
        }
        $_SESSION['flash'][$type][] = $message;
    }

    
    public function getFlash(string $type): array
    {
        $messages = $_SESSION['flash'][$type] ?? [];
        unset($_SESSION['flash'][$type]);
        return $messages;
    }

    
    public function getAllFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    
    public function hasFlash(string $type): bool
    {
        return !empty($_SESSION['flash'][$type]);
    }

    
    public function clear(): void
    {
        $_SESSION = [];
    }

    
    public function destroy(): void
    {
        $this->clear();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    
    public function all(): array
    {
        return $_SESSION;
    }
}