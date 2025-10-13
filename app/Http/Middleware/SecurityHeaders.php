<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        $csp = config('security.csp');
        if (! empty($csp)) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        $referrerPolicy = config('security.referrer_policy');
        if (! empty($referrerPolicy)) {
            $response->headers->set('Referrer-Policy', $referrerPolicy);
        }

        $permissionsPolicy = config('security.permissions_policy');
        if (! empty($permissionsPolicy)) {
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '0');

        $environments = config('security.enable_hsts_environments', []);
        if (in_array(app()->environment(), $environments, true) && $request->isSecure()) {
            $maxAge = (int) config('security.hsts_max_age', 63072000);
            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}; includeSubDomains; preload");
        }

        return $response;
    }
}
