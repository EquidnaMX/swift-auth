# Breaking Changes & Migration Guide

## v3.0.0 - "Sovereign"

Version 3.0 introduces **major breaking changes** by removing the `laravel/sanctum` dependency and replacing it with a native `UserToken` system. This release also adds comprehensive localization support and improves security for admin user creation.

### 1. Removed Sanctum Dependency ⚠️ CRITICAL

SwiftAuth v3.0.0 **completely removes** `laravel/sanctum` and replaces it with a native API authentication system.

#### What Changed

-   `composer.json` no longer lists `laravel/sanctum` as a dependency
-   Sanctum migrations are no longer published during `swift-auth:install`
-   New `UserToken` model, service, and migration replace Sanctum's `personal_access_tokens`
-   New middleware: `SwiftAuth.AuthenticateWithToken` and `SwiftAuth.CheckTokenAbilities`

#### Why This Change

1. **Table Prefix Compatibility:** Sanctum doesn't respect SwiftAuth's configurable table prefix
2. **Pattern Consistency:** Native tokens follow SwiftAuth's SHA-256 hashing patterns
3. **Reduced Dependencies:** Eliminates external dependency for core functionality
4. **Full Control:** Complete ownership of API authentication logic

#### Migration Steps

**Step 1: Update Dependencies**

```bash
composer remove laravel/sanctum
composer require equidna/swift-auth:^3.0
```

**Step 2: Publish New Migrations**

```bash
php artisan vendor:publish --tag=swift-auth:migrations --force
php artisan migrate
```

**Step 3: Update Middleware**

```php
// Before (v2.x)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/posts', [PostController::class, 'index']);
});

// After (v3.0)
Route::middleware('SwiftAuth.AuthenticateWithToken')->group(function () {
    Route::get('/api/posts', [PostController::class, 'index']);
});
```

**Step 4: Update Token Creation**

```php
// Before (Sanctum)
$token = $user->createToken('api-token', ['posts:read'])->plainTextToken;

// After (SwiftAuth)
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;

$tokenService = app(UserTokenService::class);
$result = $tokenService->createToken(
    user: $user,
    name: 'api-token',
    abilities: ['posts:read'],
    expiresAt: now()->addDays(30),
);
$token = $result['token']; // Store securely!
```

**Step 5: Update Ability Checks**

```php
// Before
if ($request->user()->tokenCan('posts:create')) { ... }

// After - Option 1: Middleware
Route::middleware([
    'SwiftAuth.AuthenticateWithToken',
    'SwiftAuth.CheckTokenAbilities:posts:create',
])->post('/api/posts', [PostController::class, 'store']);

// After - Option 2: Manual
$token = $request->attributes->get('user_token');
if ($token && $token->can('posts:create')) { ... }
```

**Step 6: Update Tests**

```php
// Before
use Laravel\Sanctum\Sanctum;
Sanctum::actingAs($user, ['posts:read']);

// After
$tokenService = app(UserTokenService::class);
$result = $tokenService->createToken($user, 'test-token', ['posts:read']);
$this->withHeader('Authorization', 'Bearer ' . $result['token'])
    ->getJson('/api/posts');
```

**Step 7: Migrate Existing Tokens (Optional)**

If you have existing Sanctum tokens and want to preserve them:

```php
// Create migration to copy data
DB::table($prefix . 'UserTokens')->insert(
    DB::table('personal_access_tokens')
        ->select([
            'tokenable_id as id_user',
            'name',
            'token as hashed_token',
            'abilities',
            'last_used_at',
            'expires_at',
            'created_at',
            'updated_at',
        ])
        ->get()
        ->toArray()
);
```

### 2. Admin User Creation Security Enhancement

The `swift-auth:create-admin` command no longer accepts passwords as CLI arguments for security reasons.

#### What Changed

**Before (v2.x):**

```bash
# Insecure - password visible in shell history
php artisan swift-auth:create-admin "Admin" admin@example.com password123

# Or via environment variables
SWIFT_ADMIN_NAME="Admin" SWIFT_ADMIN_EMAIL="admin@example.com" php artisan swift-auth:create-admin
```

**After (v3.0):**

```bash
# Password MUST be entered interactively
php artisan swift-auth:create-admin "Admin" admin@example.com
# Command prompts: "Enter admin password (leave empty to generate random):"
```

#### Why This Change

1. **Security:** Passwords in CLI arguments are visible in shell history and process lists
2. **Best Practice:** Interactive password entry prevents accidental exposure
3. **Convenience:** Auto-generation option for secure random passwords

#### Migration Actions

-   Remove `SWIFT_ADMIN_NAME` and `SWIFT_ADMIN_EMAIL` from `.env` files
-   Update deployment scripts to use interactive prompts or expect auto-generated passwords
-   Document generated passwords securely when using auto-generation

