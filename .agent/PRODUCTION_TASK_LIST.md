# Production Readiness Task List
**Generated:** 2025-12-12 09:45:36 CST
**Target:** Make `equidna/swift-auth` production-ready

---

## 🎯 Phase 1: Critical Database & Test Infrastructure (2-3 hours)

### Task 1.1: Configure Database Migrations in TestCase
**Priority:** CRITICAL | **Estimated Time:** 45 minutes | **Dependencies:** None

**Objective:** Enable database migrations to run during tests so models can interact with actual tables.

**Action Items:**
- [ ] Add `defineDatabaseMigrations()` method to `tests/TestCase.php`
- [ ] Load package migrations from `database/migrations`
- [ ] Ensure migrations run in correct order
- [ ] Add `RefreshDatabase` trait usage where needed

**Implementation:**
```php
// tests/TestCase.php
protected function defineDatabaseMigrations()
{
    $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
}

protected function defineEnvironment($app)
{
    $app['config']->set('database.default', 'testbench');
    $app['config']->set('database.connections.testbench', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
    ]);
}
```

**Acceptance Criteria:**
- [ ] User and Role tables exist during tests
- [ ] At least 10 previously failing tests now pass
- [ ] No migration errors in test output

---

### Task 1.2: Fix Model Relationship Tests
**Priority:** CRITICAL | **Estimated Time:** 30 minutes | **Dependencies:** 1.1

**Objective:** Ensure User/Role relationships work correctly.

**Action Items:**
- [ ] Fix `UserTest::test_has_roles_returns_true_when_user_has_role`
- [ ] Fix `UserTest::test_has_roles_is_case_insensitive`
- [ ] Fix `UserTest::test_available_actions_returns_unique_actions_from_all_roles`
- [ ] Fix `RoleTest::test_search_by_name_performs_case_insensitive_search` (SQL case issue)

**Known Issues:**
- Role relationships not loading (likely fixed by Task 1.1)
- SQL `LIKE` vs `like` case sensitivity on line 108 of `Role.php`

**Acceptance Criteria:**
- [ ] All 4 model test failures resolved
- [ ] User can load roles via relationship
- [ ] Role search is case-insensitive

---

### Task 1.3: Configure Test Database Seeders
**Priority:** HIGH | **Estimated Time:** 30 minutes | **Dependencies:** 1.1

**Objective:** Seed essential test data for feature tests.

**Action Items:**
- [ ] Create test seeder for default roles (if needed)
- [ ] Add helper methods in TestCase for creating test users with roles
- [ ] Ensure all feature tests have required data

**Implementation:**
```php
// tests/TestCase.php
protected function createTestUserWithRole(string $role): User
{
    $user = User::create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $roleModel = Role::firstOrCreate(['name' => $role]);
    $user->roles()->attach($roleModel);
    
    return $user;
}
```

**Acceptance Criteria:**
- [ ] Tests can easily create users with specific roles
- [ ] No "role not found" errors in tests

---

### Task 1.4: Run Full Test Suite After Database Fixes
**Priority:** CRITICAL | **Estimated Time:** 15 minutes | **Dependencies:** 1.1, 1.2, 1.3

**Action Items:**
- [ ] Run `vendor/bin/phpunit` and verify error count drops significantly
- [ ] Document any remaining failures
- [ ] Categorize remaining issues (database vs. logic vs. integration)

**Acceptance Criteria:**
- [ ] Error count < 20 (down from 80)
- [ ] Failure count < 5 (down from 4)
- [ ] All unit tests passing (100%)

---

## 🔧 Phase 2: Remaining Test Failures (1-2 hours)

### Task 2.1: Fix Feature Test Environment Issues
**Priority:** HIGH | **Estimated Time:** 45 minutes | **Dependencies:** 1.4

**Objective:** Ensure feature tests have all required services and facades.

**Action Items:**
- [ ] Review errors related to missing BirdFlock facade (expected, external package)
- [ ] Mock BirdFlock in tests or add package provider
- [ ] Fix SwiftAuth facade registration issues
- [ ] Ensure Cookie facade works in test environment

**Acceptance Criteria:**
- [ ] No "undefined facade" errors
- [ ] Feature tests can run without external package dependencies

---

### Task 2.2: Address Incomplete Tests
**Priority:** MEDIUM | **Estimated Time:** 30 minutes | **Dependencies:** 1.4

**Objective:** Complete or remove the 4 incomplete tests.

**Action Items:**
- [ ] Review each incomplete test
- [ ] Either complete implementation or mark as @skip with reason
- [ ] Document what functionality is missing (if any)

