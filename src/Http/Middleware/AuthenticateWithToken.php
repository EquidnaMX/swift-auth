<?php

/**
 * Middleware to authenticate requests via API tokens.
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
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates API requests using Bearer tokens.
 *
 * Validates tokens from the Authorization header, checks expiration,
 * and attaches the authenticated user to the request.
 */
class AuthenticateWithToken
{
    /**
     * Creates middleware instance with token service.
     *
     * @param UserTokenService $tokenService  Token validation service.
     */
    public function __construct(
        private UserTokenService $tokenService
    ) {
        //
    }

    /**
     * Handles an incoming request.
     *
     * @param  Request  $request  HTTP request.
     * @param  Closure  $next     Next middleware.
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return response()->json(
                data: ['message' => 'Unauthenticated. Token required.'],
                status: 401,
            );
        }

        $userToken = $this->tokenService->validateToken($token);

        if ($userToken === null) {
            return response()->json(
                data: ['message' => 'Invalid or expired token.'],
                status: 401,
            );
        }

        // Attach user to request
        $request->setUserResolver(fn() => $userToken->user);

        // Store token for ability checks
        $request->attributes->set('user_token', $userToken);

        // Update last used timestamp
        $userToken->markAsUsed();

        return $next($request);
    }

    /**
     * Extracts the bearer token from the Authorization header.
     *
     * @param  Request      $request  HTTP request.
     * @return string|null            Plain token or null if not found.
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if ($header === null || !is_string($header)) {
            return null;
        }

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(substr($header, 7));
    }
}
