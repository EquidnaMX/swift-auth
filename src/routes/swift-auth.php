<?php

/**
 * Routes for SwiftAuth package.
 *
 * PHP 8.1+
 *
 * @package Equidna\SwiftAuth\Routes
 */

use Illuminate\Support\Facades\Route;
use Equidna\SwiftAuth\Http\Controllers\AuthController;
use Equidna\SwiftAuth\Http\Controllers\PasswordController;
use Equidna\SwiftAuth\Http\Middleware\CanPerformAction;
use Equidna\SwiftAuth\Http\Middleware\RequireAuthentication;
use Equidna\SwiftAuth\Http\Controllers\MfaController;
use Equidna\SwiftAuth\Http\Controllers\UserController;

$routePrefix = (string) config('swift-auth.route_prefix', 'swift-auth');

Route::middleware(['web', 'SwiftAuth.SecurityHeaders'])
    ->prefix($routePrefix)
    ->as($routePrefix . '.')
    ->group(
        function () {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login.form');
            Route::post('login', [AuthController::class, 'login'])->name('login');

            Route::prefix('mfa')->as('mfa.')
                ->group(
                    function () {
                        Route::post('otp/verify', [MfaController::class, 'verifyOtp'])->name('otp.verify');
                        Route::post('webauthn/verify', [MfaController::class, 'verifyWebAuthn'])
                            ->name('webauthn.verify');
                    }
                );

            Route::match(['get', 'post'], 'logout', [AuthController::class, 'logout'])->name('logout');

            // Public registration routes (optional)
            if (config('swift-auth.allow_registration', true)) {
                Route::get('users/register', [UserController::class, 'register'])
                    ->name('public.register');

                Route::post('users', [UserController::class, 'store'])
                    ->middleware('throttle:swift-auth-registration')
                    ->name('public.register.store');
            }

            Route::prefix('password')->as('password.')
                ->group(
                    function () {
                        Route::get('', [PasswordController::class, 'showRequestForm'])->name('request.form');
                        Route::post('', [PasswordController::class, 'sendResetLink'])
                            ->middleware('throttle:swift-auth-password-reset')
                            ->name('request.send');

                        Route::get('sent', [PasswordController::class, 'showRequestSent'])->name('request.sent');
                        Route::get('{token}', [PasswordController::class, 'showResetForm'])->name('reset.form');
                        Route::post('reset', [PasswordController::class, 'resetPassword'])->name('reset.update');
                    }
                );

            Route::middleware([
                RequireAuthentication::class,
            ])->group(
                function () {
                    require __DIR__ . '/swift-auth-sessions.php';

                    Route::middleware(CanPerformAction::class . ':sw-admin')
                        ->group(
                            function () {
                                require __DIR__ . '/swift-auth-users.php';
                                require __DIR__ . '/swift-auth-roles.php';
                                require __DIR__ . '/swift-auth-admin-sessions.php';
                            }
                        );
                }
            );
        }
    );
