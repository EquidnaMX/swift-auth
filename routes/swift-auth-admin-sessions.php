<?php

/**
 * Admin session management routes for SwiftAuth package.
 *
 * PHP 8.2+
 *
 * @package Equidna\SwiftAuth\Routes
 */

use Illuminate\Support\Facades\Route;
use Equidna\SwiftAuth\Http\Controllers\AdminSessionController;

Route::prefix('admin/sessions')
    ->as('admin.sessions.')
    ->group(
        function () {
            Route::get('', [AdminSessionController::class, 'all'])->name('all');
            Route::get('{userId}', [AdminSessionController::class, 'index'])->name('index');
            Route::delete('{userId}/{sessionId}', [AdminSessionController::class, 'destroy'])->name('destroy');
            Route::delete('{userId}', [AdminSessionController::class, 'destroyAll'])->name('destroy-all');
        }
    );
