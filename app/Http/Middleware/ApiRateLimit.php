<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     * Limite: 30 requêtes par minute par IP
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        $key = 'rate_limit:' . $ip;
        $maxAttempts = 30; // 30 requêtes
        $decayMinutes = 1; // par minute

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez réessayer dans une minute.'
            ], 429);
        }

        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        return $next($request);
    }
}
