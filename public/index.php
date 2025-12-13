<?php
// public/index.php - Фронт-контроллер приложения

// Включаем вывод ошибок для разработки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Начинаем сессию
session_start();

// Определяем базовые константы
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('VIEWS_PATH', PUBLIC_PATH . '/views');

// Автозагрузчик классов
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Проверка на диагностику
$showDiagnostic = false;

// Проверяем наличие .env файла
$envFile = APP_ROOT . '/.env';
if (!file_exists($envFile)) {
    $showDiagnostic = true;
}

// Если не установлено - показываем диагностику
if ($showDiagnostic) {
    require __DIR__ . '/setup.php';
    exit;
}

// Загружаем конфигурацию приложения
use App\Core\Config;

// Загружаем конфигурации
Config::load('app');
Config::load('database');
Config::load('paths');
Config::load('uploads');

// Устанавливаем часовой пояс
$timezone = Config::get('app.timezone', 'Europe/Moscow');
date_default_timezone_set($timezone);

try {
    // Получаем роутер
    $router = require APP_ROOT . '/config/routes.php';

    // Запускаем роутер
    $router->dispatch();

} catch (Exception $e) {
    // Обработка ошибок
    http_response_code(500);

    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ошибка приложения</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f8f9fa;
                color: #333;
                line-height: 1.6;
                padding: 20px;
            }
            .error-container {
                max-width: 800px;
                margin: 50px auto;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            h1 {
                color: #dc3545;
                border-bottom: 2px solid #dc3545;
                padding-bottom: 10px;
            }
            pre {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Внутренняя ошибка сервера</h1>
            <p>Произошла ошибка при запуске приложения.</p>';

    if (Config::get('app.debug', false)) {
        echo '<pre>';
        echo 'Error: ' . htmlspecialchars($e->getMessage()) . "\n";
        echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo 'Stack trace:' . "\n" . htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        echo '<p>Пожалуйста, попробуйте позже или обратитесь к администратору.</p>';
    }

    echo '</div></body></html>';

    // Логируем ошибку
    error_log('Application error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}