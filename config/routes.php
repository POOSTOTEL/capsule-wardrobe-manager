<?php
// config/routes.php

use App\Core\Router;
use App\Middleware\AuthMiddleware;

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
// Маршруты для таксономии (справочников)
$router->get('/api/taxonomies', 'TaxonomyController@index', 'taxonomies.index');
$router->get('/api/taxonomies/forms', 'TaxonomyController@forForms', 'taxonomies.forForms');
$router->get('/api/taxonomies/categories', 'TaxonomyController@categories', 'taxonomies.categories');
$router->get('/api/taxonomies/colors', 'TaxonomyController@colors', 'taxonomies.colors');
$router->get('/api/taxonomies/seasons', 'TaxonomyController@seasons', 'taxonomies.seasons');
// Маршруты для управления тегами
$router->get('/api/tags', 'TagController@index', 'tags.index');
$router->get('/api/tags/grouped', 'TagController@grouped', 'tags.grouped');
$router->get('/api/tags/search', 'TagController@search', 'tags.search');
$router->get('/api/tags/popular', 'TagController@popular', 'tags.popular');
$router->post('/api/tags', 'TagController@store', 'tags.store');
$router->put('/api/tags/{id}', 'TagController@update', 'tags.update');
$router->delete('/api/tags/{id}', 'TagController@destroy', 'tags.destroy');
$router->get('/api/tags/item/{id}', 'TagController@forItem', 'tags.forItem');
$router->post('/api/tags/item/{id}/attach', 'TagController@attachToItem', 'tags.attach');
$router->delete('/api/tags/item/{itemId}/{tagId}', 'TagController@detachFromItem', 'tags.detach');

// Страница управления тегами
$router->get('/tags', 'TagController@manage', 'tags.manage');

// Защищенные маршруты (требующие аутентификации)
// Добавим позже, когда будут контроллеры
// $router->get('/items', 'ItemController@index', 'items.index');
// $router->get('/items/create', 'ItemController@create', 'items.create');
// $router->post('/items', 'ItemController@store');
// $router->get('/items/{id}', 'ItemController@show', 'items.show');

// $router->get('/outfits', 'OutfitController@index', 'outfits.index');
// $router->get('/outfits/create', 'OutfitController@create', 'outfits.create');

// $router->get('/capsules', 'CapsuleController@index', 'capsules.index');
// $router->get('/capsules/create', 'CapsuleController@create', 'capsules.create');

// $router->get('/analytics', 'AnalyticsController@index', 'analytics.index');

// Диагностика
$router->get('/diagnostic', function() {
    require dirname(__DIR__) . '/public/setup.php';
}, 'diagnostic');

// Обработка ошибок
$router->notFound(function() {
    http_response_code(404);

    $data = [
        'title' => '404 - Страница не найдена',
        'content' => '<div class="text-center py-5">
            <h1 class="display-1">404</h1>
            <p class="lead">Страница не найдена</p>
            <a href="/" class="btn btn-primary">На главную</a>
        </div>'
    ];

    extract($data);
    require dirname(__DIR__) . '/public/views/layouts/main.php';
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
        $data['content'] .= '<div class="mt-2"><pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre></div>';
    }

    $data['content'] .= '<a href="/" class="btn btn-primary mt-3">На главную</a></div>';

    extract($data);
    require dirname(__DIR__) . '/public/views/layouts/main.php';
});

return $router;