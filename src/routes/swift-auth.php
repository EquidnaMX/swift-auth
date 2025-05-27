<?php

use Illuminate\Support\Facades\Route;
use Teleurban\SwiftAuth\Http\Controllers\AuthController;
use Teleurban\SwiftAuth\Http\Middleware\CanPerformAction;
use Teleurban\SwiftAuth\Http\Middleware\RequireAuthentication;

Route::middleware('web')
    ->prefix('swift-auth')
    ->as('swift-auth.')
    ->group(
        function () {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login.form');
            Route::post('login', [AuthController::class, 'login'])->name('login');

            Route::match(['get', 'post'], 'logout', [AuthController::class, 'logout'])->name('logout');

            Route::prefix('password')
                ->as('password.')->group(
                    function () {
                        Route::get('reset', [AuthController::class, 'showResetForm'])->name('request');
                        Route::post('email', [AuthController::class, 'sendResetLink'])->name('email');

                        Route::get('reset/{token}', [AuthController::class, 'showNewPasswordForm'])->name('reset');
                        Route::post('reset', [AuthController::class, 'updatePassword'])->name('update');
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
