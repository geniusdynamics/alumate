<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $config = config('deployment.homepage.security_headers');

        // HTTP Strict Transport Security (HSTS)
        if ($config['hsts']['enabled'] && $request->secure()) {
            $hsts = 'max-age='.$config['hsts']['max_age'];

            if ($config['hsts']['include_subdomains']) {
                $hsts .= '; includeSubDomains';
            }

            if ($config['hsts']['preload']) {
                $hsts .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        // Content Security Policy
        if ($config['csp']['enabled']) {
            $response->headers->set('Content-Security-Policy', $config['csp']['policy']);
        }

        // X-Frame-Options
        $response->headers->set('X-Frame-Options', $config['x_frame_options']);

        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', $config['x_content_type_options']);

        // X-XSS-Protection
        $response->headers->set('X-XSS-Protection', $config['x_xss_protection']);

        // Referrer Policy
        $response->headers->set('Referrer-Policy', $config['referrer_policy']);

        // Additional security headers
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('X-Download-Options', 'noopen');

        return $response;
    }
}
