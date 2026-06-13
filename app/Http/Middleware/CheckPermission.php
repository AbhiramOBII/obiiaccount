<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        [$module, $action] = explode('.', $permission, 2);

        if (!auth()->check() || !auth()->user()->hasPermission($module, $action)) {
            abort(403, "You don't have permission to perform this action.");
        }

        return $next($request);
    }
}
