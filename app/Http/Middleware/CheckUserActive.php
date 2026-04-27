<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       if (auth()->check() && !auth()->user()->active_state) {
        auth()->logout();
        return redirect()->route('filament.admin.auth.login')
            ->with('error', 'Tu cuenta ha sido desactivada.');
    }

    return $next($request);
    }
}
