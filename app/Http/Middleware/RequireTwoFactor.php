<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->two_factor_enabled) {
            if (!$request->session()->has('auth.two_factor_verified')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Two factor authentication required.'], 403);
                }
                
                return redirect()->route('two-factor.challenge');
            }
        }

        // For roles that MUST have 2FA enabled (enforcement policy)
        if ($user && ($user->hasRole('super-admin') || $user->hasRole('institution-admin')) && !$user->two_factor_enabled) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Administrators must enable two-factor authentication.'], 403);
            }
            
            return redirect()->route('profile.edit')->with('status', 'Please enable Two-Factor Authentication to continue.');
        }

        return $next($request);
    }
}