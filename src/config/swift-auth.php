<?php

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
    | Admin User (Default Credentials)
    |--------------------------------------------------------------------------
    |
    | Define the default administrator credentials for initial seeding.
    | These values are typically used by the AdminSeeder to create
    | the first admin user during installation.
    */

    'admin_user' => [
        'email' => env('SWIFT_ADMIN_EMAIL'),
        'password' => env('SWIFT_ADMIN_PASSWORD'),
        'name' => env('SWIFT_ADMIN_NAME'),
    ],
];
