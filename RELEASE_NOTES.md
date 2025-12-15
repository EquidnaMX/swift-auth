# Release v2.0.0 "Obsidian"

**Release Date:** 2025-12-15

**SwiftAuth v2.0.0** ("Obsidian") is a major release focused on architectural rigidity, strict standards compliance, and developer clarity. It transitions the codebase to a fully strict-typed, Domain-Driven Design (DDD) structure, ensuring higher reliability and better static analysis integration.

While standard features (Login, MFA, Registration) work as expected, the internal structure has changed significantly, which may impact developers who have deeply extended package internals.

## 🚀 Highlights

-   **Domain-Driven Structure:** Internal classes are now organized into clear domains (`Auth`, `Notifications`, `Users`).
-   **Strict Typing:** Zero-compromise adoption of PHP strict types and return declarations across the board.
-   **Documentation Overhaul:** New `/doc` directory with comprehensive diagrams, API references, and deployment guides.
-   **Leaner Codebase:** ~350 lines of redundant documentation removed in favor of expressive type signatures.

## ⚠️ Breaking Changes

See [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for the complete migration guide.

-   Namespace reorganization in `src/Classes/`.
-   Strict type enforcement in method signatures (requires updates to overriding child classes).
-   Constructor property promotion adopted in DTOs.

## 📝 Changelog

### Changed
-   Moved Notification services to `Classes/Notifications`.
-   Moved Auth DTOs and Services to `Classes/Auth`.
-   Standardized all file headers with File-Level DocBlocks.
-   Removed redundant `@param` and `@return` tags from PHPDoc.

### Added
-   New Architectural Diagrams (`doc/architecture-diagrams.md`).
-   Full API Documentation (`doc/api-documentation.md`).
-   Operational Monitoring Guide (`doc/monitoring.md`).

---

*For full history, see [CHANGELOG.md](CHANGELOG.md).*
