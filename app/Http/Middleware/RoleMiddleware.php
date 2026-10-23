<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = (string) auth()->user()->role;
        $allowedRoles = array_map('strval', $roles);

        $company = auth()->user()->company;
        if ($company && !$company->is_active && $userRole !== 'super_admin') {
            abort(403, 'This company account is inactive. Contact your company administrator.');
        }

        // Super Admin inherits administrator access across the existing panel.
        if ($userRole === 'super_admin' && in_array('1', $allowedRoles, true)) {
            return $next($request);
        }

        if (!in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
