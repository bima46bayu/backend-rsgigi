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
        // Bypass cPanel/Nginx stripping Authorization header
        if ($request->hasHeader('X-Authorization')) {
            $request->headers->set('Authorization', $request->header('X-Authorization'));
        }

        return $next($request);
    }
}
