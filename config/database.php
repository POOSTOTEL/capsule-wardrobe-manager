<?php
// config/database.php

return [
    'driver' => 'pgsql',
    'host' => getenv('DB_HOST') ?: 'postgres',
    'port' => getenv('DB_PORT') ?: 5432,
    'database' => getenv('DB_NAME') ?: 'capsule_wardrobe',
    'username' => getenv('DB_USER') ?: 'capsule_user',
    'password' => getenv('DB_PASS') ?: 'capsule_password',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];