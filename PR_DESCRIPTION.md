# Release v2.0.0 - Obsidian

## Motivation
This release focuses on hardening the package architecture, enforcing strict coding standards, and improving the developer experience through better structure and clearer documentation. It addresses the need for a typed, reliable foundation as the package scales.

## Summary of Changes

### Architecture & Standards (Breaking)
-   Refactored `src/Classes` into Domain-Driven namespaces (`Auth`, `Notifications`, `Users`).
-   Enforced native PHP return types and parameter types (removing "loose" code).
-   Adopted Constructor Property Promotion for cleaner DTOs.

### Documentation
-   Added comprehensive project documentation in `/doc`.
-   Included Architecture Diagrams, API references, and Deployment guides.
-   Cleaned up inline PHPDoc to remove redundancy.

### Quality Assurance
-   Achieved 100% PHPStan Level 5 (clean) on new changes.
-   All 87 tests passing with 163 assertions.

## Testing Checklist
-   [x] Unit Tests passing (`composer test`)
-   [x] Static Analysis passing (`phpstan`)
-   [x] Documentation updated

## Risk & Impact
**High.** This is a major version bump. Existing applications using standard features will be fine, but developers extending core classes (e.g., `NotificationService`) will need to update namespaces and method signatures.

See [BREAKING_CHANGES.md](./BREAKING_CHANGES.md) for details.
