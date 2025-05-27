<?php

use Illuminate\Support\Facades\Route;
use Teleurban\SwiftAuth\Http\Controllers\RoleController;

Route::prefix('roles')
    ->as('roles.')
    ->group(
        function () {
            Route::get('', [RoleController::class, 'index'])->name('index');

            Route::get('create', [RoleController::class, 'create'])->name('create');
            Route::post('create', [RoleController::class, 'store'])->name('store');

            Route::prefix('{id_role}')->group(
                function () {
                    Route::get('', [RoleController::class, 'edit'])->name('edit');
                    Route::put('', [RoleController::class, 'update'])->name('update');
                    Route::delete('', [RoleController::class, 'destroy'])->name('destroy');
                }
            );
        }
    );
