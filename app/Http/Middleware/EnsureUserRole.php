<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $userRole = is_object($user?->role) ? $user->role->name : ($user?->roleName() ?? $user?->role);

        $isAuthorized = false;
        if ($user && $userRole) {
            if (in_array($userRole, $roles, true)) {
                $isAuthorized = true;
            } elseif ($userRole === 'lead_audit' && in_array('auditor', $roles, true)) {
                $isAuthorized = true;
            }
        }

        abort_unless($isAuthorized, 403);

        return $next($request);
    }
}
