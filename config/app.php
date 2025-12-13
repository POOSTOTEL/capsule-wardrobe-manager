<?php
// config/app.php

return [
    'name' => 'Капсульный Гардероб',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => getenv('APP_DEBUG') ?: true,

    'timezone' => 'Europe/Moscow',
    'locale' => 'ru',

    'secret' => getenv('APP_SECRET') ?: 'your-secret-key-change-in-production',

    'uploads' => [
        'path' => '/var/www/public/uploads',
        'url' => '/uploads',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'max_size' => 5 * 1024 * 1024, // 5MB
        'thumb_width' => 300,
        'thumb_height' => 300,
    ],

    'session' => [
        'name' => 'capsule_session',
        'lifetime' => 120, // 2 часа
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
    ],
];