<?php

namespace Teleurban\SwiftAuth\Http\Middleware;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Teleurban\SwiftAuth\Models\User;
use Illuminate\Http\Request;
use Closure;

class RequireAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()
                ->route('swift-auth.login.form')
                ->with('error', 'You must be logged in.');
        }

        try {
            $user = User::findOrFail(Auth::id());

            $request->attributes->add(
                [
                    'sw-user' => $user
                ]
            );
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('swift-auth.login.form')
                ->with('error', 'Authenticated user not found on record');
        }

        return $next($request);
    }
}
