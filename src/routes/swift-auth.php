<?php

/**
 * Routes for SwiftAuth package.
 *
 * PHP 8.1+
 *
 * @package Equidna\SwiftAuth\Routes
 */

use Equidna\SwiftAuth\Http\Middleware\RequireAuthentication;
use Equidna\SwiftAuth\Http\Controllers\PasswordController;
use Equidna\SwiftAuth\Http\Middleware\CanPerformAction;
use Equidna\SwiftAuth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

$routePrefix = config('swift-auth.route_prefix', 'swift-auth');

Route::middleware('web')
    ->prefix($routePrefix)
    ->as($routePrefix . '.')
    ->group(
        function () {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login.form');
            Route::post('login', [AuthController::class, 'login'])->name('login');

            Route::match(['get', 'post'], 'logout', [AuthController::class, 'logout'])->name('logout');

            Route::prefix('password')->as('password.')
                ->group(
                    function () {
                        Route::get('', [PasswordController::class, 'showRequestForm'])->name('request.form');
                        Route::post('', [PasswordController::class, 'sendResetLink'])->name('request.send');

                        Route::get('sent', [PasswordController::class, 'showRequestSent'])->name('request.sent');
                        Route::get('{token}', [PasswordController::class, 'showResetForm'])->name('reset.form');
                        Route::post('reset', [PasswordController::class, 'resetPassword'])->name('reset.update');
                    }
                );

            Route::middleware(
                [
                    RequireAuthentication::class,
                    CanPerformAction::class . ':sw-admin'
                ]
            )->group(
                function () {
                    require __DIR__ . '/swift-auth-users.php';
                    require __DIR__ . '/swift-auth-roles.php';
                }
            );
        }
    );
