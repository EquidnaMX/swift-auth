<?php

use Illuminate\Support\Facades\Route;
use Teleurban\SwiftAuth\Http\Controllers\UserController;

Route::prefix('users')
    ->as('users.')
    ->group(
        function () {
            Route::get('', [UserController::class, 'index'])->name('index');

            Route::get('create', [UserController::class, 'create'])->name('create');
            Route::get('register', [UserController::class, 'register'])->name('register');
            Route::post('create', [UserController::class, 'store'])->name('store');

            Route::prefix('{id_user}')->group(
                function () {
                    Route::get('', [UserController::class, 'show'])->name('show');
                    Route::get('edit', [UserController::class, 'edit'])->name('edit');
                    Route::put('edit', [UserController::class, 'update'])->name('update');
                    Route::delete('', [UserController::class, 'destroy'])->name('destroy');
                }
            );
        }
    );
