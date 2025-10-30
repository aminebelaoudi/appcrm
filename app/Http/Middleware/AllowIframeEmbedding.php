<?php

namespace App\Http\Middleware;

use Closure;

class AllowIframeEmbedding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Permettre l'intégration dans un iframe depuis n'importe quel domaine
        $response->headers->set('X-Frame-Options', 'ALLOWALL');
        $response->headers->set('Content-Security-Policy', "frame-ancestors *");
        
        // Permettre les requêtes CORS
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        
        return $response;
    }
}
