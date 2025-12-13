<?php
// config/routes.php

// Загрузка Router без использования require в начале файла
use App\Core\Router;

$router = new Router();

// Главная страница
$router->get('/', 'HomeController@index', 'home');

// Маршруты аутентификации
$router->get('/login', 'AuthController@showLogin', 'login');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister', 'register');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout', 'logout');
$router->get('/profile', 'AuthController@showProfile', 'profile');
$router->post('/profile', 'AuthController@updateProfile');

// Диагностика
$router->get('/diagnostic', function() {
    // Используем константу напрямую
    $publicPath = dirname(__DIR__) . '/public';
    require $publicPath . '/setup.php';
});

// Обработка ошибок
$router->notFound(function() {
    http_response_code(404);

    // Используем относительные пути
    $viewsPath = dirname(__DIR__) . '/public/views';

    $data = [
        'title' => '404 - Страница не найдена',
        'content' => '<div class="text-center py-5">
            <h1 class="display-1">404</h1>
            <p class="lead">Страница не найдена</p>
            <a href="/" class="btn btn-primary">На главную</a>
        </div>'
    ];

    // Временный рендеринг для отладки
    extract($data);
    require $viewsPath . '/layouts/main.php';
});

$router->error(function($exception) {
    http_response_code(500);

    $data = [
        'title' => '500 - Внутренняя ошибка сервера',
        'content' => '<div class="text-center py-5">
            <h1 class="display-1">500</h1>
            <p class="lead">Внутренняя ошибка сервера</p>'
    ];

    // Получаем конфиг приложения для проверки debug режима
    $configPath = dirname(__DIR__) . '/config/app.php';
    $appConfig = file_exists($configPath) ? require $configPath : ['debug' => true];

    if ($appConfig['debug'] ?? false) {
        $data['content'] .= '<div class="mt-4"><pre>' . htmlspecialchars($exception->getMessage()) . '</pre></div>';
    }

    $data['content'] .= '<a href="/" class="btn btn-primary mt-3">На главную</a></div>';

    $viewsPath = dirname(__DIR__) . '/public/views';
    extract($data);
    require $viewsPath . '/layouts/main.php';
});

return $router;