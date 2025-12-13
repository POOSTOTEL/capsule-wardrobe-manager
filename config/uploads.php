<?php
// config/uploads.php

return [
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'max_size' => 5 * 1024 * 1024, // 5MB
    'thumbnails' => [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 600, 'height' => 600],
    ],
    'paths' => [
        'originals' => 'originals',
        'thumbnails' => 'thumbs',
        'avatars' => 'avatars',
        'items' => 'items',
    ],
];