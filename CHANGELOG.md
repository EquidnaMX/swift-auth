# Changelog

All notable changes to this project will be documented in this file.

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
