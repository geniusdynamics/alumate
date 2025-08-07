<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\HomepageService;
use App\Services\ABTestingService;

class HeroSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_with_individual_audience()
    {
        $response = $this->get('/homepage');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Homepage/Index')
                ->has('audience')
                ->where('audience', 'individual')
                ->has('content')
                ->has('meta')
        );
    }

    public function test_homepage_loads_with_institutional_audience()
    {
        $response = $this->get('/homepage/institutional');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Homepage/Index')
                ->has('audience')
                ->where('audience', 'institutional')
                ->has('content')
                ->has('meta')
        );
    }

    public function test_cta_click_tracking()
    {
        $response = $this->post('/homepage/track-cta', [
            'action' => 'trial',
            'section' => 'hero',
            'audience' => 'individual',
            'additional_data' => [
                'text' => 'Start Free Trial',
                'variant' => 'primary'
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'CTA click tracked successfully'
        ]);
    }

    public function test_ab_test_conversion_tracking()
    {
        $response = $this->post('/homepage/track-conversion', [
            'test_id' => 'hero_message_dual_audience',
            'variant_id' => 'control',
            'goal' => 'hero_cta_click',
            'audience' => 'individual',
            'additional_data' => [
                'action' => 'trial',
                'section' => 'hero'
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Conversion tracked successfully'
        ]);
    }

    public function test_homepage_service_provides_dual_audience_content()
    {
        $homepageService = app(HomepageService::class);
        
        // Test individual content
        $individualContent = $homepageService->getPersonalizedContent('individual');
        $this->assertArrayHasKey('hero', $individualContent);
        $this->assertArrayHasKey('cta', $individualContent);
        $this->assertStringContains('Career', $individualContent['hero']['headline']);
        
        // Test institutional content
        $institutionalContent = $homepageService->getPersonalizedContent('institutional');
        $this->assertArrayHasKey('hero', $institutionalContent);
        $this->assertArrayHasKey('cta', $institutionalContent);
        $this->assertStringContains('Alumni Engagement', $institutionalContent['hero']['headline']);
    }

    public function test_ab_testing_service_provides_variants()
    {
        $abTestingService = app(ABTestingService::class);
        
        $variant = $abTestingService->getVariant(
            'hero_message_dual_audience',
            'test_user_123',
            'individual'
        );
        
        $this->assertArrayHasKey('id', $variant);
        $this->assertArrayHasKey('name', $variant);
        $this->assertArrayHasKey('component_overrides', $variant);
    }

    public function test_hero_section_displays_statistics()
    {
        $homepageService = app(HomepageService::class);
        $statistics = $homepageService->getPlatformStatistics('individual');
        
        $this->assertArrayHasKey('total_alumni', $statistics);
        $this->assertArrayHasKey('average_salary_increase', $statistics);
        $this->assertArrayHasKey('job_placements', $statistics);
        $this->assertIsNumeric($statistics['total_alumni']);
        $this->assertIsNumeric($statistics['average_salary_increase']);
        $this->assertIsNumeric($statistics['job_placements']);
    }

    public function test_hero_section_displays_testimonials()
    {
        $homepageService = app(HomepageService::class);
        $testimonials = $homepageService->getTestimonials('individual');
        
        $this->assertNotEmpty($testimonials);
        $testimonial = $testimonials->first();
        $this->assertArrayHasKey('quote', $testimonial);
        $this->assertArrayHasKey('author', $testimonial);
        $this->assertArrayHasKey('name', $testimonial['author']);
    }
}