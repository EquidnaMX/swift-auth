<?php

use Illuminate\Support\Facades\Route;
use Teleurban\SwiftAuth\Http\Controllers\UserController;

Route::prefix('users')
    ->as('users.')
    ->group(
        function () {
            Route::get('', [UserController::class, 'index'])->name('index');
            Route::post('', [UserController::class, 'store'])->name('store');

            Route::get('register', [UserController::class, 'register'])->name('register');

            Route::prefix('{id_user}')->group(
                function () {
                    Route::get('', [UserController::class, 'show'])->name('show');

                    Route::put('', [UserController::class, 'update'])->name('update');
                    Route::delete('', [UserController::class, 'destroy'])->name('destroy');
                }
            );
        }
    );
