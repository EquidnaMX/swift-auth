<?php

/**
 * Minimal PHPUnit base TestCase for unit tests.
 *
 * This file provides a lightweight base so tests can run outside a full
 * Laravel application. It intentionally avoids booting the framework.
 */

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure basic globals from tests/bootstrap are available.
        if (!function_exists('config')) {
            // bootstrap.php normally defines helpers; guard in case it's missing.
            require_once __DIR__ . '/bootstrap.php';
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
