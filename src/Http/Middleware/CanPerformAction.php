<?php

/**
 * Verifies SwiftAuth permissions before executing privileged routes.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Middleware
 */

namespace Equidna\SwiftAuth\Http\Middleware;

use Illuminate\Http\Request;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Closure;

/**
 * Blocks access when the current SwiftAuth user lacks the requested action.
 */
class CanPerformAction
{
    /**
     * Handle an incoming request.
     *
     * Verifies if the authenticated user has permission to perform the given action.
     * If the user does not have the required permission, they are redirected back with an error message.
     *
     * @param  Request $request  Incoming HTTP request.
     * @param  Closure $next     Next middleware to handle the request.
     * @param  string  $action   Action the user is attempting to perform.
     * @return Response          Response after handling the request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $action,
    ): Response {
        if (!SwiftAuth::canPerformAction($action)) {
            return ResponseHelper::forbidden(message: 'You cannot perform this action.');
        }

        return $next($request);
    }
}
