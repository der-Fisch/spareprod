<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStaffToBackoffice
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_staff && ! $request->routeIs('backoffice.*')) {
            return redirect()->route('backoffice.dashboard');
        }

        return $next($request);
    }
}
