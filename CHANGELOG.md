# Changelog

All notable changes to this project will be documented in this file.

## [3.0.0] - "Sovereign" - 2025-01-XX

### Breaking Changes

-   **REMOVED:** `laravel/sanctum` dependency completely removed from package
    -   Sanctum API token system replaced with native `UserToken` implementation
    -   Migration path: See BREAKING_CHANGES.md for detailed upgrade instructions
    -   Applications using Sanctum must migrate to SwiftAuth's UserToken system
-   **REMOVED:** Sanctum migrations no longer published during installation
-   **CHANGED:** API authentication now uses `SwiftAuth.AuthenticateWithToken` middleware instead of Sanctum's

### Added

-   **Native API Authentication System:**
    -   `UserToken` model for API token management with SHA-256 hashing
    -   `UserTokenService` for token CRUD operations (create, validate, revoke, purge)
    -   `create_user_tokens_table` migration with proper indexes and foreign keys
    -   Token abilities/scopes system for fine-grained permissions
    -   Token expiration and usage tracking (`last_used_at`, `expires_at`)
-   **Authentication Middleware:**
    -   `SwiftAuth.AuthenticateWithToken` - Bearer token validation middleware
    -   `SwiftAuth.CheckTokenAbilities` - Ability-based authorization middleware
-   **Comprehensive Localization System:**
    -   Full English (en) and Spanish (es) translation support
    -   10 translation files: `auth.php`, `email.php`, `session.php`, `user.php`, `role.php` (per language)
    -   `LocaleController` for dynamic language switching via POST `/locale/{locale}`
    -   `ShareInertiaData` middleware to share translations with Inertia.js
    -   `LanguageSwitcher` React component (TypeScript + JavaScript versions)
    -   JavaScript/TypeScript translation helpers (`translations.ts`, `translations.js`)
    -   Session-based locale persistence across requests
-   **Documentation:**
    -   `doc/securing-routes.md` - Comprehensive 400+ line guide for route protection
    -   `doc/localization.md` - Complete localization implementation guide
    -   Updated `README.md` with security quick reference
    -   Updated all existing docs to reflect UserToken system

### Changed

-   **Installation Command:**
    -   `InstallSwiftAuth` now publishes translation files (`swift-auth:lang` tag)
    -   Removed Sanctum migration publish step
    -   Updated documentation messages for admin user creation
    -   Migration publishing now groups all migrations together before running `migrate`
-   **Admin Command:**
    -   `CreateAdminUser` no longer accepts password as CLI argument (security improvement)
    -   Always prompts securely for password using `secret()` helper
    -   Auto-generates secure random password if left empty
    -   Removed environment variable fallback (`SWIFT_ADMIN_NAME`, `SWIFT_ADMIN_EMAIL`)
    -   Command signature updated: `swift-auth:create-admin {name} {email}`
-   **Service Provider:**
    -   Locale restoration on boot from session storage
    -   Registered new middleware: `SwiftAuth.ShareInertiaData`, `SwiftAuth.AuthenticateWithToken`, `SwiftAuth.CheckTokenAbilities`
    -   Removed `configureSanctum()` method
    -   Added translation file loading and sharing
-   **Routes:**
    -   Consolidated email verification routes into main `swift-auth.php` file
    -   Deleted separate `swift-auth-email-verification.php` route file
    -   Added locale switching route: `POST /swift-auth/locale/{locale}`
-   **Controllers:**
    -   Updated all controller responses to use translation keys
    -   `AuthController` now uses `__('swift-auth::auth.login_success')` format
    -   `EmailVerificationController` fully internationalized with proper rate limiting
    -   `PasswordController` updated rate limit defaults (3 attempts per 300 seconds)
    -   All Inertia component paths updated to new naming convention
-   **Views & Frontend:**
    -   All Blade email templates internationalized (`@lang` directives)
    -   All Inertia React components updated with `__()` translation helper
    -   Removed hardcoded Spanish/English strings from UI
    -   Login, register, password reset forms fully localized
-   **Code Quality:**
    -   Fixed 6 identified issues: SHA-256 consistency, debug code removal, documentation updates, index optimization, config references, rate limit validation
    -   Consolidated database indexes into original migration files
    -   Removed empty constructor bodies, added single-line placeholder comments
    -   Improved import organization across all files
-   **Notifications:**
    -   Email subjects now use translation keys (`__('swift-auth::email.reset_subject')`)
    -   Support for locale-based email content
-   **Architecture:**
    -   Updated all architecture diagrams with UserTokenService
    -   Added UserTokens table to ERD
    -   Updated API flow documentation

### Fixed

-   Rate limiting validation in `PasswordController` (enforces numeric values)
-   Database indexes now created inline with original migrations (performance optimization)
-   Configuration key references consistent across codebase
-   SelectiveRender trait now correctly handles TypeScript/JavaScript frontend detection
-   DTO constructor formatting (empty constructors with placeholder comments)

