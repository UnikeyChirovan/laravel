<?php

return [
    'paths' => ['api/*','upload'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
    'http://127.0.0.1:8080',
    'http://localhost:8080',  
    'http://selorson.com',   
],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Authorization', 'Set-Cookie'],
    'max_age' => 0,
    'supports_credentials' => true,
];

