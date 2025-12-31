# Open Questions & Assumptions

## Assumptions

1.  **Frontend Stack:**
    SwiftAuth supports three frontend stacks: Blade (traditional server-side), React + TypeScript (Inertia.js), and React + JavaScript (Inertia.js). The package publishes raw source files for React frontends, expecting the consuming application to configure their build tool (Vite/Webpack) to compile assets. Inertia components use the `SwiftAuth/` namespace (e.g., `SwiftAuth/Login`) to avoid conflicts with application components.

2.  **Notification Service:**
    It is assumed that `equidna/bird-flock` is configured and credentials are set in the host application's `.env` file, as SwiftAuth relies on it for email delivery without providing its own mailer configuration.

3.  **Table Names:**
    Documentation assumes default table names (e.g., `swift-auth_Users`). If `TABLE_PREFIX` is changed, manual adjustments to SQL queries or external tools are expected.

4.  **Sanctum:**
    It handles API tokens but SwiftAuth's primary flow is Session-based. The docs focus on Session auth.

## Open Questions

-   **WebAuthn FIDO2 Server:** Does the `laragear/webauthn` package require a specific RP ID configuration in specific environments (e.g. subdomains)? (Check upstream docs).
-   **Session Driver Compatibility:** Does `PurgeStaleSessions` work effectively with `redis` or `memcached` drivers, or is it strictly for `database` driver maintenance? (Currently assumes `database` driver for listing/purging features).
