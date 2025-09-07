<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MobileTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MobileTemplateRendererTest extends TestCase
{
    use RefreshDatabase;

    private MobileTemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MobileTemplateRenderer();
    }

    public function test_mobile_template_rendering()
    {
        $templateStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'content' => [
                        'text' => 'Welcome to our site',
                        'images' => [
                            ['src' => '/image1.jpg', 'alt' => 'Hero image']
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('css', $result);
        $this->assertArrayHasKey('javascript', $result);
        $this->assertArrayHasKey('optimizations', $result);
        $this->assertArrayHasKey('device_capabilities', $result);

        // Check that mobile-specific HTML is generated
        $this->assertStringContainsString('mobile-template-container', $result['html']);
        $this->assertStringContainsString('mobile-section', $result['html']);
    }

    public function test_tablet_device_capabilities()
    {
        $templateStructure = ['sections' => []];
        $result = $this->renderer->renderForMobile($templateStructure, 'tablet');

        $this->assertEquals(768, $result['device_capabilities']['viewport_width']);
        $this->assertTrue($result['device_capabilities']['high_dpi']);
        $this->assertTrue($result['device_capabilities']['touch_enabled']);
    }

    public function test_mobile_device_capabilities()
    {
        $templateStructure = ['sections' => []];
        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        $this->assertEquals(320, $result['device_capabilities']['viewport_width']);
        $this->assertTrue($result['device_capabilities']['high_dpi']);
        $this->assertTrue($result['device_capabilities']['touch_enabled']);
        $this->assertTrue($result['device_capabilities']['accelerometer']);
    }

    public function test_mobile_optimizations_applied()
    {
        $templateStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'content' => [
                        'images' => [
                            ['src' => '/test.jpg', 'alt' => 'Test image']
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        // Check that mobile optimizations are applied
        $this->assertArrayHasKey('mobile_meta', $result['optimizations']);
        $this->assertArrayHasKey('mobile_spacing', $result['optimizations']['sections'][0]);
        $this->assertArrayHasKey('mobile_styles', $result['optimizations']['sections'][0]['content']['images'][0]);
    }

    public function test_responsive_css_generation()
    {
        $templateStructure = ['sections' => []];
        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        // Check that responsive CSS is generated
        $this->assertStringContainsString('@media (max-width: 640px)', $result['css']);
        $this->assertStringContainsString('mobile-template-container', $result['css']);
        $this->assertStringContainsString('mobile-section', $result['css']);
    }

    public function test_touch_interactions_javascript()
    {
        $templateStructure = ['sections' => []];
        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        // Check that touch interaction JavaScript is generated
        $this->assertStringContainsString('TouchHandler', $result['javascript']);
        $this->assertStringContainsString('handleTouchStart', $result['javascript']);
        $this->assertStringContainsString('handleSwipeLeft', $result['javascript']);
        $this->assertStringContainsString('handleSwipeRight', $result['javascript']);
    }

    public function test_mobile_meta_tags()
    {
        $templateStructure = ['sections' => []];
        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        // Check that mobile meta tags are included
        $this->assertArrayHasKey('mobile_meta', $result['optimizations']);
        $this->assertArrayHasKey('viewport', $result['optimizations']['mobile_meta']);
        $this->assertArrayHasKey('mobile_web_app_capable', $result['optimizations']['mobile_meta']);
    }

    public function test_image_optimization_for_mobile()
    {
        $templateStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'content' => [
                        'images' => [
                            ['src' => '/hero.jpg', 'alt' => 'Hero image']
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        // Check that images are optimized for mobile
        $optimizedImage = $result['optimizations']['sections'][0]['content']['images'][0];
        $this->assertArrayHasKey('attributes', $optimizedImage);
        $this->assertArrayHasKey('mobile_styles', $optimizedImage);
        $this->assertEquals('lazy', $optimizedImage['attributes']['loading']);
    }

    public function test_responsive_breakpoints()
    {
        $templateStructure = ['sections' => []];
        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        // Check that responsive breakpoints are configured
        $this->assertArrayHasKey('responsive_breakpoints', $result['optimizations']);
        $this->assertArrayHasKey('xs', $result['optimizations']['responsive_breakpoints']);
        $this->assertArrayHasKey('sm', $result['optimizations']['responsive_breakpoints']);
        $this->assertArrayHasKey('md', $result['optimizations']['responsive_breakpoints']);
    }

    public function test_performance_optimizations()
    {
        $templateStructure = ['sections' => []];
        $result = $this->renderer->renderForMobile($templateStructure, 'mobile');

        // Check that performance optimizations are included
        $this->assertArrayHasKey('optimizations', $result);
        $this->assertTrue($result['optimizations']['lazy_loading']);
        $this->assertTrue($result['optimizations']['image_optimization']);
        $this->assertTrue($result['optimizations']['critical_css']);
    }
}