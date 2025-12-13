<?php
// app/Middleware/AuthMiddleware.php

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    protected $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    // Основной метод обработки middleware
    public function handle(): bool
    {
        // Проверяем, авторизован ли пользователь
        if (!$this->session->get('user_id')) {
            return false;
        }

        // Можно добавить дополнительную проверку, например:
        // - Проверка активности пользователя в БД
        // - Проверка времени последней активности
        // - Проверка IP адреса

        return true;
    }

    // Проверка и редирект если не авторизован
    public function requireAuth(string $redirectTo = '/login'): void
    {
        if (!$this->handle()) {
            // Сохраняем URL для редиректа после входа
            if ($redirectTo === '/login') {
                $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
                header("Location: /login?redirect=" . urlencode($currentUrl));
            } else {
                header("Location: " . $redirectTo);
            }
            exit();
        }
    }

    // Проверка и редирект если уже авторизован (для страниц логина/регистрации)
    public function requireGuest(string $redirectTo = '/'): void
    {
        if ($this->handle()) {
            header("Location: " . $redirectTo);
            exit();
        }
    }

    // Получение ID текущего пользователя
    public function getUserId(): ?int
    {
        return $this->session->get('user_id');
    }

    // Получение данных текущего пользователя
    public function getUserData(): ?array
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return null;
        }

        // Здесь можно добавить получение данных пользователя из БД
        // или использовать данные из сессии
        return [
            'id' => $userId,
            'email' => $this->session->get('user_email'),
            'username' => $this->session->get('user_username'),
            'full_name' => $this->session->get('user_full_name')
        ];
    }

    // Проверка роли пользователя (базовая реализация)
    public function hasRole(string $role): bool
    {
        // В текущей реализации у всех пользователей одинаковая роль
        // Можно расширить в будущем
        return $this->handle(); // Если авторизован, значит имеет роль "user"
    }

    // Проверка доступа к ресурсу
    public function canAccessResource(int $resourceUserId): bool
    {
        $currentUserId = $this->getUserId();

        // Пользователь может обращаться только к своим ресурсам
        return $currentUserId && $currentUserId === $resourceUserId;
    }

    // Обновление времени последней активности
    public function updateLastActivity(): void
    {
        $this->session->set('last_activity', time());
    }

    // Проверка времени бездействия
    public function checkInactivity(int $timeout = 1800): bool // 30 минут по умолчанию
    {
        $lastActivity = $this->session->get('last_activity');

        if (!$lastActivity) {
            $this->updateLastActivity();
            return true;
        }

        $inactiveTime = time() - $lastActivity;

        if ($inactiveTime > $timeout) {
            // Время бездействия истекло
            $this->session->destroy();
            return false;
        }

        $this->updateLastActivity();
        return true;
    }
}