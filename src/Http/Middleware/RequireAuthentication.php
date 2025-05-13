<?php

namespace Teleurban\SwiftAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Teleurban\SwiftAuth\Facades\SwiftAuth;

class RequireAuthentication
{
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
