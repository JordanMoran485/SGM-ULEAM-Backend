<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
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
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (method_exists($user, 'getSystemAccessDenialMessage')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                if (! $user->active_state) {
                    $this->disconnect($request);
                    return response()->json([
                        'message' => 'Tu cuenta se encuentra desactivada. Contacta al administrador.',
                    ], 403);
                }

                return $next($request);
            }

            $message = $user->getSystemAccessDenialMessage();

            if (! $message) {
                return $next($request);
            }

            $this->disconnect($request);

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            Notification::make()
                ->title($message)
                ->danger()
                ->send();

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('error', $message);
        }

        return $next($request);
    }

    protected function disconnect(Request $request): void
    {
        if ($request->user()?->currentAccessToken()) {
            $request->user()->tokens()->delete();
        }

        if ($request->hasSession()) {
            auth()->logout();
            $request->session()?->invalidate();
            $request->session()?->regenerateToken();
        }
    }
}
