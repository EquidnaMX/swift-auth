# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2025-11-17

### Added

-   Initial stable release: authentication and authorization for Laravel projects
-   Admin creation command with secure password handling
-   Password reset flow with TTL and rate-limiting
-   Configurable frontend (Blade, TypeScript, JavaScript)
-   Queue-based password reset emails
-   PSR-12 code style and unit test guidance

### Changed

-   Namespace standardized to `Equidna\SwifthAuth`
-   Removed legacy admin password config for security

### Security

-   No plaintext password exposure for admin creation
-   Password reset tokens and rate-limiting improvements

---

See previous commits for pre-1.0.0 changes.
