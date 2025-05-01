<?php

return [
    'frontend'    => env('SWIFT_AUTH_FRONTEND', 'typescript'),
    'success_url' => env('SWIFT_AUTH_SUCCESS_URL', '/'),
    'actions' => [
        'sw-admin' => 'Swift Auth admin', //!! DO NOT REMOVE THIS ACTION AS THIS IS USED IN CORE SW FUNCTIONS
    ]
];
