<?php
// config/paths.php

// Определяем корневую директорию
$rootPath = dirname(__DIR__);

define('ROOT_PATH', $rootPath);
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEWS_PATH', ROOT_PATH . '/public/views');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('LOG_PATH', ROOT_PATH . '/logs');