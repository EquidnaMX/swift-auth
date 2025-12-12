<?php

/**
 * Minimal PHPUnit base TestCase for unit tests.
 *
 * This file provides a lightweight base so tests can run outside a full
 * Laravel application. It intentionally avoids booting the framework.
 */

namespace Equidna\SwiftAuth\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Equidna\SwiftAuth\Providers\SwiftAuthServiceProvider;

class TestCase extends OrchestraTestCase
{
    use TestHelpers;
    protected function setUp(): void
    {
        parent::setUp();

        // Mock BirdFlock facade if it exists
        if (class_exists(\Equidna\BirdFlock\Facades\BirdFlock::class)) {
            \Equidna\BirdFlock\Facades\BirdFlock::fake();
        }
    }

    protected function tearDown(): void
    {
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        $providers = [
            SwiftAuthServiceProvider::class,
        ];

        // Add BirdFlock provider if available
        if (class_exists(\Equidna\BirdFlock\BirdFlockServiceProvider::class)) {
            $providers[] = \Equidna\BirdFlock\BirdFlockServiceProvider::class;
        }

        return $providers;
    }

    protected function defineEnvironment($app)
    {
        // Database configuration
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // App configuration for feature tests
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('app.debug', true);

        // Configure swift-auth settings for tests
        $app['config']->set('swift-auth.remember_me.policy', 'strict');
        $app['config']->set('swift-auth.session_limits.max_sessions', 5);
        $app['config']->set('swift-auth.table_prefix', '');
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
