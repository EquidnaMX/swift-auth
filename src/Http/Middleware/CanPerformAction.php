<?php

namespace Teleurban\SwiftAuth\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

class CanPerformAction
{
    public function handle(Request $request, Closure $next, string $action): Response
    {

        $user = $request->attributes->get('sw-user');

        if (!in_array($action, $user->availableActions())) {
            return back()->with('error', 'You can not perform this action');
        }

        return $next($request);
    }
}
