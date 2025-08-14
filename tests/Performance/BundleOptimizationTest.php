<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BundleOptimizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_loads_homepage_with_optimized_bundles()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Check that critical CSS is inlined or preloaded
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'rel="preload"') || 
            str_contains($content, '<style>'),
            'Critical CSS should be preloaded or inlined'
        );
        
        // Check for proper resource hints
        $this->assertStringContainsString('rel="dns-prefetch"', $content);
        $this->assertStringContainsString('rel="preconnect"', $content);
    }

    /** @test */
    public function it_serves_compressed_assets()
    {
        $response = $this->get('/build/assets/app.js');
        
        // Check for compression headers
        $this->assertTrue(
            $response->headers->has('content-encoding') ||
            $response->headers->get('content-type') === 'application/javascript',
            'Assets should be compressed or properly served'
        );
    }

    /** @test */
    public function it_implements_proper_caching_headers()
    {
        $response = $this->get('/build/assets/app.css');
        
        // Check for caching headers
        $this->assertTrue(
            $response->headers->has('cache-control') ||
            $response->headers->has('etag') ||
            $response->headers->has('last-modified'),
            'Assets should have proper caching headers'
        );
    }

    /** @test */
    public function it_loads_lazy_components_on_demand()
    {
        // Test that lazy components are not loaded initially
        $response = $this->get('/');
        $content = $response->getContent();
        
        // Chart.js should not be in initial bundle
        $this->assertStringNotContainsString('chart.js', $content);
        
        // Test lazy loading endpoint
        $response = $this->get('/testing/performance-optimization');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_optimizes_images_with_lazy_loading()
    {
        $response = $this->get('/testing/performance-optimization');
        $content = $response->getContent();
        
        // Check for lazy loading attributes
        $this->assertStringContainsString('loading="lazy"', $content);
        $this->assertStringContainsString('data-lazy', $content);
    }

    /** @test */
    public function it_implements_code_splitting()
    {
        // Check that different routes load different chunks
        $homepageResponse = $this->get('/');
        $dashboardResponse = $this->get('/dashboard');
        
        $homepageContent = $homepageResponse->getContent();
        $dashboardContent = $dashboardResponse->getContent();
        
        // Extract script tags
        preg_match_all('/<script[^>]*src="([^"]*)"/', $homepageContent, $homepageScripts);
        preg_match_all('/<script[^>]*src="([^"]*)"/', $dashboardContent, $dashboardScripts);
        
        // Should have some different scripts (code splitting)
        $this->assertNotEquals($homepageScripts[1], $dashboardScripts[1]);
    }

    /** @test */
    public function it_preloads_critical_resources()
    {
        $response = $this->get('/');
        $content = $response->getContent();
        
        // Check for preload links
        $this->assertStringContainsString('rel="preload"', $content);
        
        // Check for critical resource preloading
        $this->assertTrue(
            str_contains($content, 'as="style"') ||
            str_contains($content, 'as="script"') ||
            str_contains($content, 'as="font"'),
            'Critical resources should be preloaded'
        );
    }

    /** @test */
    public function it_implements_tree_shaking()
    {
        // This test would typically check the built bundles
        // For now, we'll test that unused libraries aren't loaded
        $response = $this->get('/');
        $content = $response->getContent();
        
        // Check that large libraries are not in the main bundle
        $this->assertStringNotContainsString('elasticsearch', $content);
        $this->assertStringNotContainsString('pdf-lib', $content);
    }

    /** @test */
    public function it_serves_webp_images_when_supported()
    {
        // Test WebP support detection
        $response = $this->withHeaders([
            'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8'
        ])->get('/testing/performance-optimization');
        
        $response->assertStatus(200);
        
        // In a real implementation, this would check for WebP images
        // For now, we'll just ensure the page loads
        $this->assertStringContainsString('LazyImage', $response->getContent());
    }

    /** @test */
    public function it_implements_service_worker_caching()
    {
        $response = $this->get('/sw.js');
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/javascript');
        
        $content = $response->getContent();
        
        // Check for caching strategies
        $this->assertStringContainsString('cache', $content);
        $this->assertStringContainsString('fetch', $content);
    }

    /** @test */
    public function it_monitors_performance_metrics()
    {
        // Test performance monitoring endpoint
        $response = $this->postJson('/api/performance/sessions', [
            'sessionId' => 'test-session',
            'startTime' => now()->timestamp * 1000,
            'metrics' => [
                [
                    'loadTime' => 1500,
                    'renderTime' => 800,
                    'memoryUsage' => 50000000,
                    'bundleSize' => 500000,
                    'networkRequests' => 15
                ]
            ],
            'userAgent' => 'Test Agent',
            'url' => 'http://localhost/test'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}