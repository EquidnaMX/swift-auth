<?php

namespace Teleurban\SwiftAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Teleurban\SwiftAuth\Console\Commands\InstallSwiftAuth;
use Teleurban\SwiftAuth\Http\Middleware\RequireAuthentication;
use Teleurban\SwiftAuth\Http\Middleware\CanPerformAction;
use Teleurban\SwiftAuth\Services\SwiftSessionAuth;

/**
 * SwiftAuthServiceProvider
 *
 * Registers and bootstraps SwiftAuth components: middleware, views, migrations,
 * config, commands, and publishes related assets.
 */
final class SwiftAuthServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * - Merges the package configuration.
     * - Binds the 'swift-auth' service as a singleton.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/swift-auth.php', 'swift-auth');

        $this->app->singleton('swift-auth', function ($app) {
            return new SwiftSessionAuth($app['session.store']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @param Router $router
     * @return void
     */
    public function boot(Router $router): void
    {
        // Register middleware
        $router->aliasMiddleware('SwiftAuth.RequireAuthentication', RequireAuthentication::class);
        $router->aliasMiddleware('SwifthAuth.CanPerformAction', CanPerformAction::class);

        // Load package resources
        $this->loadRoutesFrom(__DIR__ . '/../routes/swift-auth.php');
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'swift-auth');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/swift-auth.php' => config_path('swift-auth.php'),
        ], 'swift-auth:config');

        // Publish application models
        $this->publishes([
            __DIR__ . '/../Models' => app_path('Models'),
        ], 'swift-auth:models');

        // Publish Blade views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views'),
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
                InstallSwiftAuth::class,
            ]);
        }
    }
}
