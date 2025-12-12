# Changelog

All notable changes to this project will be documented in this file.

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
