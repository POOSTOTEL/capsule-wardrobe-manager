<?php
// config/routes.php

use App\Core\Router;

$router = new Router();

// Главная страница
$router->get('/', 'HomeController@index', 'home');

// Авторизация
$router->get('/login', 'AuthController@showLogin', 'login');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister', 'register');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout', 'logout');

// Вещи гардероба
$router->get('/items', 'ItemController@index', 'items.index');
$router->get('/items/create', 'ItemController@create', 'items.create');
$router->post('/items', 'ItemController@store');
$router->get('/items/{id}', 'ItemController@show', 'items.show');
$router->get('/items/{id}/edit', 'ItemController@edit', 'items.edit');
$router->put('/items/{id}', 'ItemController@update');
$router->delete('/items/{id}', 'ItemController@destroy');

// Образы
$router->get('/outfits', 'OutfitController@index', 'outfits.index');
$router->get('/outfits/create', 'OutfitController@create', 'outfits.create');
$router->post('/outfits', 'OutfitController@store');
$router->get('/outfits/{id}', 'OutfitController@show', 'outfits.show');

// Капсулы
$router->get('/capsules', 'CapsuleController@index', 'capsules.index');
$router->get('/capsules/create', 'CapsuleController@create', 'capsules.create');
$router->post('/capsules', 'CapsuleController@store');

// Аналитика
$router->get('/analytics', 'AnalyticsController@index', 'analytics.index');

// API для AJAX
$router->get('/api/categories', 'ApiController@categories');
$router->get('/api/colors', 'ApiController@colors');
$router->get('/api/seasons', 'ApiController@seasons');

// Диагностика
$router->get('/diagnostic', function() {
    require __DIR__ . '/../public/setup.php';
});

// Обработка ошибок
$router->notFound(function() {
    http_response_code(404);
    echo '<h1>404 - Страница не найдена</h1>';
});

$router->error(function($exception) {
    http_response_code(500);
    echo '<h1>500 - Внутренняя ошибка сервера</h1>';
    if (Config::get('app.debug', false)) {
        echo '<pre>' . htmlspecialchars($exception->getMessage()) . '</pre>';
    }
});

return $router;