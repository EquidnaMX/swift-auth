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
use Symfony\Component\HttpFoundation\Response;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Closure;

/**
 * Redirects unauthenticated users or missing session records to the login form.
 */
class RequireAuthentication
{
    /**
     * Handles an incoming request.
     *
     * Verifies if the user is authenticated using SwiftAuth. If not authenticated, redirects to
     * the login form. If authenticated user cannot be found, also redirects to login.
     *
     * @throws ModelNotFoundException  When authenticated user ID exists but user record not found.
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        if (!SwiftAuth::check()) {
            return $this->unauthorized(
                message: 'You must be logged in.',
            );
        }

        try {
            $user = SwiftAuth::userOrFail();

            $request->attributes->add(['swift_auth_user' => $user]);
        } catch (ModelNotFoundException $e) {
            return $this->unauthorized(
                message: 'Authenticated user not found on record.',
            );
        }

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        $loginUrl = route('swift-auth.login.form');
        $response = ResponseHelper::unauthorized(message: $message, forward_url: $loginUrl);

        return $response instanceof Response ? $response : redirect()->to($loginUrl);
    }
}
