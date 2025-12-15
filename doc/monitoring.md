# Monitoring & Operations

## Logging

SwiftAuth emits structured logs via the default Laravel Log channel (`stack`/`single`/`daily`).

**Log Keys to Watch:**

| Log Key | Level | Description |
| :--- | :--- | :--- |
| `swift-auth.user.logged-in` | `info` | User session created. |
| `swift-auth.user.logged-out` | `info` | User session terminated. |
| `swift-auth.session.evicted` | `info` | Session removed due to concurrency limits. |
| `swift-auth.mfa.challenge-started` | `info` | MFA flow initiated. |
| `swift-auth.login.failed` | `warning` | Failed login attempt (bad credentials). |
| `swift-auth.lockout` | `notice` | Account locked due to repeated failures. |

## Health Checks

### Scheduler
Monitor the exit status of the scheduled commands:
- `swift-auth:purge-stale-sessions`
- `swift-auth:purge-expired-tokens`

Failures here may lead to database bloat.

### Database
- **Users Table:** Monitor growth rate.
- **Sessions Table:** Ensure it doesn't grow indefinitely (implies purge failure).

## Alerts

Recommended alerting rules:

1.  **High Failed Login Rate:** > 50 per minute (Possible brute force).
2.  **Account Lockout Spikes:** > 10 per hour.
3.  **MFA Failures:** High rate of starts without completions.
