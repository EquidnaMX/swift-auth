<?php

/**
 * Session management routes for SwiftAuth package.
 *
 * PHP 8.2+
 *
 * @package Equidna\SwiftAuth\Routes
 */

use Illuminate\Support\Facades\Route;
use Equidna\SwiftAuth\Http\Controllers\SessionController;

Route::prefix('sessions')
    ->as('sessions.')
    ->group(
        function () {
            Route::get('', [SessionController::class, 'index'])->name('index');
            Route::delete('{sessionId}', [SessionController::class, 'destroy'])->name('destroy');
        }
    );
