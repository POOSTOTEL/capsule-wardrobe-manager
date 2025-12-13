<?php
// public/index.php

// Настройка времени выполнения и памяти
set_time_limit(30);
ini_set('memory_limit', '256M');

// Включение отладки (только для разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Загрузка конфигурации путей
require_once __DIR__ . '/../config/paths.php';

// Простая автозагрузка классов
spl_autoload_register(function ($className) {
    // Преобразуем пространство имен в путь к файлу
    $className = str_replace('App\\', 'app/', $className);
    $className = str_replace('\\', '/', $className);
    $file = ROOT_PATH . '/' . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    // Альтернативные пути для поиска
    $alternativePaths = [
        APP_PATH . '/',
        APP_PATH . '/Core/',
        APP_PATH . '/Controllers/',
        APP_PATH . '/Models/',
        APP_PATH . '/Middleware/',
    ];

    foreach ($alternativePaths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    return false;
});

// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загрузка конфигурации
$appConfig = require_once CONFIG_PATH . '/app.php';

// Установка временной зоны
if (isset($appConfig['timezone'])) {
    date_default_timezone_set($appConfig['timezone']);
}

// Загрузка маршрутов
$router = require_once CONFIG_PATH . '/routes.php';

// Запуск роутера
try {
    $router->dispatch();
} catch (Throwable $e) {
    // Обработка ошибок
    http_response_code(500);

    if ($appConfig['debug'] ?? false) {
        echo '<h1>500 - Internal Server Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>500 - Internal Server Error</h1>';
        echo '<p>An error occurred while processing your request.</p>';
    }

    error_log('Application error: ' . $e->getMessage());
}