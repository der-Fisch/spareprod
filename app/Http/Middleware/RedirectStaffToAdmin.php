<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStaffToAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_staff && ! $request->routeIs('admin.*')) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
