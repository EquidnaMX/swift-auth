<?php

/**
 * Role routes for SwiftAuth package.
 *
 * PHP 8.1+
 *
 * @package Equidna\SwifthAuth\Routes
 */

use Illuminate\Support\Facades\Route;
use Equidna\SwifthAuth\Http\Controllers\RoleController;

Route::prefix('roles')
    ->as('roles.')
    ->group(
        function () {
            Route::get('', [RoleController::class, 'index'])->name('index');
            Route::post('', [RoleController::class, 'store'])->name('store');

            Route::get('create', [RoleController::class, 'create'])->name('create');

            Route::prefix('{id_role}')->group(
                function () {
                    Route::get('', [RoleController::class, 'edit'])->name('edit');
                    Route::put('', [RoleController::class, 'update'])->name('update');
                    Route::delete('', [RoleController::class, 'destroy'])->name('destroy');
                }
            );
        }
    );
