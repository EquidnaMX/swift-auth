<?php

/**
 * Middleware to check API token abilities/scopes.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Middleware
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Equidna\SwiftAuth\Models\UserToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks if the authenticated token has required abilities.
 *
 * Use after AuthenticateWithToken middleware to enforce scope-based permissions.
 */
class CheckTokenAbilities
{
    /**
     * Handles an incoming request.
     *
     * @param  Request       $request    HTTP request.
     * @param  Closure       $next       Next middleware.
     * @param  string        ...$abilities  Required abilities.
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                data: ['message' => 'Unauthenticated.'],
                status: 401,
            );
        }

        // Get the token from request attributes (set by AuthenticateWithToken)
        $token = $request->attributes->get('user_token');

        if ($token === null || !($token instanceof UserToken)) {
            return response()->json(
                data: ['message' => 'Token authentication required.'],
                status: 401,
            );
        }

        foreach ($abilities as $ability) {
            if (!$token->can($ability)) {
                return response()->json(
                    data: [
                        'message' => 'Insufficient permissions.',
                        'required' => $abilities,
                    ],
                    status: 403,
                );
            }
        }

        return $next($request);
    }
}
