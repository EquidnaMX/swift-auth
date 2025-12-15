<?php

/**
 * User routes for SwiftAuth package.
 *
 * PHP 8.1+
 *
 * @package Equidna\SwiftAuth\Routes
 */

use Illuminate\Support\Facades\Route;
use Equidna\SwiftAuth\Http\Controllers\UserController;

Route::prefix('users')
    ->as('users.')
    ->group(
        function () {
            Route::get('', [UserController::class, 'index'])->name('index');

            // Admin create page
            Route::get('create', [UserController::class, 'create'])->name('create');


            Route::prefix('{id_user}')->group(
                function () {
                    Route::get('', [UserController::class, 'show'])->name('show');

                    // Admin edit page
                    Route::get('edit', [UserController::class, 'edit'])->name('edit');

                    Route::put('', [UserController::class, 'update'])->name('update');
                    Route::delete('', [UserController::class, 'destroy'])->name('destroy');
                }
            );
        }
    );
