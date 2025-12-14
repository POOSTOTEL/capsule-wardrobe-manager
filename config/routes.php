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
// Маршруты для вещей (Items)
$router->get('/items', 'ItemController@index', 'items.index');
$router->get('/items/create', 'ItemController@create', 'items.create');
$router->post('/items', 'ItemController@store', 'items.store');
$router->get('/items/{id}', 'ItemController@show', 'items.show');
$router->get('/items/{id}/edit', 'ItemController@edit', 'items.edit');
$router->post('/items/{id}', 'ItemController@update', 'items.update');
$router->put('/items/{id}', 'ItemController@update');
$router->patch('/items/{id}', 'ItemController@update');
$router->post('/items/{id}/delete', 'ItemController@destroy', 'items.destroy');
$router->delete('/items/{id}', 'ItemController@destroy');
$router->delete('/api/items/{id}', 'ItemController@destroy');
$router->get('/api/items', 'ItemController@index');
$router->get('/api/items/{id}', 'ItemController@show');
$router->get('/api/items/{id}/image', 'ItemController@getImage', 'items.image');

// Маршруты для образов (Outfits)
$router->get('/outfits', 'OutfitController@index', 'outfits.index');
$router->get('/outfits/create', 'OutfitController@create', 'outfits.create');
$router->get('/outfits/builder', 'OutfitController@builder', 'outfits.builder');
$router->post('/outfits', 'OutfitController@store', 'outfits.store');
$router->get('/outfits/{id}', 'OutfitController@show', 'outfits.show');
$router->get('/outfits/{id}/edit', 'OutfitController@edit', 'outfits.edit');
$router->post('/outfits/{id}', 'OutfitController@update', 'outfits.update');
$router->put('/outfits/{id}', 'OutfitController@update');
$router->patch('/outfits/{id}', 'OutfitController@update');
$router->post('/outfits/{id}/delete', 'OutfitController@destroy', 'outfits.destroy');
$router->delete('/outfits/{id}', 'OutfitController@destroy');
$router->delete('/api/outfits/{id}', 'OutfitController@destroy');
$router->post('/outfits/{id}/favorite', 'OutfitController@toggleFavorite', 'outfits.toggleFavorite');
$router->post('/outfits/{id}/items', 'OutfitController@addItem', 'outfits.addItem');
$router->post('/outfits/{id}/items/remove', 'OutfitController@removeItem', 'outfits.removeItem');
$router->delete('/outfits/{id}/items/{itemId}', 'OutfitController@removeItem');
$router->get('/api/outfits', 'OutfitController@index');
$router->get('/api/outfits/{id}', 'OutfitController@show');

// Маршруты для капсул (Capsules)
$router->get('/capsules', 'CapsuleController@index', 'capsules.index');
$router->get('/capsules/create', 'CapsuleController@create', 'capsules.create');
$router->post('/capsules', 'CapsuleController@store', 'capsules.store');
$router->get('/capsules/{id}', 'CapsuleController@show', 'capsules.show');
$router->get('/capsules/{id}/edit', 'CapsuleController@edit', 'capsules.edit');
$router->post('/capsules/{id}', 'CapsuleController@update', 'capsules.update');
$router->put('/capsules/{id}', 'CapsuleController@update');
$router->patch('/capsules/{id}', 'CapsuleController@update');
$router->post('/capsules/{id}/delete', 'CapsuleController@destroy', 'capsules.destroy');
$router->delete('/capsules/{id}', 'CapsuleController@destroy');
$router->delete('/api/capsules/{id}', 'CapsuleController@destroy');
$router->get('/capsules/{id}/combinations', 'CapsuleController@combinations', 'capsules.combinations');
$router->post('/capsules/{id}/generate-outfits', 'CapsuleController@generateOutfits', 'capsules.generateOutfits');
$router->get('/api/capsules', 'CapsuleController@index');
$router->get('/api/capsules/{id}', 'CapsuleController@show');

// Маршруты для аналитики (Analytics)
$router->get('/analytics', 'AnalyticsController@index', 'analytics.index');
$router->get('/analytics/categories', 'AnalyticsController@categories', 'analytics.categories');
$router->get('/analytics/colors', 'AnalyticsController@colors', 'analytics.colors');
$router->get('/analytics/usage', 'AnalyticsController@usage', 'analytics.usage');
$router->get('/analytics/compatibility', 'AnalyticsController@compatibility', 'analytics.compatibility');
$router->get('/api/analytics', 'AnalyticsController@index');
$router->get('/api/analytics/categories', 'AnalyticsController@categories');
$router->get('/api/analytics/colors', 'AnalyticsController@colors');
$router->get('/api/analytics/usage', 'AnalyticsController@usage');
$router->get('/api/analytics/compatibility', 'AnalyticsController@compatibility');

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