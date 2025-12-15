# Tests Documentation

SwiftAuth uses **PHPUnit** for testing.

## Running Tests

Run the full test suite via Composer script or PHPUnit directly:

```bash
# Via Composer (recommended)
composer test

# Direct PHPUnit
vendor/bin/phpunit
```

## Structure

Tests are located in the `tests/` directory:

- `tests/Unit/`: Isolated tests for Services, DTOs, and Helpers.
- `tests/Feature/`: HTTP integration tests (Controllers, Middleware routes).
- `tests/Stubs/`: Mock objects and test doubles.

## Key Test Classes

- `Tests\TestCase`: Base class that sets up the **Orchestra Testbench** environment, loads the service provider, and runs migrations for the test database (sqlite :memory:).
- `Tests\TestHelpers`: Trait containing factories and auth helpers.

## Writing New Tests

1.  **Placement:**
    - Logic/Class tests → `tests/Unit`
    - Controller/Route tests → `tests/Feature`

2.  **Naming:**
    - Class: `VerificationServiceTest` (Suffix `Test`)
    - Method: `test_it_does_something` or `it_does_something` (using `/** @test */` annotation if preferred, though `test_` prefix is standard in this repo).

3.  **Database:**
    - Tests run on an in-memory SQLite database.
    - Traits `RefreshDatabase` or `DatabaseMigrations` are used automatically via `TestCase`.

## Coverage

- **Core Auth:** Login, Register, Logout flow.
- **Services:** NotificationService, RateLimit checks.
- **Middleware:** Security headers, Auth requirements.
