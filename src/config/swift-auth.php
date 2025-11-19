<?php

/**
 * Package configuration for SwiftAuth.
 *
 * PHP 8.1+
 *
 * @package Equidna\SwiftAuth\Config
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Frontend Stack
    |--------------------------------------------------------------------------
    |
    | This option controls which frontend stack is installed during setup.
    | Supported values: "typescript", "javascript", "blade"
    | The default is "typescript".
    |
    */

    'frontend' => env('SWIFT_AUTH_FRONTEND', 'typescript'),

    /*
    |--------------------------------------------------------------------------
    | Success Redirect URL
    |--------------------------------------------------------------------------
    |
    | This URL is used to redirect users after successful authentication.
    | You can override it using the SWIFT_AUTH_SUCCESS_URL environment variable.
    |
    */

    'success_url' => env('SWIFT_AUTH_SUCCESS_URL', '/'),

    /*
    |--------------------------------------------------------------------------
    | Password Reset Token TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live for password reset tokens (in seconds). Tokens older than
    | this value will be considered expired and rejected by the reset flow.
    | Default is 900 seconds (15 minutes).
    |
    */

    'password_reset_ttl' => env('SWIFT_AUTH_PASSWORD_RESET_TTL', 900),

    /*
    |--------------------------------------------------------------------------
    | Password Reset Rate Limit
    |--------------------------------------------------------------------------
    |
    | Limits password reset *request* attempts per target (hashed email).
    | `attempts` = number of allowed tries in the window, `decay_seconds`
    | = length of the window in seconds. Tune per-app; Redis is recommended
    | as the cache driver to enforce limits across instances.
    |
    */

    'password_reset_rate_limit' => [
        'attempts' => 5,
        'decay_seconds' => 60,
        // 'block_for_seconds' => 600, // optional: stronger block after limit reached
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Verification Rate Limit
    |--------------------------------------------------------------------------
    |
    | Limits password reset token *verification* attempts per email to
    | prevent brute-force attacks on reset tokens. This is separate from
    | the request rate limit above.
    |
    */

    'password_reset_verify_attempts' => 10,
    'password_reset_verify_decay_seconds' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Available Actions
    |--------------------------------------------------------------------------
    |
    | Define the actions (permissions) available in the system.
    | The 'sw-admin' action is used internally by SwiftAuth core functions.
    | Do not remove it unless you know what you're doing.
    |
    */

    'actions' => [
        'sw-admin' => 'Swift Auth admin', // !! DO NOT REMOVE THIS ACTION: used in core SwiftAuth functions
    ],
    /*
    |--------------------------------------------------------------------------
    | Table & Route Prefix
    |--------------------------------------------------------------------------
    |
    | You can add a prefix to all package tables and routes to avoid name
    | collisions with host applications. Leave empty for no prefix.
    |
    */
    'table_prefix' => env('SWIFT_AUTH_TABLE_PREFIX', 'swift-auth_'),
    'route_prefix' => env('SWIFT_AUTH_ROUTE_PREFIX', 'swift-auth'),
];
