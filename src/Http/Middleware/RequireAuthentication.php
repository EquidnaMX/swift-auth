<?php

namespace Teleurban\SwiftAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Teleurban\SwiftAuth\Facades\SwiftAuth;

class RequireAuthentication
{
    /**
     * Handle an incoming request.
     *
     * Verifies if the user is authenticated using SwiftAuth.
     * If the user is not authenticated, redirects to the login form with an error message.
     * If the authenticated user cannot be found, redirects to the login form with a different error message.
     *
     * @param  Request  $request The incoming HTTP request.
     * @param  Closure  $next The next middleware to handle the request.
     * @return Response The response after handling the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!SwiftAuth::check()) {
            return redirect()
                ->route('swift-auth.login.form')
                ->with('error', 'You must be logged in.');
        }

        try {
            $user = SwiftAuth::userOrFail();

            $request->attributes->add(['sw-user' => $user]);
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('swift-auth.login.form')
                ->with('error', 'Authenticated user not found on record');
        }

        return $next($request);
    }
}