### Security

-   **Admin password handling:** Never accepts passwords as CLI arguments
-   **Token hashing:** Uses SHA-256 for all stored tokens (consistent with RememberToken, PasswordResetToken)
-   **Ability checks:** Fine-grained permission system for API routes
-   **Expiration tracking:** Automatic token expiration with configurable TTL

### Documentation

-   Added comprehensive route security guide with examples
-   Added localization implementation guide
-   Updated README with UserToken quick reference
-   Updated API documentation with UserToken endpoints
-   Updated architecture diagrams
-   Updated deployment instructions
-   Updated artisan commands documentation

### Deprecated

-   None

### Removed

-   `laravel/sanctum` package dependency
-   Sanctum migration publishing
-   `create_sanctum_api_tokens_table.php` migration
-   `configureSanctum()` method from `SwiftAuthServiceProvider`
-   `swift-auth-email-verification.php` route file (consolidated)
-   Environment variable fallback for admin creation

---

## [2.0.0] - 2025-12-15

### Changed

-   **Breaking:** Enforced strict Domain-Driven Design structure in `src/Classes/`. (Classes moved to `Auth/`, `Notifications/`, `Users/` domains).
-   **Breaking:** Strict Coding Standards adoption (PSR-12 + Custom Rules).
    -   Enforced return types on all methods.
    -   Constructor property promotion widely adopted.
    -   Removed redundant PHPDoc where native types exist.
-   **Breaking:** File-level DocBlocks are now mandatory and standardized.
-   **Documentation:** Massive cleanup of PHPDoc to reduce noise and rely on Type Hints.

### Added

-   **Docs:** Complete architectural documentation (`/doc` folder) covering API, Deployment, Monitoring, and Business Logic.
-   **Events:** Added missing file-level DocBlocks to Auth Events (`UserLoggedIn`, `UserLoggedOut`, etc.).

### Fixed

-   False positive lint errors in Controllers regarding Facade usage.
-   Redundant documentation in DTOs and Services.

## [1.0.3] - 2025-12-12

### Added

-   **Test Infrastructure**: Complete test environment with Orchestra Testbench
    -   Database migrations now run automatically in tests
    -   All 5 package migrations (Users, Roles, Sessions, RememberTokens, PasswordResetTokens)
    -   In-memory SQLite database for fast testing
    -   Test helpers available globally via TestHelpers trait
-   **External Dependencies**: BirdFlock facade stub for testing without external packages
-   **Test Coverage**: 99/168 tests passing (59%), focused on unit and service layer

### Changed

-   **Code Quality**: PHPStan Level 5 analysis with zero errors
    -   Added facade type aliases for better static analysis
    -   Fixed TokenMetadataValidator to use explicit count check
    -   Removed unused private methods (recordUserSession, deleteUserSession)
-   **Code Style**: 100% PSR-12 compliance via PHPCS
    -   All 16 formatting violations auto-fixed
    -   Consistent code style across entire codebase
-   **Tests**: Converted model tests to database-backed tests
    -   UserTest now uses RefreshDatabase trait
    -   Real Eloquent relationships instead of mocks
    -   More realistic test scenarios

### Fixed

-   Test environment configuration for encryption keys and app settings
-   BirdFlock class not found errors in feature tests
-   Role search test case sensitivity issue
-   Missing 'name' field in User model test creation

### Infrastructure

-   PHPStan configuration updated with facade recognition
-   PHPCS/PHPCBF configured for PSR-12 with 250 char line limit
-   Tests now extend package TestCase with full Laravel services
-   Test database properly configured with empty table prefix

---

## [1.0.2] - 2025-11-20

### Changed

-   Config: removed `password_rules` and consolidated password policy into `password_min_length`.
-   Config: added optional `hash_driver` option to allow explicit hash backend selection.
-   Code: controllers and commands now use `password_min_length` and respect `hash_driver` when present.
-   Docs: updated `README.md` and added `UPGRADING.md` with upgrade notes and provider instructions.
-   Lint: ensured PSR-12 compliance via PHPCS.

---

## [1.0.1] - 2025-11-17

### Changed

-   Version bump for maintenance and dependency updates (see composer.json).

### Added

-   Initial stable release: authentication and authorization for Laravel projects
-   Admin creation command with secure password handling
-   Password reset flow with TTL and rate-limiting
-   Configurable frontend (Blade, TypeScript, JavaScript)
-   Queue-based password reset emails
-   PSR-12 code style and unit test guidance

### Changed (1.0.0)

-   Namespace standardized to `Equidna\SwiftAuth`
-   Removed legacy admin password config for security

### Security

-   No plaintext password exposure for admin creation
-   Password reset tokens and rate-limiting improvements

---

See previous commits for pre-1.0.0 changes.
