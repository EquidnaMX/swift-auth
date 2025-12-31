# SwiftAuth Localization Guide

## Overview

SwiftAuth provides comprehensive localization support for English (en) and Spanish (es). The package includes translations for all UI elements, validation messages, email templates, and notifications.

## Features

-   **Dual Language Support:** English and Spanish translations
-   **Dynamic Language Switching:** Users can toggle between languages via UI
-   **Session Persistence:** Language preference is stored in the session
-   **Inertia.js Integration:** Translations are automatically shared with React components
-   **Comprehensive Coverage:** All modules (auth, email, session, user, role) are translated

## Available Languages

-   `en` - English
-   `es` - Spanish

## Translation Files

Translations are organized by module in `resources/lang/{locale}/`:

```
resources/lang/
├── en/
│   ├── auth.php      # Authentication messages
│   ├── email.php     # Email templates and notifications
│   ├── session.php   # Session management messages
│   ├── user.php      # User management
│   └── role.php      # Role and permissions
└── es/
    ├── auth.php
    ├── email.php
    ├── session.php
    ├── user.php
    └── role.php
```

## Using Translations

### In PHP/Blade

Use the `__()` helper with the `swift-auth::` namespace:

```php
// In controllers
return redirect()->with('message', __('swift-auth::auth.login_success'));

// In Blade templates
{{ __('swift-auth::auth.login_title') }}
@lang('swift-auth::email.verify_button')
```

### In JavaScript/TypeScript

Import and use the `__()` helper from `translations.js` or `translations.ts`:

```typescript
import { __ } from '../../../lang/translations';

// In React components
<h1>{__('auth.login_title')}</h1>
<button>{__('auth.login_button')}</button>
```

Note: Do not include the `swift-auth::` prefix in JavaScript translations.

## Language Switcher Component

### Integration

The `LanguageSwitcher` component is available in both TypeScript and JavaScript versions:

```tsx
import { LanguageSwitcher } from "../components/LanguageSwitcher";

// In your layout or navbar
<LanguageSwitcher currentLocale={locale} className="optional-class" />;
```

The component displays EN/ES toggle buttons and automatically handles:

-   Visual active state for current locale
-   POST request to switch locale
-   Page reload to apply new translations
-   Session persistence

### Example Integration (Navbar)

```tsx
import { LanguageSwitcher } from "../LanguageSwitcher";
import { usePage } from "@inertiajs/react";

export function Navbar({ user }) {
    const { locale } = usePage().props;

    return (
        <nav>
            <div>
                {/* Other nav items */}
                <LanguageSwitcher currentLocale={locale} />
            </div>
        </nav>
    );
}
```

## Backend Architecture

### Locale Switching Route

```php
POST /swift-auth/locale/{locale}
```

-   **Controller:** `LocaleController@switch`
-   **Validation:** Locale must be `en` or `es`
-   **Action:** Stores locale in session, sets app locale, redirects back
-   **Named Route:** `swift-auth.locale.switch`

### Middleware: ShareInertiaData

The `ShareInertiaData` middleware automatically shares:

1. **Authenticated user data** (`auth` key)
2. **Current locale** (`locale` key)
3. **All translations** (`translations` key)

Translations are flattened into a single object:

```json
{
    "auth.login_title": "Log In",
    "auth.login_button": "Sign In",
    "user.name": "Name",
    "user.email": "Email Address"
}
```

### Session Restoration

On application boot, the service provider:

1. Checks for `locale` in session
2. Validates against allowed locales (`['en', 'es']`)
3. Sets `App::setLocale()` to restore user preference

This ensures the correct locale is active on every request.

## Translation Helper Fallback Chain

The JavaScript translation helper uses a multi-tier fallback:

1. **Inertia Shared Props:** `window.page?.props?.translations` (primary)
2. **Window Object:** `window.swiftAuthTranslations` (legacy)
3. **Hardcoded Fallbacks:** Embedded translation objects (offline support)

This ensures translations work even if:

-   Backend sharing fails
-   Inertia data is unavailable
-   Network requests are blocked

## Adding New Translations

### 1. Add to Translation Files

Edit both `resources/lang/en/{module}.php` and `resources/lang/es/{module}.php`:

```php
// resources/lang/en/auth.php
return [
    // Existing translations...
    'new_key' => 'New message in English',
];

// resources/lang/es/auth.php
return [
    // Existing translations...
    'new_key' => 'Nuevo mensaje en español',
];
```

### 2. Add to JavaScript Fallbacks (Optional)

