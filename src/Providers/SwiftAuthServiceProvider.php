<?php

/**
 * Service provider that registers and bootstraps SwiftAuth components.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Providers
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

use Equidna\SwiftAuth\Console\Commands\CreateAdminUser;
use Equidna\SwiftAuth\Console\Commands\InstallSwiftAuth;
use Equidna\SwiftAuth\Console\Commands\ListSessions;
use Equidna\SwiftAuth\Console\Commands\PreviewEmailTemplates;
use Equidna\SwiftAuth\Console\Commands\PurgeExpiredTokens;
use Equidna\SwiftAuth\Console\Commands\PurgeStaleSessions;
use Equidna\SwiftAuth\Console\Commands\RevokeUserSessions;
use Equidna\SwiftAuth\Console\Commands\UnlockUserCommand;
use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Http\Middleware\CanPerformAction;
use Equidna\SwiftAuth\Http\Middleware\RequireAuthentication;
use Equidna\SwiftAuth\Http\Middleware\SecurityHeaders;
use Equidna\SwiftAuth\Providers\RateLimitServiceProvider;
use Equidna\SwiftAuth\Repositories\EloquentUserRepository;
use Equidna\SwiftAuth\Services\SwiftSessionAuth;

/**
 * Registers and bootstraps SwiftAuth components.
 *
 * Handles middleware registration, view/migration loading, config merging, command registration,
 * and publishes related assets.
 */
final class SwiftAuthServiceProvider extends ServiceProvider
{
    /**
     * Registers bindings in the container.
     *
     * Merges the package configuration and binds the 'swift-auth' service as a singleton.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/swift-auth.php', 'swift-auth');

        // Bind user repository interface
        $this->app->singleton(UserRepositoryInterface::class, EloquentUserRepository::class);

        // Bind SwiftAuth service
        $this->app->singleton('swift-auth', function ($app) {
            return new SwiftSessionAuth(
                $app['session.store'],
                $app->make(UserRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstraps any application services.
     *
     * Registers middleware aliases, loads routes/views/migrations, and publishes assets.
     * Validates configuration on boot to catch misconfigurations early.
     *
     * @param  Router $router  Laravel router instance.
     * @return void
     */
    public function boot(Router $router): void
    {
        $this->validateConfiguration();

        // Register rate limiting
        $this->app->register(RateLimitServiceProvider::class);

        // Register middleware
        $router->aliasMiddleware('SwiftAuth.RequireAuthentication', RequireAuthentication::class);
        $router->aliasMiddleware('SwiftAuth.CanPerformAction', CanPerformAction::class);
        $router->aliasMiddleware('SwiftAuth.SecurityHeaders', SecurityHeaders::class);

        // Load package resources
        $this->loadRoutesFrom(__DIR__ . '/../routes/swift-auth.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/swift-auth-email-verification.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'swift-auth');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/swift-auth.php' => config_path('swift-auth.php'),
        ], 'swift-auth:config');

        // Publish application models
        $this->publishes([
            __DIR__ . '/../Models' => app_path('Models'),
        ], 'swift-auth:models');

        // Publish Blade views (into a vendor subfolder to avoid overwriting app views)
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/swift-auth'),
        ], 'swift-auth:views');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'swift-auth:migrations');

        // Publish React + TypeScript views
        $this->publishes([
            __DIR__ . '/../resources/ts' => resource_path('js'),
        ], 'swift-auth:ts-react');

        // Publish React + JavaScript views
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js'),
        ], 'swift-auth:js-react');

        // Publish icons
        $this->publishes([
            __DIR__ . '/../resources/icons' => public_path('icons'),
        ], 'swift-auth:icons');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateAdminUser::class,
                InstallSwiftAuth::class,
                ListSessions::class,
                PreviewEmailTemplates::class,
                PurgeExpiredTokens::class,
                PurgeStaleSessions::class,
                RevokeUserSessions::class,
                UnlockUserCommand::class,
            ]);

            $this->callAfterResolving(
                \Illuminate\Console\Scheduling\Schedule::class,
                function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
                    $schedule->command(PurgeExpiredTokens::class)->hourly();

                    if (config('swift-auth.session_cleanup.enabled', true)) {
                        $frequency = (string) config('swift-auth.session_cleanup.schedule', 'daily');

                        $event = $schedule->command(PurgeStaleSessions::class);

                        if (method_exists($event, $frequency)) {
                            $event->{$frequency}();
                        } else {
                            $event->cron($frequency);
                        }
                    }
                }
            );
        }
    }

    /**
     * Validates swift-auth configuration and logs warnings for invalid values.
     *
     * @return void
     */
    private function validateConfiguration(): void
    {
        $maxAttempts = config('swift-auth.account_lockout.max_attempts', 5);
        $lockoutDuration = config('swift-auth.account_lockout.lockout_duration', 900);
        $passwordMinLength = config('swift-auth.password_min_length', 8);

        if ($maxAttempts < 1) {
            logger()->warning('swift-auth.config.invalid-max-attempts', [
                'value' => $maxAttempts,
                'default' => 5,
            ]);
        }

        if ($lockoutDuration < 60) {
            logger()->warning('swift-auth.config.invalid-lockout-duration', [
                'value' => $lockoutDuration,
                'minimum' => 60,
                'default' => 900,
            ]);
        }

        if ($passwordMinLength < 8 || $passwordMinLength > 128) {
            logger()->warning('swift-auth.config.invalid-password-min-length', [
                'value' => $passwordMinLength,
                'default' => 8,
            ]);
        }

        $hashDriver = config('swift-auth.hash_driver');
        if ($hashDriver !== null && !in_array($hashDriver, ['bcrypt', 'argon', 'argon2id'], true)) {
            logger()->warning('swift-auth.config.invalid-hash-driver', [
                'value' => $hashDriver,
                'supported' => ['bcrypt', 'argon', 'argon2id', 'null (default)'],
            ]);
        }
    }
}
