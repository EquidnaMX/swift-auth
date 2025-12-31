# Securing Routes

This guide explains how to protect routes using SwiftAuth's session-based and API token authentication systems.

---

## Authentication Methods

SwiftAuth provides two authentication mechanisms:

| Method        | Use Case                            | Middleware                        |
| ------------- | ----------------------------------- | --------------------------------- |
| **Session**   | Web browsers, Blade/Inertia apps    | `SwiftAuth.RequireAuthentication` |
| **API Token** | Mobile apps, SPAs, third-party APIs | `SwiftAuth.AuthenticateWithToken` |

Both can be used simultaneously in the same application.

---

## Session Authentication (Web)

### Basic Protection

Require users to be logged in via session:

```php
use Illuminate\Support\Facades\Route;

Route::middleware('SwiftAuth.RequireAuthentication')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

### Role-Based Authorization

Check if user has specific role:

```php
Route::middleware([
    'SwiftAuth.RequireAuthentication',
    'SwiftAuth.CanPerformAction:admin-panel',
])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::post('/admin/roles', [AdminController::class, 'createRole']);
});
```

### Multiple Actions

Require multiple permissions:

```php
Route::middleware([
    'SwiftAuth.RequireAuthentication',
    'SwiftAuth.CanPerformAction:users-manage,reports-view',
])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});
```

### Controller-Level Protection

Apply middleware in controllers:

```php
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('SwiftAuth.RequireAuthentication');
        $this->middleware('SwiftAuth.CanPerformAction:dashboard-access')->only('index');
    }

    public function index()
    {
        return view('dashboard');
    }
}
```

---

## API Token Authentication

### Basic Token Protection

Require valid API token:

```php
Route::prefix('api')->middleware('SwiftAuth.AuthenticateWithToken')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/users/me', [UserController::class, 'profile']);
});
```

### Ability-Based Authorization

Require specific token abilities/scopes:

```php
Route::prefix('api')->middleware('SwiftAuth.AuthenticateWithToken')->group(function () {

    // Read-only access
    Route::get('/posts', [PostController::class, 'index'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:read');

    // Write access
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:create');

    // Multiple abilities required
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:delete,admin');
});
```

### Resource Controllers

Apply middleware to specific actions:

```php
class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('SwiftAuth.AuthenticateWithToken');
        $this->middleware('SwiftAuth.CheckTokenAbilities:posts:read')->only(['index', 'show']);
        $this->middleware('SwiftAuth.CheckTokenAbilities:posts:create')->only('store');
        $this->middleware('SwiftAuth.CheckTokenAbilities:posts:update')->only(['update', 'edit']);
        $this->middleware('SwiftAuth.CheckTokenAbilities:posts:delete')->only('destroy');
    }
}
```

---

## Hybrid Routes (Both Session & Token)

Support both authentication methods on the same routes:

```php
// Custom middleware to accept both
Route::middleware(function ($request, $next) {
    // Try session authentication first
    if ($request->user()) {
        return $next($request);
    }

    // Try token authentication
    $tokenMiddleware = app(\Equidna\SwiftAuth\Http\Middleware\AuthenticateWithToken::class);
    return $tokenMiddleware->handle($request, $next);
})->group(function () {
    Route::get('/api/posts', [PostController::class, 'index']);
});
```

Or create a dedicated middleware:

```php
// app/Http/Middleware/AuthenticateWithSessionOrToken.php
class AuthenticateWithSessionOrToken
{
    public function handle($request, Closure $next)
    {
        // Session authenticated
        if ($request->user()) {
            return $next($request);
        }

        // Try token authentication
        $tokenService = app(\Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService::class);
        $token = $this->extractToken($request);

        if ($token !== null) {
            $userToken = $tokenService->validateToken($token);
            if ($userToken !== null) {
                $request->setUserResolver(fn () => $userToken->user);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    private function extractToken($request): ?string
    {
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }
        return null;
    }
}
```

---

## Rate Limiting Integration

Combine authentication with rate limiting:

```php
Route::middleware([
    'SwiftAuth.RequireAuthentication',
    'throttle:60,1', // 60 requests per minute
])->group(function () {
    Route::get('/api/search', [SearchController::class, 'index']);
});

// Different limits for API tokens
Route::middleware([
    'SwiftAuth.AuthenticateWithToken',
    'throttle:api', // Uses api limiter from RouteServiceProvider
])->group(function () {
    Route::get('/api/data', [DataController::class, 'index']);
});
```

---

## Examples by Use Case

### Web Dashboard (Session Only)

```php
Route::prefix('dashboard')
    ->middleware(['SwiftAuth.RequireAuthentication', 'SwiftAuth.SecurityHeaders'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/settings', [SettingsController::class, 'show']);
    });
```

### Admin Panel (Session + Role)

```php
Route::prefix('admin')
    ->middleware([
        'SwiftAuth.RequireAuthentication',
        'SwiftAuth.CanPerformAction:admin-panel',
    ])
    ->group(function () {
        Route::resource('users', AdminUserController::class);
        Route::resource('roles', RoleController::class);
    });
```

### Mobile API (Token + Abilities)

```php
Route::prefix('api/v1')
    ->middleware('SwiftAuth.AuthenticateWithToken')
    ->group(function () {

        Route::get('/profile', [ProfileController::class, 'show']);

        Route::middleware('SwiftAuth.CheckTokenAbilities:posts:read')
            ->get('/posts', [PostController::class, 'index']);

        Route::middleware('SwiftAuth.CheckTokenAbilities:posts:create')
            ->post('/posts', [PostController::class, 'store']);
    });
```

### Public + Protected (Mixed)

```php
// Public routes
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);

// Protected routes (token required)
Route::middleware('SwiftAuth.AuthenticateWithToken')->group(function () {
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:create');

    Route::put('/posts/{id}', [PostController::class, 'update'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:update');
});
```

---

## Testing Protected Routes

### Session Authentication Tests

```php
use Equidna\SwiftAuth\Models\User;

public function test_dashboard_requires_authentication()
{
    $response = $this->get('/dashboard');
    $response->assertRedirect('/swift-auth/login');
}

public function test_authenticated_user_can_access_dashboard()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();
}
```

### Token Authentication Tests

```php
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;

public function test_api_requires_token()
{
    $response = $this->getJson('/api/posts');
    $response->assertUnauthorized();
}

public function test_valid_token_grants_access()
{
    $user = User::factory()->create();
    $tokenService = app(UserTokenService::class);
    $result = $tokenService->createToken($user, 'Test', ['posts:read']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $result['token'])
        ->getJson('/api/posts');

    $response->assertOk();
}

public function test_token_without_ability_is_denied()
{
    $user = User::factory()->create();
    $tokenService = app(UserTokenService::class);
    $result = $tokenService->createToken($user, 'Test', ['posts:read']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $result['token'])
        ->postJson('/api/posts', ['title' => 'New Post']);

    $response->assertForbidden();
}
```

---

## Security Best Practices

1. **Always use HTTPS** in production for both sessions and tokens
2. **Session routes:** Enable CSRF protection (Laravel default)
3. **API routes:** Exclude from CSRF but validate tokens rigorously
4. **Token storage:** Never log or expose plain tokens after creation
5. **Abilities:** Use granular scopes (`resource:action` format)
6. **Expiration:** Set reasonable token lifetimes based on use case
7. **Rate limiting:** Apply to prevent abuse
8. **Logging:** Monitor failed authentication attempts

---

## Common Patterns

### RESTful API with Scoped Access

```php
Route::prefix('api/v1')->middleware('SwiftAuth.AuthenticateWithToken')->group(function () {
    // Anyone authenticated can read
    Route::get('/posts', [PostController::class, 'index']);

    // Only tokens with posts:create
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:create');

    // Only tokens with posts:update
    Route::put('/posts/{id}', [PostController::class, 'update'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:update');

    // Only tokens with posts:delete OR admin
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])
        ->middleware('SwiftAuth.CheckTokenAbilities:posts:delete');
});
```

### Admin Panel with Multiple Permissions

```php
Route::prefix('admin')
    ->middleware([
        'SwiftAuth.RequireAuthentication',
        'SwiftAuth.CanPerformAction:admin-panel',
    ])
    ->group(function () {

        // All admins can view
        Route::get('/dashboard', [AdminController::class, 'index']);

        // Requires users-manage action
        Route::middleware('SwiftAuth.CanPerformAction:users-manage')
            ->resource('users', UserController::class);

        // Requires roles-manage action
        Route::middleware('SwiftAuth.CanPerformAction:roles-manage')
            ->resource('roles', RoleController::class);
    });
```

---

## Troubleshooting

**Session not persisting:**

-   Check `SESSION_DRIVER` in `.env`
-   Ensure cookies are enabled in browser
-   Verify `SESSION_DOMAIN` matches your domain

**Token authentication fails:**

-   Verify `Authorization: Bearer {token}` header format
-   Check token hasn't expired
-   Ensure token hasn't been revoked
-   Validate token abilities match route requirements

**401 Unauthorized:**

-   Session: User not logged in or session expired
-   Token: Missing, invalid, or expired token

**403 Forbidden:**

-   Session: User lacks required role/action
-   Token: Token lacks required ability/scope
