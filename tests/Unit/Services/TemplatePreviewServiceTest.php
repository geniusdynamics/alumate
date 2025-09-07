<?php

namespace Tests\Unit\Services;

use App\Models\Template;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Services\TemplatePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Support\Str;

class TemplatePreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TemplatePreviewService $previewService;
    protected Template $template;
    protected LandingPage $landingPage;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Test Title',
                            'subtitle' => 'Test Subtitle',
                            'cta_text' => 'Get Started'
                        ]
                    ]
                ]
            ]
        ]);

        $this->landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'config' => ['custom_setting' => 'test_value'],
            'brand_config' => ['primary_color' => '#FF0000']
        ]);

        $this->previewService = new TemplatePreviewService();
    }

    /** @test */
    public function it_generates_template_preview_successfully()
    {
        $config = ['custom_title' => 'Custom Title'];
        $options = ['device_mode' => 'desktop'];

        $preview = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertIsArray($preview);
        $this->assertEquals($this->template->id, $preview['template_id']);
        $this->assertEquals('desktop', $preview['device_mode']);
        $this->assertArrayHasKey('compiled_html', $preview);
        $this->assertArrayHasKey('responsive_styles', $preview);
        $this->assertArrayHasKey('cache_hash', $preview);
        $this->assertTrue(strstr($preview['compiled_html'], 'Test Title') !== false);
    }

    /** @test */
    public function it_generates_landing_page_preview_successfully()
    {
        $options = ['device_mode' => 'mobile'];

        $preview = $this->previewService->generateLandingPagePreview(
            $this->landingPage->id,
            $options
        );

        $this->assertIsArray($preview);
        $this->assertEquals($this->landingPage->id, $preview['landing_page_id']);
        $this->assertEquals($this->template->id, $preview['template_id']);
        $this->assertEquals('mobile', $preview['device_mode']);
        $this->assertArrayHasKey('compiled_html', $preview);
        $this->assertArrayHasKey('seo_metadata', $preview);
    }

    /** @test */
    public function it_generates_multi_device_preview()
    {
        $config = [];

        $preview = $this->previewService->generateMultiDevicePreview(
            $this->template->id,
            $config
        );

        $this->assertIsArray($preview);
        $this->assertEquals($this->template->id, $preview['template_id']);
        $this->assertArrayHasKey('devices', $preview);
        $this->assertArrayHasKey('cache_hash', $preview);

        // Check all device modes are present
        $this->assertArrayHasKey('desktop', $preview['devices']);
        $this->assertArrayHasKey('tablet', $preview['devices']);
        $this->assertArrayHasKey('mobile', $preview['devices']);
    }

    /** @test */
    public function it_uses_cache_for_identical_requests()
    {
        $config = ['test' => 'value'];
        $options = ['device_mode' => 'desktop'];

        // First request
        $preview1 = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        // Second request (should use cache)
        $preview2 = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertEquals($preview1['cache_hash'], $preview2['cache_hash']);
        $this->assertEquals($preview1['generated_at'], $preview2['generated_at']);
    }

    /** @test */
    public function it_generates_different_cache_hashes_for_different_configs()
    {
        $config1 = ['test' => 'value1'];
        $config2 = ['test' => 'value2'];
        $options = ['device_mode' => 'desktop'];

        $preview1 = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config1,
            $options
        );

        $preview2 = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config2,
            $options
        );

        $this->assertNotEquals($preview1['cache_hash'], $preview2['cache_hash']);
    }

    /** @test */
    public function it_generates_responsive_css_for_mobile()
    {
        $config = [];
        $options = ['device_mode' => 'mobile'];

        $preview = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertStringContains('@media (max-width: 576px)', $preview['responsive_styles']);
        $this->assertStringContains('.container { max-width: 100%;', $preview['responsive_styles']);
        $this->assertStringContains('padding: 0 15px;', $preview['responsive_styles']);
    }

    /** @test */
    public function it_generates_responsive_css_for_tablet()
    {
        $config = [];
        $options = ['device_mode' => 'tablet'];

        $preview = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertStringContains('@media (max-width: 768px)', $preview['responsive_styles']);
        $this->assertStringContains('.container { max-width: 720px;', $preview['responsive_styles']);
    }

    /** @test */
    public function it_generates_correct_hero_section_html()
    {
        $config = [];
        $options = ['device_mode' => 'desktop'];

        $preview = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertStringContains('<section class=\'hero-section\'>', $preview['compiled_html']);
        $this->assertStringContains('<h1 class=\'hero-title\'>Test Title</h1>', $preview['compiled_html']);
        $this->assertStringContains('<p class=\'hero-subtitle\'>Test Subtitle</p>', $preview['compiled_html']);
        $this->assertStringContains('<a href=\'#\' class=\'cta-button\'>Get Started</a>', $preview['compiled_html']);
    }

    /** @test */
    public function it_includes_performance_metrics()
    {
        $config = [];
        $options = ['device_mode' => 'desktop'];

        $preview = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertArrayHasKey('performance_metrics', $preview);
        $this->assertArrayHasKey('generation_time_ms', $preview['performance_metrics']);
        $this->assertArrayHasKey('memory_usage_mb', $preview['performance_metrics']);
        $this->assertArrayHasKey('generated_at', $preview['performance_metrics']);
    }

    /** @test */
    public function it_includes_tenant_metadata_in_preview()
    {
        $config = [];
        $options = ['device_mode' => 'desktop'];

        $preview = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertArrayHasKey('metadata', $preview);
        $this->assertEquals($this->tenant->id, $preview['metadata']['tenant_id']);
        $this->assertEquals($this->template->category, $preview['metadata']['category']);
        $this->assertTrue($preview['metadata']['is_active']);
    }

    /** @test */
    public function it_fails_with_nonexistent_template()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->previewService->generateTemplatePreview(99999, [], []);
    }

    /** @test */
    public function it_fails_with_tenant_isolation_violation()
    {
        // Create template for different tenant
        $otherTenant = Tenant::factory()->create();
        $otherTemplate = Template::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: Template does not belong to current tenant');

        $this->previewService->generateTemplatePreview($otherTemplate->id, [], []);
    }

    /** @test */
    public function it_generates_preview_url()
    {
        $config = ['test' => 'config'];
        $options = ['device_mode' => 'desktop'];

        $preview = $this->previewService->generateTemplatePreview(
            $this->template->id,
            $config,
            $options
        );

        $this->assertArrayHasKey('preview_url', $preview);
        $this->assertStringContains('/api/templates/preview/', $preview['preview_url']);
        $this->assertStringContains('template_id=' . $this->template->id, $preview['preview_url']);
        $this->assertStringContains('device_mode=desktop', $preview['preview_url']);
    }

    /** @test */
    public function it_clears_template_cache_successfully()
    {
        $result = $this->previewService->clearTemplateCache($this->template->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_provides_preview_options_configuration()
    {
        $options = $this->previewService->getPreviewOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('device_modes', $options);
        $this->assertArrayHasKey('breakpoints', $options);
        $this->assertArrayHasKey('responsive_modes', $options);
        $this->assertArrayHasKey('output_formats', $options);
        $this->assertArrayHasKey('cache_options', $options);

        $this->assertContains('desktop', $options['device_modes']);
        $this->assertContains('tablet', $options['device_modes']);
        $this->assertContains('mobile', $options['device_modes']);
    }
}