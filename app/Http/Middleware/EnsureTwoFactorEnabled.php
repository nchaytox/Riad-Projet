<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->shouldPromptForTwoFactorSetup()) {
            if (! $request->routeIs('two-factor.*')) {
                return redirect()
                    ->route('two-factor.manage')
                    ->with('warning', 'Two-factor authentication is required for your role. Please complete the setup.');
            }
        }

        return $next($request);
    }
}
