# Release v3.0.0 "Sovereign"

**Release Date:** 2025-01-XX  
**Codename:** Sovereign  
**Type:** Major Release (Breaking Changes)

**SwiftAuth v3.0.0** ("Sovereign") marks a transformative milestone: **complete independence from external authentication dependencies** while delivering comprehensive internationalization support. This release removes `laravel/sanctum` and introduces a native, table-prefix-aware API token system, alongside full English/Spanish localization.

## 🎯 What's New

### 🔐 Native API Authentication System

SwiftAuth now includes a **fully integrated API token system** that respects your table prefix and follows established security patterns.

**Key Features:**

-   ✅ **UserToken Model** with SHA-256 hashing (consistent with RememberToken/PasswordResetToken)
-   ✅ **Fine-grained Abilities/Scopes** for precise permission control
-   ✅ **Token Expiration & Usage Tracking** (`expires_at`, `last_used_at`)
-   ✅ **Dedicated Middleware** (`SwiftAuth.AuthenticateWithToken`, `SwiftAuth.CheckTokenAbilities`)
-   ✅ **Table Prefix Support** out of the box

**Example:**

```php
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;

$tokenService = app(UserTokenService::class);
$result = $tokenService->createToken(
    user: $user,
    name: 'mobile-app',
    abilities: ['posts:read', 'posts:create'],
    expiresAt: now()->addDays(30),
);

$plainToken = $result['token']; // Store securely!
```

### 🌍 Complete Localization Support

SwiftAuth now speaks **English and Spanish** natively, with flexible architecture for additional languages.

**Included:**

-   ✅ 10 Translation Files per language (auth, email, session, user, role)
-   ✅ Dynamic Language Switcher UI component (React TypeScript + JavaScript)
-   ✅ Session-Based Locale Persistence
-   ✅ Inertia.js Integration for seamless frontend translations
-   ✅ Fully Localized Email Templates

**Example:**

```php
// PHP/Blade
{{ __('swift-auth::auth.login_title') }}

// JavaScript/TypeScript
import { __ } from './translations';
<h1>{__('auth.login_title')}</h1>
```

### 🛡️ Enhanced Security

**Admin Password Handling:**

-   Passwords **never** accepted as CLI arguments (prevents shell history exposure)
-   Secure interactive prompting with `secret()` helper
-   Auto-generation option for maximum security

**Before:**

```bash
php artisan swift-auth:create-admin "Admin" admin@example.com password123  # ❌ Insecure
```

**After:**

```bash
php artisan swift-auth:create-admin "Admin" admin@example.com
# Prompts: "Enter admin password (leave empty to generate random):"  # ✅ Secure
```

## 📚 Comprehensive Documentation

New guides to help you secure routes and localize your application:

1. **[securing-routes.md](./doc/securing-routes.md)** — 400+ line guide covering session auth, API tokens, hybrid patterns, and testing
2. **[localization.md](./doc/localization.md)** — Complete implementation guide for translations

## ⚠️ Breaking Changes

**This is a MAJOR release with breaking changes.** See [BREAKING_CHANGES.md](./BREAKING_CHANGES.md) for detailed migration instructions.

### Critical Changes:

1. **Sanctum Dependency Removed**

    - `laravel/sanctum` completely removed
    - Native `UserToken` system replaces Sanctum
    - Middleware: `auth:sanctum` → `SwiftAuth.AuthenticateWithToken`

2. **Admin Command Security Update**

    - Password argument removed from CLI
    - Environment variables no longer supported
    - Interactive password entry required

3. **Installation Changes**
    - Translations now published automatically
    - Sanctum migrations no longer published

## 🚀 Migration Quickstart

```bash
# 1. Update dependencies
composer remove laravel/sanctum
composer require equidna/swift-auth:^3.0

# 2. Publish new migrations
php artisan vendor:publish --tag=swift-auth:migrations --force
php artisan migrate

# 3. Update middleware
# Before: Route::middleware('auth:sanctum')
# After:  Route::middleware('SwiftAuth.AuthenticateWithToken')

# 4. Update token creation
# Before: $user->createToken('name', ['ability'])->plainTextToken
# After:  UserTokenService::createToken($user, 'name', ['ability'])
```

**Complete Migration Guide:** [BREAKING_CHANGES.md](./BREAKING_CHANGES.md)

## 🎨 Additional Improvements

-   ✅ Fixed 6 code quality issues (SHA-256, debug removal, docs, indexes, config, rate limits)
-   ✅ Consolidated database indexes into original migrations
-   ✅ Route file consolidation (email verification moved to main routes)
-   ✅ Updated architecture diagrams with UserTokenService
-   ✅ All frontend components internationalized

## 📊 By The Numbers

-   **30+ commits** since v2.0.0
-   **2 languages** supported (English, Spanish)
-   **10 translation files** per language
-   **400+ lines** of route security documentation
-   **Zero** external authentication dependencies
-   **100%** table prefix compatibility

## 🙏 Acknowledgments

Special thanks to **Gabriel Ruelas** (@gruelas) for architecture and native token system implementation.

## 🔮 What's Next?

-   Additional language support (French, German, Portuguese)
-   OAuth2/OIDC integration options
-   Enhanced MFA capabilities

**Happy Authenticating! 🚀**

_SwiftAuth v3.0.0 "Sovereign" — Building on solid foundations, owning our future._

---

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

_For full history, see [CHANGELOG.md](CHANGELOG.md)._
