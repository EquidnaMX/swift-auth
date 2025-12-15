# Breaking Changes & Migration Guide

## v2.0.0 - "Obsidian"

Version 2.0 introduces strict architectural standards and a Domain-Driven Design (DDD) reorganization to improve maintainability and type safety.

### 1. Class Relocations (Domain Structure)

Files within `src/Classes/` have been organized into strict domains. If you were importing classes directly from `Equidna\SwiftAuth\Classes`, you may need to update your imports.

| Old Namespace / Path | New Namespace / Path |
| :--- | :--- |
| `Equidna\SwiftAuth\Classes\NotificationService` | `Equidna\SwiftAuth\Classes\Notifications\NotificationService` |
| `Equidna\SwiftAuth\Classes\RememberMeService` | `Equidna\SwiftAuth\Classes\Auth\Services\RememberMeService` |
| `Equidna\SwiftAuth\Classes\RememberToken` | `Equidna\SwiftAuth\Classes\Auth\DTO\RememberToken` |
| `Equidna\SwiftAuth\Classes\NotificationResult` | `Equidna\SwiftAuth\Classes\Notifications\DTO\NotificationResult` |
| `Equidna\SwiftAuth\Classes\Traits\ChecksRateLimits` | `Equidna\SwiftAuth\Classes\Auth\Traits\ChecksRateLimits` |

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
