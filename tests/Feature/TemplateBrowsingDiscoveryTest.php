<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TemplateBrowsingDiscoveryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $testUser;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Discovery University',
            'domain' => 'discovery-university.com',
        ]);

        $this->testUser = User::factory()->create([
            'name' => 'Discovery Admin',
            'email' => 'admin@discovery-university.com',
            'tenant_id' => $this->institution->id,
        ]);

        // Create variety of templates for discovery testing
        $templates = [
            ['name' => 'Marketing Landing Page', 'category' => 'landing', 'audience_type' => 'individual', 'campaign_type' => 'marketing', 'usage_count' => 150, 'is_premium' => false],
            ['name' => 'Premium Business Template', 'category' => 'landing', 'audience_type' => 'institution', 'campaign_type' => 'leadership', 'usage_count' => 75, 'is_premium' => true],
            ['name' => 'Student Recruitment Form', 'category' => 'form', 'audience_type' => 'individual', 'campaign_type' => 'onboarding', 'usage_count' => 200, 'is_premium' => false],
            ['name' => 'Corporate Email Newsletter', 'category' => 'email', 'audience_type' => 'institution', 'campaign_type' => 'marketing', 'usage_count' => 95, 'is_premium' => false],
            ['name' => 'Social Media Graphic', 'category' => 'social', 'audience_type' => 'general', 'campaign_type' => 'marketing', 'usage_count' => 180, 'is_premium' => false],
        ];

        foreach ($templates as $templateData) {
            Template::factory()->create(array_merge($templateData, [
                'tenant_id' => $this->testUser->tenant_id,
                'structure' => ['sections' => []],
                'default_config' => ['theme' => []],
                'tags' => ['popular', 'featured'],
            ]));
        }
    }

    public function test_template_discovery_and_recommendations()
    {
        // Test template recommendations based on usage patterns
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/recommendations');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id', 'name', 'category', 'audience_type',
                            'usage_count', 'is_premium', 'score'
                        ]
                    ]
                ]);

        $recommendations = $response->json('data');
        $this->assertTrue(count($recommendations) > 0);

        // Verify recommendations are sorted by engagement score
        $sortedByScore = collect($recommendations)->pluck('usage_count')->toArray();
        $this->assertEquals($sortedByScore, collect($sortedByScore)->sortDesc()->values()->toArray());
    }

    public function test_template_search_and_filters()
    {
        // Test text search
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?search=Marketing');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(2, $templates); // "Marketing Landing Page" and "Corporate Email Newsletter"

        // Test category filter
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?category=landing');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(2, $templates); // Both landing page templates

        // Test audience filter
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?audience_type=institution');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(2, $templates); // Business and Corporate templates

        // Test premium filter
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?is_premium=true');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals('Premium Business Template', $templates[0]['name']);
    }

    public function test_template_sorting_and_pagination()
    {
        // Test sorting by usage count
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?sort=usage_count&direction=desc');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $usageCounts = collect($templates)->pluck('usage_count')->toArray();
        $this->assertEquals($usageCounts, collect($usageCounts)->sortDesc()->values()->toArray());

        // Test sorting by name
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?sort=name&direction=asc');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $names = collect($templates)->pluck('name')->toArray();
        $this->assertEquals($names, collect($names)->sort()->values()->toArray());

        // Test pagination
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?per_page=2&page=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data['data']);
        $this->assertEquals(2, $data['per_page']);
        $this->assertEquals(1, $data['current_page']);
    }

    public function test_template_featured_and_trending_categories()
    {
        // Mark some templates as featured
        Template::where('name', 'Marketing Landing Page')
            ->update(['tags' => array_merge(['featured'], ['popular'])]);

        Template::where('name', 'Premium Business Template')
            ->update(['usage_count' => 300]); // Make it trending

        // Test featured templates
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/featured');

        $response->assertStatus(200);
        $featured = $response->json('data');
        $this->assertContains('Marketing Landing Page',
            collect($featured)->pluck('name')->toArray());

        // Test trending templates (high usage)
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/trending');

        $response->assertStatus(200);
        $trending = $response->json('data');
        $trendingNames = collect($trending)->pluck('name')->toArray();
        $this->assertContains('Premium Business Template', $trendingNames);
        $this->assertContains('Student Recruitment Form', $trendingNames); // High usage count
    }

    public function test_template_categories_and_tags()
    {
        // Test category browsing
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/categories');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'name', 'count', 'templates'
                        ]
                    ]
                ]);

        $categories = $response->json('data');
        $categoryNames = collect($categories)->pluck('name')->toArray();
        $this->assertContains('landing', $categoryNames);
        $this->assertContains('form', $categoryNames);

        // Test tag-based discovery
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?tags[]=popular');

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertTrue(count($templates) > 0);

        // All should have the 'popular' tag
        foreach ($templates as $template) {
            $this->assertContains('popular', $template['tags']);
        }
    }

    public function test_template_similar_suggestions()
    {
        $baseTemplate = Template::where('name', 'Marketing Landing Page')->first();

        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$baseTemplate->id}/similar");

        $response->assertStatus(200);
        $similar = $response->json('data');

        // Should suggest templates with same category/audience
        $similarNames = collect($similar)->pluck('name')->toArray();
        $this->assertContains('Corporate Email Newsletter', $similarNames); // Same campaign type

        // Verify similarity scoring
        foreach ($similar as $template) {
            $this->assertArrayHasKey('similarity_score', $template);
            $this->assertGreaterThan(0, $template['similarity_score']);
        }
    }

    public function test_template_recent_and_popular_browse()
    {
        // Update some templates with recent activity
        Template::where('name', 'Social Media Graphic')
            ->update(['last_used_at' => now()]);

        // Test recently used templates
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/recent');

        $response->assertStatus(200);
        $recent = $response->json('data');
        $recentNames = collect($recent)->pluck('name')->toArray();
        $this->assertContains('Social Media Graphic', $recentNames);

        // Test popular templates
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/popular');

        $response->assertStatus(200);
        $popular = $response->json('data');
        $this->assertTrue(count($popular) > 0);

        // Verify all have high usage counts
        foreach ($popular as $template) {
            $this->assertGreaterThan(100, $template['usage_count']);
        }
    }

    public function test_template_preview_and_quick_view()
    {
        $template = Template::first();

        // Test quick preview without full rendering
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/quick-preview");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'preview_url', 'thumbnail_url', 'key_features',
                        'performance_score', 'compatibility_info'
                    ]
                ]);

        // Test template preview generation
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");

        $response->assertStatus(200);
        $previewData = $response->json('data');
        $this->assertArrayHasKey('render_time', $previewData);
        $this->assertLessThan(5.0, $previewData['render_time']); // Should render quickly
    }

    public function test_template_rating_and_review_system()
    {
        $template = Template::first();

        // Add rating and review
        $reviewData = [
            'rating' => 5,
            'review' => 'Excellent template! Very easy to customize and looks professional.',
            'helpful_features' => ['responsive', 'fast_loading'],
            'improvement_suggestions' => 'Would love more color themes',
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson("/api/templates/{$template->id}/reviews", $reviewData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id', 'rating', 'review', 'helpful_count'
                    ]
                ]);

        // Test getting template reviews
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/reviews");

        $response->assertStatus(200);
        $reviews = $response->json('data');
        $this->assertCount(1, $reviews);
        $this->assertEquals(5, $reviews[0]['rating']);

        // Test helpful votes
        $response = $this->actingAs($this->testUser)
            ->postJson("/api/templates/{$template->id}/reviews/{$reviews[0]['id']}/helpful");

        $response->assertStatus(200);

        // Test average rating calculation
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}");

        $response->assertStatus(200);
        $templateData = $response->json('data');
        $this->assertArrayHasKey('average_rating', $templateData);
        $this->assertEquals(5.0, $templateData['average_rating']);
    }

    public function test_template_collections_and_curated_sets()
    {
        // Create a template collection
        $collectionData = [
            'name' => 'Marketing Essentials',
            'description' => 'Essential templates for marketing campaigns',
            'templates' => Template::where('campaign_type', 'marketing')->pluck('id')->toArray(),
            'tags' => ['marketing', 'essentials', 'campaign'],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/template-collections', $collectionData);

        $response->assertStatus(201);
        $collectionId = $response->json('data.id');

        // Test browsing collections
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/template-collections');

        $response->assertStatus(200);
        $collections = $response->json('data.data');
        $this->assertCount(1, $collections);
        $this->assertEquals('Marketing Essentials', $collections[0]['name']);

        // Test collection contents
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/template-collections/{$collectionId}/templates");

        $response->assertStatus(200);
        $templates = $response->json('data');
        $this->assertCount(3, $templates); // Should include 3 marketing templates
    }

    public function test_template_usage_analytics_and_insights()
    {
        // Simulate template usage
        $template = Template::first();
        $template->increment('usage_count');
        $template->update(['last_used_at' => now()]);

        // Test usage analytics
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/analytics/usage');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total_usage' => [
                            'total_count' => [],
                            'by_category' => [],
                            'trending' => [],
                            'peak_usage_times' => []
                        ]
                    ]
                ]);

        $analytics = $response->json('data.total_usage');
        $this->assertGreaterThan(0, $analytics['total_count']);

        // Test user engagement insights
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates/analytics/engagement');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'avg_session_duration' => [],
                        'popular_features' => [],
                        'abandonment_points' => []
                    ]
                ]);
    }
}