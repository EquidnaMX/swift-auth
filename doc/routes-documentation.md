# Routes Documentation

SwiftAuth routes are automatically registered by `SwiftAuthServiceProvider`.

**Configuration:**
- **Prefix:** `config('swift-auth.route_prefix')` (Default: `/swift-auth`)
- **Middleware:** `web`, `SwiftAuth.SecurityHeaders`

## Route List

| Method | URI (Default) | Name | Controller | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/swift-auth/login` | `login.form` | `AuthController@showLoginForm` | Shows login page. |
| `POST` | `/swift-auth/login` | `login` | `AuthController@login` | Handles login attempt. |
| `POST` | `/swift-auth/logout` | `logout` | `AuthController@logout` | Logs out user. |
| `GET` | `/swift-auth/users/register` | `public.register` | `UserController@register` | Registration form (if enabled). |
| `POST` | `/swift-auth/users` | `public.register.store` | `UserController@store` | Handles registration. |

### Password Reset

| Method | URI | Name | Controller |
| :--- | :--- | :--- | :--- |
| `GET` | `/swift-auth/password` | `password.request.form` | `PasswordController@showRequestForm` |
| `POST` | `/swift-auth/password` | `password.request.send` | `PasswordController@sendResetLink` |
| `GET` | `/swift-auth/password/sent` | `password.request.sent` | `PasswordController@showRequestSent` |
| `GET` | `/swift-auth/password/{token}` | `password.reset.form` | `PasswordController@showResetForm` |
| `POST` | `/swift-auth/password/reset` | `password.reset.update` | `PasswordController@resetPassword` |

### WebAuthn (Passkeys)

| Uri | Name | Controller | Description |
| :--- | :--- | :--- | :--- |
| `.../webauthn/register/options` | `webauthn.register.options` | `WebAuthnController@registerOptions` | **Auth Required.** |
| `.../webauthn/register` | `webauthn.register` | `WebAuthnController@register` | verify & save. |
| `.../webauthn/login/options` | `webauthn.login.options` | `WebAuthnController@loginOptions` | **Public.** |
| `.../webauthn/login` | `webauthn.login` | `WebAuthnController@login` | Verify & login. |

### Admin Routes (Protected)

**Middleware:** `auth`, `SwiftAuth.CanPerformAction:sw-admin`

defined in secondary route files required by `routes/swift-auth.php`:

- `swift-auth-users.php`: User management.
- `swift-auth-roles.php`: Role management.
- `swift-auth-admin-sessions.php`: Global session oversight.