**Acceptance Criteria:**
- [ ] 0 incomplete tests
- [ ] All test logic is sound and complete

---

### Task 2.3: Fix Deprecation Warnings
**Priority:** LOW | **Estimated Time:** 15 minutes | **Dependencies:** None

**Objective:** Address the 1 deprecation warning.

**Action Items:**
- [ ] Identify source of deprecation
- [ ] Update to non-deprecated API
- [ ] Verify no new warnings introduced

**Acceptance Criteria:**
- [ ] 0 deprecation warnings

---

## 📊 Phase 3: Code Quality & Standards (1-2 hours)

### Task 3.1: Run Static Analysis (PHPStan)
**Priority:** HIGH | **Estimated Time:** 45 minutes | **Dependencies:** 1.4

**Action Items:**
- [ ] Run `vendor/bin/phpstan analyse`
- [ ] Fix type errors and undefined properties
- [ ] Address any critical security issues flagged
- [ ] Aim for level 5+ (current unknown)

**Acceptance Criteria:**
- [ ] PHPStan level 5 passes without errors
- [ ] All critical type issues resolved

---

### Task 3.2: Code Style Compliance
**Priority:** MEDIUM | **Estimated Time:** 30 minutes | **Dependencies:** None

**Action Items:**
- [ ] Run `vendor/bin/phpcs`
- [ ] Fix coding standard violations
- [ ] Ensure PSR-12 compliance
- [ ] Run `vendor/bin/phpcbf` for auto-fixes

**Acceptance Criteria:**
- [ ] 0 PHPCS errors
- [ ] Minimal warnings (< 5)

---

### Task 3.3: Documentation Review
**Priority:** MEDIUM | **Estimated Time:** 30 minutes | **Dependencies:** None

**Objective:** Ensure package is well-documented for production use.

**Action Items:**
- [ ] Update README.md with:
  - Installation instructions
  - Configuration examples
  - Migration commands
  - Usage examples
- [ ] Document required environment variables
- [ ] Add CHANGELOG.md entry for this release

**Acceptance Criteria:**
- [ ] README has complete setup guide
- [ ] All config options documented
- [ ] Breaking changes noted

---

## ✅ Phase 4: Final Validation (30 minutes)

### Task 4.1: Complete Test Suite Validation
**Priority:** CRITICAL | **Estimated Time:** 15 minutes | **Dependencies:** All previous

**Action Items:**
- [ ] Run full test suite: `vendor/bin/phpunit`
- [ ] Verify 100% passing tests
- [ ] Check test coverage report (if available)

**Acceptance Criteria:**
- [ ] 168 tests, 0 errors, 0 failures
- [ ] Test coverage > 70% (if measured)

---

### Task 4.2: Integration Smoke Test
**Priority:** HIGH | **Estimated Time:** 15 minutes | **Dependencies:** 4.1

**Action Items:**
- [ ] Install package in a test Laravel application
- [ ] Run migrations
- [ ] Test basic auth flow:
  - Register user
  - Login
  - Access protected route
  - Logout
- [ ] Test remember-me functionality
- [ ] Test MFA flow (if applicable)

**Acceptance Criteria:**
- [ ] Package installs without errors
- [ ] Basic auth flows work end-to-end
- [ ] No runtime errors in test app

---

## 📋 Summary

### Total Estimated Time: 5-8 hours

**Breakdown:**
- Phase 1 (Critical): 2-3 hours
- Phase 2 (Fixes): 1-2 hours  
- Phase 3 (Quality): 1-2 hours
- Phase 4 (Validation): 0.5 hours

### Success Metrics
- [ ] All 168 tests passing
- [ ] PHPStan level 5+ clean
- [ ] PHPCS compliant
- [ ] Documentation complete
- [ ] Successfully installs in test app

### Risk Areas
1. **External Dependencies:** BirdFlock facade might need special handling
2. **Database Migrations:** Complex migrations might need ordering fixes
3. **Feature Tests:** May require more Laravel app context than available in Orchestra

### Recommended Execution Order
1. Start with Phase 1 (database fixes) - biggest impact
2. Run tests after each task to measure progress
3. Move to Phase 2 only when error count < 20
4. Phase 3 can run in parallel with Phase 2
5. Phase 4 is final validation

---

## 🚀 Quick Start Command

To begin immediately:
```bash
# Run only unit tests that work now
vendor/bin/phpunit tests/Unit/TokenMetadataValidatorTest.php tests/Unit/Services/SwiftSessionAuthTest.php

# Expected: All passing
```

Then start with **Task 1.1** to unlock the remaining tests.
