# Open Questions & Assumptions

## Assumptions

1.  **Frontend Stack:**
    The documentation assumes the user might be using Inertia/React based on the `resources/ts` folder existence, but config allows for 'blade' fallback. The README Setup assumes a backend-heavy setup (Blade/Inertia) handled via Laravel Mix or Vite.

2.  **Notification Service:**
    It is assumed that `equidna/bird-flock` is configured and credentials are set in the host application's `.env` file, as SwiftAuth relies on it for email delivery without providing its own mailer configuration.

3.  **Table Names:**
    Documentation assumes default table names (e.g., `swift-auth_Users`). If `TABLE_PREFIX` is changed, manual adjustments to SQL queries or external tools are expected.

4.  **Sanctum:**
    It handles API tokens but SwiftAuth's primary flow is Session-based. The docs focus on Session auth.

## Open Questions

- **WebAuthn FIDO2 Server:** Does the `laragear/webauthn` package require a specific RP ID configuration in specific environments (e.g. subdomains)? (Check upstream docs).
- **Session Driver Compatibility:** Does `PurgeStaleSessions` work effectively with `redis` or `memcached` drivers, or is it strictly for `database` driver maintenance? (Currently assumes `database` driver for listing/purging features).
