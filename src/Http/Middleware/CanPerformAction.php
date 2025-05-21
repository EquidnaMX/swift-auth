<?php

namespace Teleurban\SwiftAuth\Http\Middleware;

use Teleurban\SwiftAuth\Facades\SwiftAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

class CanPerformAction
{
    /**
     * Handle an incoming request.
     *
     * Verifies if the authenticated user has permission to perform the given action.
     * If the user does not have the required permission, they are redirected back with an error message.
     *
     * @param  Request  $request The incoming HTTP request.
     * @param  Closure  $next The next middleware to handle the request.
     * @param  string   $action The action the user is attempting to perform.
     * @return Response The response after handling the request.
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        if (!SwiftAuth::CanPerformAction($action)) {
            return back()->with('error', 'You cannot perform this action');
        }

        return $next($request);
    }
}
