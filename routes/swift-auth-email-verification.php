<?php

/**
 * Email verification routes for SwiftAuth.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Routes
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

use Illuminate\Support\Facades\Route;
use Equidna\SwiftAuth\Http\Controllers\EmailVerificationController;

$prefix = (string) config('swift-auth.route_prefix', 'swift-auth');
$namePrefix = (string) config('swift-auth.route_prefix', 'swift-auth');

Route::middleware(['web', 'SwiftAuth.SecurityHeaders'])
    ->prefix($prefix)
    ->name($namePrefix . '.')
    ->group(function () {
        Route::post('/email/send', [EmailVerificationController::class, 'send'])
            ->name('email.send');

        Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])
            ->name('email.verify');
    });
