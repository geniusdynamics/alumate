<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\EmailAnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Track Email Opens Middleware
 *
 * Middleware for handling email open tracking via transparent pixel.
 * Returns a 1x1 transparent GIF image and records the open event.
 */
class TrackEmailOpens
{
    /**
     * Email analytics service instance
     */
    protected EmailAnalyticsService $analyticsService;

    /**
     * Create a new middleware instance
     */
    public function __construct(EmailAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Handle an incoming request for email open tracking
     */
    public function handle(Request $request, Closure $next): Response
    {
        $trackingId = $request->route('trackingId');

        if (! $trackingId) {
            Log::warning('Email tracking request without tracking ID', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->getPixelResponse();
        }

        try {
            // Collect metadata for tracking
            $metadata = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'timestamp' => now()->toIso8601String(),
            ];

            // Track the open event
            $result = $this->analyticsService->trackOpen($trackingId, $metadata);

            if ($result['success']) {
                Log::debug('Email open tracked successfully', [
                    'tracking_id' => $trackingId,
                    'ip' => $request->ip(),
                ]);
            } else {
                Log::warning('Email open tracking failed', [
                    'tracking_id' => $trackingId,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Email open tracking exception', [
                'tracking_id' => $trackingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return the pixel, regardless of tracking success
        return $this->getPixelResponse();
    }

    /**
     * Get the transparent pixel response
     */
    protected function getPixelResponse(): Response
    {
        // 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Content-Length', strlen($pixel))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
