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
    |
    | Enable public registration
    |
    */
    'allow_registration' => env('SWIFT_AUTH_ALLOW_REGISTRATION', true),

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
    | Login Rate Limits
    |--------------------------------------------------------------------------
    |
    | Tune rate limits for login attempts by email and IP. Each limiter
    | contains the allowed attempts within a decay window (seconds).
    |
    */

    'login_rate_limits' => [
        'email' => [
            'attempts' => 5,
            'decay_seconds' => 300,
        ],
        'ip' => [
            'attempts' => 20,
            'decay_seconds' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Lifetimes
    |--------------------------------------------------------------------------
    |
    | Controls how long sessions remain valid. The idle timeout resets whenever
    | activity occurs, while the absolute timeout caps the total lifespan from
    | the moment of login. Set values to null to disable either limit.
    |
    */

    'session_lifetimes' => [
        'idle_timeout_seconds' => env('SWIFT_AUTH_SESSION_IDLE_TIMEOUT', 1800), // 30 minutes
        'absolute_timeout_seconds' => env('SWIFT_AUTH_SESSION_ABSOLUTE_TIMEOUT', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember Me Tokens
    |--------------------------------------------------------------------------
    |
    | Controls persistent login tokens stored as signed cookies. When enabled,
    | users can opt into a long-lived cookie that is rotated on use and
    | revocable server-side. Cookie attributes may be tuned for your deployment.
    |
    */

    'remember_me' => [
        'enabled' => env('SWIFT_AUTH_REMEMBER_ENABLED', true),
        'cookie_name' => env('SWIFT_AUTH_REMEMBER_COOKIE', 'swift_auth_remember'),
        'ttl_seconds' => env('SWIFT_AUTH_REMEMBER_TTL', 1209600), // 14 days
        'rotate_on_use' => env('SWIFT_AUTH_REMEMBER_ROTATE', true),
        'secure' => env('SWIFT_AUTH_REMEMBER_SECURE', true),
        'same_site' => env('SWIFT_AUTH_REMEMBER_SAMESITE', 'lax'),
        'domain' => env('SWIFT_AUTH_REMEMBER_DOMAIN', null),
        'path' => env('SWIFT_AUTH_REMEMBER_PATH', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Cleanup
    |--------------------------------------------------------------------------
    |
    | Automated cleanup of stale session rows in the persistence table. The
    | cutoff defaults to the longest configured session lifetime; adjust the
    | grace period to keep limited historical data if desired.
    |
    */

    'session_cleanup' => [
        'enabled' => env('SWIFT_AUTH_SESSION_CLEANUP_ENABLED', true),
        'grace_seconds' => env('SWIFT_AUTH_SESSION_CLEANUP_GRACE', 0),
        'schedule' => env('SWIFT_AUTH_SESSION_CLEANUP_CRON', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Concurrent Session Limits
    |--------------------------------------------------------------------------
    |
    | Caps the number of simultaneous sessions per user. When the limit is
    | exceeded, SwiftAuth will evict either the oldest or newest sessions based
    | on the eviction policy. Set `max_sessions` to null to disable.
    |
    */

    'session_limits' => [
        'max_sessions' => env('SWIFT_AUTH_MAX_SESSIONS', null),
        'eviction' => env('SWIFT_AUTH_SESSION_EVICTION', 'oldest'), // oldest | newest
    ],

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
    | Multi-factor Authentication (OTP/WebAuthn)
    |--------------------------------------------------------------------------
    |
    | Enable this to require a second factor after primary credential
    | verification. The driver flag allows clients to display the correct UI
    | (e.g., OTP code entry or WebAuthn prompt). Verification is expected to be
    | completed by the host application using the provided verification URL.
    |
    */

    'mfa' => [
        'enabled' => env('SWIFT_AUTH_MFA_ENABLED', false),
        'driver' => env('SWIFT_AUTH_MFA_DRIVER', 'otp'), // otp | webauthn
        'verification_url' => env('SWIFT_AUTH_MFA_VERIFICATION_URL', '/mfa/verify'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password minimum length and hashing
    |--------------------------------------------------------------------------
    |
    | `password_min_length` controls the minimum allowed password length for
    | lightweight validations (e.g. login). Stronger validation for
    | creation and reset flows is enforced by combining the required,
    | confirmed and min rules in controllers using this value.
    |
    | `hash_driver` (optional) can be set to a specific Laravel hash
    | driver name (e.g. `argon`, `bcrypt`). When null the application
    | default Hash driver is used.
    |
    */

    'password_min_length' => 8,
    'hash_driver' => null,

    /*
    |--------------------------------------------------------------------------
    | Password Complexity
    |--------------------------------------------------------------------------
    |
    | Optional complexity requirements layered on top of the minimum length.
    | Enable the booleans to enforce the related rule. The `common_passwords`
    | list is only used when `disallow_common_passwords` is true.
    |
    */

    'password_requirements' => [
        'require_letters' => false,
        'require_mixed_case' => false,
        'require_numbers' => false,
        'require_symbols' => false,
        'disallow_common_passwords' => false,
        'common_passwords' => [
            'password',
            'password1',
            '123456',
            '12345678',
            '123456789',
            'qwerty',
            'abc123',
            'iloveyou',
            'welcome',
            'admin',
            'letmein',
        ],
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    |
    | Enable email verification flow. When enabled, users must verify their
    | email address before accessing protected resources.
    |
    */
    'email_verification' => [
        'required' => env('SWIFT_AUTH_REQUIRE_VERIFICATION', false),
        'token_ttl' => 86400, // 24 hours
        'resend_rate_limit' => [
            'attempts' => 3,
            'decay_seconds' => 300, // 5 minutes
        ],
        'ip_rate_limit' => [
            'attempts' => 5,
            'decay_seconds' => 60, // 1 minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    |
    | Automatically lock accounts after repeated failed login attempts.
    |
    */
    'account_lockout' => [
        'enabled' => env('SWIFT_AUTH_LOCKOUT_ENABLED', true),
        'max_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'reset_after' => 3600, // Reset counter after 1 hour of no attempts
    ],

    /*
    |--------------------------------------------------------------------------
    | Bird Flock Messaging
    |--------------------------------------------------------------------------
    |
    | Integration with bird-flock package for email notifications.
    |
    */
    'bird_flock' => [
        'enabled' => env('SWIFT_AUTH_BIRD_FLOCK_ENABLED', true),
        'from_email' => env('SWIFT_AUTH_FROM_EMAIL', 'noreply@example.com'),
        'from_name' => env('SWIFT_AUTH_FROM_NAME', 'SwiftAuth'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure HTTP security headers applied by SecurityHeaders middleware.
    | CSP and Permissions-Policy are optional; leave null to skip.
    |
    */
    'security_headers' => [
        'csp' => env('SWIFT_AUTH_CSP', null), // e.g., "default-src 'self'; script-src 'self' 'unsafe-inline'"
        'permissions_policy' => env('SWIFT_AUTH_PERMISSIONS_POLICY', null), // e.g., "geolocation=(), microphone=()"
        'hsts' => [
            'enabled' => true,
            'max_age' => 31536000,
            'include_subdomains' => true,
            'preload' => false,
        ],
        'cross_origin_opener_policy' => env('SWIFT_AUTH_COOP', null), // e.g., "same-origin"
        'cross_origin_embedder_policy' => env('SWIFT_AUTH_COEP', null), // e.g., "require-corp"
        'cross_origin_resource_policy' => env('SWIFT_AUTH_CORP', null), // e.g., "same-origin"
        'referrer_policy' => env('SWIFT_AUTH_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],
];
