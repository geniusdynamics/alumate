<?php

namespace Tests\Integration;

use App\Models\LandingPage;
use Tests\TestCase;

/**
 * Cross-browser compatibility testing
 */
class CrossBrowserTest extends TestCase
{
    public function test_landing_pages_work_across_browsers(): void
    {
        $landingPage = LandingPage::factory()->create(['status' => 'published']);
        
        $browsers = [
            'Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Firefox' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Safari' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Edge' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59'
        ];

        foreach ($browsers as $browserName => $userAgent) {
            $response = $this->withHeaders([
                'User-Agent' => $userAgent
            ])->get("/landing/{$landingPage->slug}");

            $response->assertStatus(200);
            
            // Verify browser-specific analytics tracking
            $analytics = $landingPage->analytics()->latest()->first();
            $this->assertNotNull($analytics);
            $this->assertEquals($userAgent, $analytics->user_agent);
        }
    }
}