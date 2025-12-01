<?php

/**
 * Ensures SwiftAuth authenticated sessions are present on protected routes.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Middleware
 */

namespace Equidna\SwiftAuth\Http\Middleware;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Closure;

/**
 * Redirects unauthenticated users or missing session records to the login form.
 */
class RequireAuthentication
{
    /**
     * Handle an incoming request.
     *
     * Verifies if the user is authenticated using SwiftAuth.
     * If the user is not authenticated, redirects to the login form with an error message.
     * If the authenticated user cannot be found, redirects to the login form with a different error message.
     *
     * @param  Request $request  Incoming HTTP request.
     * @param  Closure $next     Next middleware to handle the request.
     * @return Response          Response after handling the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!SwiftAuth::check()) {
            return ResponseHelper::unauthorized(
                message: 'You must be logged in.',
                forward_url: route('swift-auth.login.form'),
            );
        }

        try {
            $user = SwiftAuth::userOrFail();

            $request->attributes->add(['swift_auth_user' => $user]);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::unauthorized(
                message: 'Authenticated user not found on record.',
                forward_url: route('swift-auth.login.form'),
            );
        }

        return $next($request);
    }
}
