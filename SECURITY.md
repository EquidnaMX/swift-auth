# Security Guidance

Swift Auth ships hardened defaults, but production deployments should enforce a few additional controls. This document highlights the most important runtime dependencies and cookie/session safeguards.

## Runtime dependencies

Swift Auth relies on the following packages at runtime:

-   **Laravel 11.21+** – provides the HTTP kernel, queue system, and session handling.
-   **equidna/laravel-toolkit** – supplies the `ResponseHelper` utilities and domain exceptions used by the controllers.
-   **inertiajs/inertia-laravel** – required when rendering the Inertia front-end variant.
-   **Swift Auth core** – this package (`equidna/swift-auth`).

Make sure these dependencies remain up to date (respecting semantic versioning) and monitor their changelogs for security advisories.

## Cookie and session hardening

Set the following values in your host application's `.env` file to reduce session hijacking risk:

| Environment variable     | Recommended value                                         | Rationale                                                                                                 |
| ------------------------ | --------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `SESSION_DRIVER`         | `redis`, `database`, or another shared store              | Keeps sessions server-side for horizontal scaling.                                                        |
| `SESSION_SECURE_COOKIE`  | `true`                                                    | Prevents cookies from being transmitted over HTTP.                                                        |
| `SESSION_HTTP_ONLY`      | `true`                                                    | Blocks JavaScript from reading the session cookie.                                                        |
| `SESSION_SAME_SITE`      | `strict` (or `lax` if third-party redirects are required) | Mitigates CSRF attacks by limiting cross-site cookie use.                                                 |
| `SESSION_DOMAIN`         | Match your primary domain (e.g., `.example.com`)          | Ensures the cookie is only sent to trusted hosts.                                                         |
| `SESSION_COOKIE`         | `swift_auth_session`                                      | Helps differentiate Swift Auth sessions from the host application's own session cookie when both coexist. |
| `SWIFT_AUTH_SUCCESS_URL` | HTTPS URL under your domain                               | Avoids open redirects after login/logout.                                                                 |

Additional recommendations:

-   Regenerate the session ID after login and logout (Swift Auth already calls `regenerate()`/`regenerateToken()`).
-   Configure your reverse proxy or CDN to set `Strict-Transport-Security`, `X-Frame-Options`, and `Content-Security-Policy` headers appropriate for your app.
-   Disable browser autocomplete on admin login forms if your compliance rules require it.

Review this guidance after each release and expand it with any organization-specific controls.