### 3. Installation Command Changes

The `swift-auth:install` command now publishes translation files automatically.

#### What Changed

**Before (v2.x):**

-   Did not publish translation files
-   Published Sanctum migrations separately

**After (v3.0):**

-   Automatically publishes `swift-auth:lang` translations (10 files)
-   No longer publishes Sanctum migrations
-   Groups all SwiftAuth migrations before running `migrate`

#### Migration Actions

No action required unless you have custom translation files that might conflict.

### 4. Route File Consolidation

Email verification routes have been consolidated into the main route file.

#### What Changed

**Before (v2.x):**

-   Separate `routes/swift-auth-email-verification.php` file

**After (v3.0):**

-   All routes in `routes/swift-auth.php`
-   Email verification routes: `POST /email/send`, `GET /email/verify/{token}`

#### Migration Actions

None required if using default package routes. Check for conflicts if you've customized routes.

### 5. Complete API Migration Summary

| Feature        | v2.x (Sanctum)         | v3.0 (UserToken)                           |
| -------------- | ---------------------- | ------------------------------------------ |
| Dependency     | `laravel/sanctum`      | Native SwiftAuth                           |
| Middleware     | `auth:sanctum`         | `SwiftAuth.AuthenticateWithToken`          |
| Token Creation | `$user->createToken()` | `UserTokenService::createToken()`          |
| Ability Check  | `$user->tokenCan()`    | `SwiftAuth.CheckTokenAbilities` middleware |
| Revocation     | `$token->delete()`     | `UserTokenService::revokeToken()`          |
| Table Prefix   | Not supported          | Fully supported via config                 |
| Expiration     | `expires_at`           | `expires_at` + `isExpired()` method        |
| Hashing        | SHA-256                | SHA-256 (compatible)                       |

### 6. Documentation Resources

-   **Route Security:** `doc/securing-routes.md` - Comprehensive guide with examples
-   **Localization:** `doc/localization.md` - Translation system guide
-   **API Docs:** `doc/api-documentation.md` - Updated with UserToken endpoints
-   **README:** Updated with security quick reference

---

## v2.0.0 - "Obsidian"

Version 2.0 introduces strict architectural standards and a Domain-Driven Design (DDD) reorganization to improve maintainability and type safety.

### 1. Class Relocations (Domain Structure)

Files within `src/Classes/` have been organized into strict domains. If you were importing classes directly from `Equidna\SwiftAuth\Classes`, you may need to update your imports.

| Old Namespace / Path                                | New Namespace / Path                                             |
| :-------------------------------------------------- | :--------------------------------------------------------------- |
| `Equidna\SwiftAuth\Classes\NotificationService`     | `Equidna\SwiftAuth\Classes\Notifications\NotificationService`    |
| `Equidna\SwiftAuth\Classes\RememberMeService`       | `Equidna\SwiftAuth\Classes\Auth\Services\RememberMeService`      |
| `Equidna\SwiftAuth\Classes\RememberToken`           | `Equidna\SwiftAuth\Classes\Auth\DTO\RememberToken`               |
| `Equidna\SwiftAuth\Classes\NotificationResult`      | `Equidna\SwiftAuth\Classes\Notifications\DTO\NotificationResult` |
| `Equidna\SwiftAuth\Classes\Traits\ChecksRateLimits` | `Equidna\SwiftAuth\Classes\Auth\Traits\ChecksRateLimits`         |

#### Migration Action:

Search and replace namespace imports in your application code if you have extended or used these internal classes directly.

### 2. Strict Type Enforcement

We have enforced native PHP return types and parameter types across the codebase to reduce reliance on PHPDoc.

-   **Before:**

    ```php
    /**
     * @return string
     */
    public function getToken() { ... }
    ```

-   **After:**
    ```php
    public function getToken(): string { ... }
    ```

#### Migration Action:

If you have **extended** any SwiftAuth classes and overridden methods, you **must** update your method signatures to match the new strict types. Failure to do so will result in a fatal PHP error (`Declaration of Child::method() must be compatible with Parent::method()`).

### 3. Constructor Property Promotion

Many DTOs and Services now use Constructor Property Promotion.

-   **Impact:** If you were using reflection or relying on specific internal property existence before the constructor ran, behavior might slightly differ, though public API surfaces remain largely compatible.

### 4. Event Constructors

Auth events (`UserLoggedIn`, `SessionEvicted`, etc.) now enforce strict types in their constructors.

-   `userId` is strictly `int|string|null`.
-   `driverMetadata` is strictly `array`.

#### Migration Action:

Ensure any manual instantiation of these events passes the correct strictly typed arguments.
