<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestoreAuthorizationHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Log headers for debugging
        \Illuminate\Support\Facades\Log::info('Incoming Request Headers:', [
            'origin' => $request->header('origin'),
            'authorization' => $request->header('authorization'),
            'x-authorization' => $request->header('x-authorization'),
            'ip' => $request->ip(),
        ]);

        // Bypass cPanel/Nginx stripping Authorization header via custom header
        if ($request->hasHeader('X-Authorization')) {
            $request->headers->set('Authorization', $request->header('X-Authorization'));
        }

        // Ultimate fallback: Bypass via Query Parameter (e.g. ?token=...)
        if ($request->has('token') && !str_starts_with($request->path(), 'login')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->query('token'));
        }

        return $next($request);
    }
}
