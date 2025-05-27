# Swift Auth

**Swift Auth** is a simple yet efficient system designed for user management, providing an intuitive and scalable solution to manage access and permissions.

# Features

-   **User management**: Create, update, delete, and view users with roles and permissions.
-   **Authentication**: Simplifies user login and registration.
-   **Roles and Permissions**: Assign roles to users and check if they have specific permissions to perform actions.
-   **Middleware**: Protect routes that require authentication or special permissions.
-   **Easy to integrate**: Compatible with **Laravel** and **Blade**, with support for **TypeScript** and **JavaScript**.

# Environment Variables

To run this project, you need to add the following environment variables to your `.env` file:

-   `SWIFT_AUTH_FRONTEND`: Defines the type of frontend you will use. It can have one of the following values: **typescript**, **blade**, or **javascript**.

    **Example:**

    ```bash
    SWIFT_AUTH_FRONTEND=typescript
    ```

-   `SWIFT_AUTH_SUCCESS_URL`: Defines the URL to redirect to after a successful login.

    **Example:**

    ```bash
    SWIFT_AUTH_SUCCESS_URL=/dashboard
    ```

# Installation

1. **Create the project**:
   If you haven't created the project yet, do so and navigate to your project directory:

    ```bash
    mkdir my-project
    cd my-project
    ```

2. **Install Swift Auth**:

    **Install with Composer**:

    ```bash
    composer require teleurban/swift-auth
    ```

    If you encounter an error, use the beta version:

    ```bash
    composer require teleurban/swift-auth:dev-main
    ```

3. **Install Swift Auth**:
   Run the following command to install Swift Auth:

    ```bash
    php artisan swift-auth:install
    ```

    After running the command, it will ask if you want to publish different configuration files and resources.

4. **Run migrations**:
   Run the migrations to create the necessary tables in the database:
    ```bash
    php artisan migrate
    ```

---

# Middleware

Swift Auth provides middleware to protect routes that require authentication or special permissions. You must add it to the corresponding routes in your routes file.

**Add Authentication Middleware**:

```php
use Teleurban\SwiftAuth\Http\Middleware\RequireAuthentication;

Route::middleware(RequireAuthentication::class)->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

**Add Action Middleware**:
If you want to protect a specific action that only certain users can perform, you can use the `CanPerformAction` middleware. This checks if the user has the necessary permission to perform the requested action.

```php
use Teleurban\SwiftAuth\Http\Middleware\CanPerformAction;

Route::middleware(CanPerformAction::class . ':sw-admin')->group(function () {
    Route::post('/create', [UserController::class, 'create']);
});
```

---

# Swift Auth - Route Reference Guide

This guide outlines all the available routes used in the **Swift Auth** package, grouped by feature. All routes are prefixed with `/swift-auth` and protected by the necessary middleware.

---

## 📌 Authentication Routes

| Method   | URL                | Name                  | Description       |
| -------- | ------------------ | --------------------- | ----------------- |
| GET      | /swift-auth/login  | swift-auth.login.form | Show login form   |
| POST     | /swift-auth/login  | swift-auth.login      | Authenticate user |
| GET/POST | /swift-auth/logout | swift-auth.logout     | Logout user       |

---

## 🔒 Password Reset Routes

| Method | URL                                | Name                        | Description                      |
| ------ | ---------------------------------- | --------------------------- | -------------------------------- |
| GET    | /swift-auth/password/reset         | swift-auth.password.request | Show password reset request form |
| POST   | /swift-auth/password/email         | swift-auth.password.email   | Send reset link to user's email  |
| GET    | /swift-auth/password/reset/{token} | swift-auth.password.reset   | Show new password form           |
| POST   | /swift-auth/password/reset         | swift-auth.password.update  | Update password                  |

---

## 👥 User Management Routes

| Method | URL                              | Name                      | Description             |
| ------ | -------------------------------- | ------------------------- | ----------------------- |
| GET    | /swift-auth/users                | swift-auth.users.index    | List users              |
| GET    | /swift-auth/users/create         | swift-auth.users.create   | Show user creation form |
| GET    | /swift-auth/users/register       | swift-auth.users.register | Show user register form |
| POST   | /swift-auth/users/create         | swift-auth.users.store    | Store new user          |
| GET    | /swift-auth/users/{id_user}      | swift-auth.users.show     | Show user details       |
| GET    | /swift-auth/users/{id_user}/edit | swift-auth.users.edit     | Show edit form          |
| PUT    | /swift-auth/users/{id_user}/edit | swift-auth.users.update   | Update user             |
| DELETE | /swift-auth/users/{id_user}      | swift-auth.users.destroy  | Delete user             |

---

## 🛡️ Role Management Routes

| Method | URL                         | Name                     | Description             |
| ------ | --------------------------- | ------------------------ | ----------------------- |
| GET    | /swift-auth/roles           | swift-auth.roles.index   | List roles              |
| GET    | /swift-auth/roles/create    | swift-auth.roles.create  | Show role creation form |
| POST   | /swift-auth/roles/create    | swift-auth.roles.store   | Store new role          |
| GET    | /swift-auth/roles/{id_role} | swift-auth.roles.edit    | Show edit form for role |
| PUT    | /swift-auth/roles/{id_role} | swift-auth.roles.update  | Update role             |
| DELETE | /swift-auth/roles/{id_role} | swift-auth.roles.destroy | Delete role             |

---

## 🔐 Middleware Applied

All sensitive routes are protected by:

-   `SwiftAuth.RequireAuthentication` - Ensures the user is authenticated.
-   `SwiftAuth.CanPerformAction:sw-admin` - Ensures the user has `sw-admin` action permission.

These middleware groups are applied to all `/users` and `/roles` route files.

---

### Usage

-   Access login page at `/swift-auth/login`
-   Protect your routes using `SwiftAuth.RequireAuthentication`
-   Assign `sw-admin` permission to users who need to manage users and roles
-   Customize views and controllers as needed by overriding published files

---

For detailed implementation and controller logic, review the source code.

### Contributing

If you would like to contribute to Swift Auth, please follow these steps:

1. Fork the repository.
2. Create a new branch for your changes.
3. Make your changes and ensure that the tests pass.
4. Submit a pull request.

### License

Swift Auth is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