Update `resources/lang/translations.js` and `translations.ts`:

```typescript
const fallbackTranslations = {
    en: {
        auth: {
            // Existing translations...
            new_key: "New message in English",
        },
    },
    es: {
        auth: {
            // Existing translations...
            new_key: "Nuevo mensaje en español",
        },
    },
};
```

### 3. Use in Code

```php
// PHP/Blade
{{ __('swift-auth::auth.new_key') }}

// JavaScript/TypeScript
{__('auth.new_key')}
```

## Adding New Languages

To add a new language (e.g., French):

1. **Create translation directory:**

    ```
    mkdir resources/lang/fr
    ```

2. **Copy and translate files:**

    ```powershell
    cp resources/lang/en/*.php resources/lang/fr/
    # Edit each file to translate to French
    ```

3. **Update locale validation:**

    ```php
    // src/Http/Controllers/LocaleController.php
    if (!in_array($locale, ['en', 'es', 'fr'], strict: true)) {
        // error handling
    }
    ```

4. **Update service provider:**

    ```php
    // src/Providers/SwiftAuthServiceProvider.php (boot method)
    if (in_array($locale, ['en', 'es', 'fr'], strict: true)) {
        App::setLocale($locale);
    }
    ```

5. **Update JavaScript fallbacks:**

    ```typescript
    const fallbackTranslations = {
        en: {
            /* ... */
        },
        es: {
            /* ... */
        },
        fr: {
            /* ... */
        }, // Add French translations
    };
    ```

6. **Update LanguageSwitcher component** to include FR button.

## Best Practices

1. **Always use translation keys** - Never hardcode user-facing strings
2. **Maintain consistency** - Use the same keys across languages
3. **Test both languages** - Verify translations display correctly
4. **Keep fallbacks updated** - Sync JavaScript fallbacks with PHP files
5. **Use semantic keys** - Prefer `auth.login_button` over `button_1`
6. **Document context** - Add comments for ambiguous translations

## Troubleshooting

### Translations not appearing

1. Check middleware is registered: `SwiftAuth.ShareInertiaData`
2. Verify locale is in session: `Session::get('locale')`
3. Ensure translation files exist in `resources/lang/{locale}/`
4. Check browser console for JavaScript errors

### Language switch not persisting

1. Verify session driver is configured (`config/session.php`)
2. Check locale validation in `LocaleController`
3. Ensure service provider restores locale on boot

### Inertia not receiving translations

1. Confirm `ShareInertiaData` middleware is active
2. Check Inertia version compatibility (>= 1.0)
3. Verify `loadTranslations()` method returns valid array

### Missing translations showing keys

This indicates the key doesn't exist in the translation file. Add the missing key to both language files.

## Related Files

-   **Controllers:** `src/Http/Controllers/LocaleController.php`
-   **Middleware:** `src/Http/Middleware/ShareInertiaData.php`
-   **Components:**
    -   `resources/ts/components/LanguageSwitcher.tsx`
    -   `resources/js/components/LanguageSwitcher.jsx`
-   **Helpers:**
    -   `resources/lang/translations.ts`
    -   `resources/lang/translations.js`
-   **Service Provider:** `src/Providers/SwiftAuthServiceProvider.php`
-   **Routes:** `routes/swift-auth.php` (locale switching route)
-   **Translations:** `resources/lang/{en,es}/*.php`

## API Reference

### LocaleController Methods

```php
public function switch(Request $request, string $locale): RedirectResponse
```

Switches the application locale and stores it in the session.

**Parameters:**

-   `$locale` - Target locale code (`en` or `es`)

**Returns:** Redirect response to previous page

**Throws:** 404 if locale is invalid

### ShareInertiaData Methods

```php
public function handle(Request $request, Closure $next): Response
```

Shares authentication, locale, and translation data with Inertia.

```php
private function loadTranslations(string $locale): array
```

Loads all translation files for a locale and flattens them.

**Parameters:**

-   `$locale` - Locale code to load

**Returns:** Flattened translation array

### Translation Helper Functions

```typescript
function __(key: string, replacements?: Record<string, string>): string;
```

Retrieves a translation by key with optional replacements.

```typescript
function getCurrentLocale(): string;
```

Returns the current locale code (`en` or `es`).

```typescript
function getTranslations(): Record<string, string>;
```

Returns all available translations for the current locale.

---

For more information, see:

-   [Architecture Diagrams](./architecture-diagrams.md)
-   [Routes Documentation](./routes-documentation.md)
-   [API Documentation](./api-documentation.md)
