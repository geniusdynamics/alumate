# Testing Strategy

## Overview
This document outlines the comprehensive testing strategy for the template creation system and brand management features.

## Testing Pyramid

```
        ┌─────────────────────┐
        │   E2E Tests (5%)    │
        │  Browser Automation │
        └─────────────────────┘
               │
        ┌─────────────────────┐
        │ Integration Tests   │
        │      (15%)          │
        │  API + Database     │
        └─────────────────────┘
               │
        ┌─────────────────────┐
        │   Unit Tests (80%)  │
        │   Models + Services  │
        └─────────────────────┘
```

## Unit Testing Strategy

### Model Tests

#### Template Model Tests (`tests/Unit/Models/TemplateTest.php`)
```php
class TemplateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_valid_attributes()
    {
        $template = Template::factory()->create();
        
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'name' => $template->name
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(ValidationException::class);
        
        Template::create([
            'tenant_id' => 1,
            // Missing required name field
        ]);
    }

    /** @test */
    public function it_generates_unique_slugs()
    {
        $tenantId = 1;
        $template1 = Template::factory()->create(['name' => 'Test Template', 'tenant_id' => $tenantId]);
        $template2 = Template::factory()->create(['name' => 'Test Template', 'tenant_id' => $tenantId]);
        
        $this->assertNotEquals($template1->slug, $template2->slug);
        $this->assertStringEndsWith('-1', $template2->slug);
    }

    /** @test */
    public function it_applies_tenant_scoping()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $template1 = Template::factory()->create(['tenant_id' => $tenant1->id]);
        $template2 = Template::factory()->create(['tenant_id' => $tenant2->id]);
        
        $this->assertCount(1, Template::forTenant($tenant1->id)->get());
        $this->assertCount(1, Template::forTenant($tenant2->id)->get());
    }

    /** @test */
    public function it_handles_json_structure_validation()
    {
        $template = Template::factory()->create([
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Test Hero']
                    ]
                ]
            ]
        ]);
        
        $this->assertArrayHasKey('sections', $template->structure);
        $this->assertEquals('hero', $template->structure['sections'][0]['type']);
    }

    /** @test */
    public function it_tracks_usage_metrics()
    {
        $template = Template::factory()->create();
        
        $template->incrementUsage();
        $template->refresh();
        
        $this->assertEquals(1, $template->usage_count);
        $this->assertNotNull($template->last_used_at);
    }

    /** @test */
    public function it_calculates_conversion_rates()
    {
        $template = Template::factory()->create([
            'usage_count' => 100,
            'conversion_count' => 25
        ]);
        
        $this->assertEquals(25.0, $template->getConversionRate());
    }
}
```

#### LandingPage Model Tests (`tests/Unit/Models/LandingPageTest.php`)
```php
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_published_and_unpublished()
    {
        $page = LandingPage::factory()->create(['status' => 'draft']);
        
        $page->publish();
        $page->refresh();
        
        $this->assertEquals('published', $page->status);
        $this->assertNotNull($page->published_at);
        
        $page->unpublish();
        $page->refresh();
        
        $this->assertEquals('draft', $page->status);
        $this->assertNull($page->published_at);
    }

    /** @test */
    public function it_generates_public_urls()
    {
        $page = LandingPage::factory()->create([
            'slug' => 'test-page',
            'status' => 'published',
            'published_at' => now()
        ]);
        
        $url = $page->getFullPublicUrl();
        
        $this->assertStringContainsString('test-page', $url);
    }

    /** @test */
    public function it_tracks_usage_and_conversions()
    {
        $page = LandingPage::factory()->create();
        
        $page->incrementUsage();
        $page->incrementConversion();
        $page->refresh();
        
        $this->assertEquals(1, $page->usage_count);
        $this->assertEquals(1, $page->conversion_count);
    }
}
```

#### Brand Model Tests (`tests/Unit/Models/BrandModelTest.php`)
```php
class BrandModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_manages_brand_logos()
    {
        $logo = BrandLogo::factory()->create([
            'name' => 'Primary Logo',
            'type' => 'primary',
            'is_primary' => false
        ]);
        
        $this->assertEquals('Primary Logo', $logo->name);
        $this->assertEquals('primary', $logo->type);
    }

    /** @test */
    public function it_manages_brand_colors()
    {
        $color = BrandColor::factory()->create([
            'name' => 'Primary Blue',
            'value' => '#007bff',
            'type' => 'primary'
        ]);
        
        $this->assertEquals('#007bff', $color->value);
        $this->assertEquals('primary', $color->type);
    }

    /** @test */
    public function it_checks_color_accessibility()
    {
        $color = BrandColor::factory()->create([
            'value' => '#007bff' // Blue
        ]);
        
        $accessibility = $color->accessibility;
        
        $this->assertArrayHasKey('wcag_compliant', $accessibility);
        $this->assertArrayHasKey('contrast_issues', $accessibility);
    }

    /** @test */
    public function it_manages_brand_fonts()
    {
        $font = BrandFont::factory()->create([
            'name' => 'Custom Font',
            'family' => 'CustomFont, Arial, sans-serif',
            'weights' => ['400', '700'],
            'type' => 'custom'
        ]);
        
        $this->assertCount(2, $font->weights);
        $this->assertEquals('custom', $font->type);
    }

    /** @test */
    public function it_manages_brand_templates()
    {
        $template = BrandTemplate::factory()->create([
            'name' => 'Corporate Template'
        ]);
        
        $this->assertEquals('Corporate Template', $template->name);
    }

    /** @test */
    public function it_manages_brand_guidelines()
    {
        $guidelines = BrandGuidelines::factory()->create([
            'enforce_color_palette' => true,
            'min_contrast_ratio' => 4.5
        ]);
        
        $this->assertTrue($guidelines->enforce_color_palette);
        $this->assertEquals(4.5, $guidelines->min_contrast_ratio);
    }
}
```

### Service Tests

#### TemplateService Tests (`tests/Unit/Services/TemplateServiceTest.php`)
```php
class TemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = new TemplateService();
    }

    /** @test */
    public function it_creates_templates_with_valid_data()
    {
        $tenantId = 1;
        $data = [
            'name' => 'Test Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'tenant_id' => $tenantId
        ];

        $template = $this->templateService->create($data, $tenantId);

        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals('Test Template', $template->name);
        $this->assertEquals('landing', $template->category);
    }

    /** @test */
    public function it_duplicates_templates_with_modifications()
    {
        $original = Template::factory()->create(['name' => 'Original Template']);
        $modifications = ['name' => 'Modified Copy'];

        $duplicate = $this->templateService->duplicate($original, $modifications);

        $this->assertInstanceOf(Template::class, $duplicate);
        $this->assertEquals('Modified Copy', $duplicate->name);
        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertStringContainsString('Copy', $duplicate->name);
    }

    /** @test */
    public function it_searches_templates_by_filters()
    {
        Template::factory()->count(5)->create(['category' => 'landing']);
        Template::factory()->count(3)->create(['category' => 'homepage']);

        $results = $this->templateService->search(['category' => 'landing']);

        $this->assertCount(5, $results);
    }

    /** @test */
    public function it_generates_template_previews()
    {
        $template = Template::factory()->create();

        $preview = $this->templateService->generatePreview($template);

        $this->assertIsArray($preview);
        $this->assertArrayHasKey('id', $preview);
        $this->assertArrayHasKey('name', $preview);
        $this->assertArrayHasKey('structure', $preview);
    }
}
```

#### LandingPageService Tests (`tests/Unit/Services/LandingPageServiceTest.php`)
```php
class LandingPageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LandingPageService $landingPageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->landingPageService = new LandingPageService();
    }

    /** @test */
    public function it_creates_landing_pages()
    {
        $template = Template::factory()->create();
        $data = [
            'template_id' => $template->id,
            'name' => 'Test Landing Page',
            'tenant_id' => 1,
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'category' => 'individual'
        ];

        $page = $this->landingPageService->create($data, 1);

        $this->assertInstanceOf(LandingPage::class, $page);
        $this->assertEquals('Test Landing Page', $page->name);
        $this->assertEquals('draft', $page->status);
    }

    /** @test */
    public function it_publishes_landing_pages()
    {
        $page = LandingPage::factory()->create(['status' => 'draft']);

        $publishedPage = $this->landingPageService->publish($page);

        $this->assertEquals('published', $publishedPage->status);
        $this->assertNotNull($publishedPage->published_at);
    }

    /** @test */
    public function it_tracks_page_usage()
    {
        $page = LandingPage::factory()->create();

        $this->landingPageService->incrementUsage($page);
        $page->refresh();

        $this->assertEquals(1, $page->usage_count);
    }
}
```

#### BrandCustomizerService Tests (`tests/Unit/Services/BrandCustomizerServiceTest.php`)
```php
class BrandCustomizerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BrandCustomizerService $brandService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->brandService = new BrandCustomizerService();
    }

    /** @test */
    public function it_creates_brand_logos()
    {
        $data = [
            'name' => 'Test Logo',
            'type' => 'primary',
            'url' => 'https://example.com/logo.png'
        ];

        $logo = $this->brandService->createLogo($data, 1);

        $this->assertInstanceOf(BrandLogo::class, $logo);
        $this->assertEquals('Test Logo', $logo->name);
        $this->assertEquals('primary', $logo->type);
    }

    /** @test */
    public function it_creates_brand_colors()
    {
        $data = [
            'name' => 'Test Blue',
            'value' => '#007bff',
            'type' => 'primary'
        ];

        $color = $this->brandService->createColor($data, 1);

        $this->assertInstanceOf(BrandColor::class, $color);
        $this->assertEquals('#007bff', $color->value);
        $this->assertEquals('primary', $color->type);
    }

    /** @test */
    public function it_creates_brand_fonts()
    {
        $data = [
            'name' => 'Test Font',
            'family' => 'TestFont, Arial, sans-serif',
            'weights' => ['400', '700'],
            'type' => 'custom'
        ];

        $font = $this->brandService->createFont($data, 1);

        $this->assertInstanceOf(BrandFont::class, $font);
        $this->assertCount(2, $font->weights);
    }

    /** @test */
    public function it_runs_consistency_checks()
    {
        $guidelines = [
            'enforce_color_palette' => true,
            'require_contrast_check' => true
        ];

        $assets = [
            'colors' => [['value' => '#007bff']],
            'fonts' => [['family' => 'TestFont']]
        ];

        $report = $this->brandService->runConsistencyCheck($guidelines, $assets, 1);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('issues', $report);
    }
}
```

## Integration Testing Strategy

### API Endpoint Tests (`tests/Feature/Api/TemplateApiTest.php`)
```php
class TemplateApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    /** @test */
    public function it_lists_templates_for_authenticated_users()
    {
        $this->actingAs($this->user);

        Template::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->id}/templates");

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_creates_templates_via_api()
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'API Created Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding'
        ];

        $response = $this->postJson("/api/v1/tenants/{$this->tenant->id}/templates", $data);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'API Created Template');
        $this->assertDatabaseHas('templates', [
            'name' => 'API Created Template',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_validates_template_creation_data()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/tenants/{$this->tenant->id}/templates", [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'category']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson("/api/v1/tenants/{$this->tenant->id}/templates");

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_enforces_tenant_isolation()
    {
        $this->actingAs($this->user);

        $otherTenant = Tenant::factory()->create();
        Template::factory()->count(2)->create(['tenant_id' => $otherTenant->id]);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->id}/templates");

        $response->assertOk();
        $response->assertJsonCount(0, 'data'); // Should not see other tenant's templates
    }
}
```

### Database Integration Tests (`tests/Feature/Database/TemplateDatabaseTest.php`)
```php
class TemplateDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_maintains_referential_integrity()
    {
        $template = Template::factory()->create();
        $page = LandingPage::factory()->create(['template_id' => $template->id]);

        $template->delete();

        $this->assertDatabaseMissing('landing_pages', ['id' => $page->id]);
    }

    /** @test */
    public function it_supports_full_text_search()
    {
        Template::factory()->create(['name' => 'Marketing Landing Page']);
        Template::factory()->create(['name' => 'Sales Funnel Template']);
        Template::factory()->create(['name' => 'Newsletter Signup Form']);

        $results = Template::search('Marketing')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Marketing Landing Page', $results->first()->name);
    }

    /** @test */
    public function it_handles_large_json_structures()
    {
        $largeStructure = [
            'sections' => array_fill(0, 100, [
                'type' => 'content',
                'config' => array_fill_keys(range(1, 50), 'value')
            ])
        ];

        $template = Template::factory()->create([
            'structure' => $largeStructure
        ]);

        $this->assertNotNull($template->structure);
        $this->assertCount(100, $template->structure['sections']);
    }
}
```

## End-to-End Testing Strategy

### Browser Tests (`tests/Browser/TemplateCreationTest.php`)
```php
class TemplateCreationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_allows_users_to_create_templates_through_ui()
    {
        $user = User::factory()->create();
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/templates/create')
                    ->type('name', 'UI Created Template')
                    ->select('category', 'landing')
                    ->select('audience_type', 'individual')
                    ->select('campaign_type', 'onboarding')
                    ->press('Save Template')
                    ->assertPathIs('/templates')
                    ->assertSee('Template created successfully');
        });
    }

    /** @test */
    public function it_allows_template_customization()
    {
        $template = Template::factory()->create();
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($template, $user) {
            $browser->loginAs($user)
                    ->visit("/templates/{$template->id}/customize")
                    ->assertSee($template->name)
                    ->type('custom_title', 'Custom Title')
                    ->press('Preview')
                    ->assertSee('Custom Title')
                    ->press('Save Changes')
                    ->assertSee('Changes saved successfully');
        });
    }

    /** @test */
    public function it_supports_responsive_design()
    {
        $template = Template::factory()->create();
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($template, $user) {
            $browser->loginAs($user)
                    ->visit("/templates/{$template->id}/preview")
                    ->resize(1920, 1080)
                    ->assertSee('Desktop Preview')
                    ->resize(768, 1024)
                    ->assertSee('Tablet Preview')
                    ->resize(375, 667)
                    ->assertSee('Mobile Preview');
        });
    }
}
```

### Workflow Tests (`tests/Browser/TemplateWorkflowTest.php`)
```php
class TemplateWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_supports_complete_template_workflow()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    // 1. Browse templates
                    ->visit('/templates')
                    ->assertSee('Template Library')
                    
                    // 2. Create new template
                    ->click('@create-template-btn')
                    ->assertPathIs('/templates/create')
                    
                    // 3. Fill template details
                    ->type('name', 'Complete Workflow Template')
                    ->select('category', 'landing')
                    ->select('audience_type', 'individual')
                    ->select('campaign_type', 'onboarding')
                    ->press('Save Template')
                    
                    // 4. Customize template
                    ->click('@customize-template-btn')
                    ->type('custom_title', 'My Custom Title')
                    ->press('Save Customization')
                    ->assertSee('Customization saved')
                    
                    // 5. Create landing page
                    ->click('@create-landing-page-btn')
                    ->type('page_name', 'My Landing Page')
                    ->press('Create Page')
                    ->assertSee('Landing page created')
                    
                    // 6. Publish landing page
                    ->click('@publish-page-btn')
                    ->assertSee('Page published successfully')
                    ->assertSee('Live Preview');
        });
    }
}
```

## Performance Testing Strategy

### Load Testing (`tests/Performance/TemplateLoadTest.php`)
```php
class TemplateLoadTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_concurrent_template_requests()
    {
        $this->markTestSkipped('Run with Artisan command for load testing');
        
        // This would be run separately with multiple concurrent requests
        // php artisan test:load --concurrent=100 --requests=1000
    }

    /** @test */
    public function it_maintains_response_times_under_threshold()
    {
        $this->markTestSkipped('Run with performance monitoring');
        
        // Measure response times for template operations
        // Assert that 95th percentile is under 200ms
    }
}
```

### Memory Usage Tests (`tests/Performance/MemoryTest.php`)
```php
class MemoryTest extends TestCase
{
    /** @test */
    public function it_does_not_have_memory_leaks()
    {
        $this->markTestSkipped('Run with memory profiling tools');
        
        // Monitor memory usage during template operations
        // Assert memory consumption stays within acceptable limits
    }
}
```

## Security Testing Strategy

### Authentication Tests (`tests/Feature/Security/AuthenticationTest.php`)
```php
class AuthenticationTest extends TestCase
{
    /** @test */
    public function it_prevents_unauthorized_access()
    {
        $response = $this->get('/templates');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_enforces_tenant_boundaries()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);
        
        $template = Template::factory()->create(['tenant_id' => $tenant1->id]);

        $this->actingAs($user2)
             ->get("/templates/{$template->id}")
             ->assertNotFound(); // User 2 cannot access tenant 1's template
    }

    /** @test */
    public function it_validates_input_sanitization()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $maliciousData = [
            'name' => '<script>alert("xss")</script>',
            'description' => str_repeat('A', 10000), // Too long
            'category' => 'invalid-category' // Invalid option
        ];

        $response = $this->postJson('/api/v1/templates', $maliciousData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'description', 'category']);
    }
}
```

### Data Integrity Tests (`tests/Feature/Security/DataIntegrityTest.php`)
```php
class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_sql_injection()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sqlInjectionPayload = [
            'name' => "'; DROP TABLE templates; --",
            'category' => 'landing'
        ];

        $response = $this->postJson('/api/v1/templates', $sqlInjectionPayload);

        // Should validate and reject, not execute SQL
        $response->assertStatus(422);
        $this->assertDatabaseHas('templates', ['tenant_id' => $user->tenant_id]); // Table still exists
    }

    /** @test */
    public function it_protects_sensitive_data()
    {
        $template = Template::factory()->create([
            'structure' => ['sensitive_config' => 'secret_value']
        ]);

        $response = $this->getJson("/api/v1/templates/{$template->id}");

        $response->assertOk();
        $response->assertDontSee('secret_value'); // Sensitive data should be filtered
    }
}
```

## Test Data Strategy

### Factories (`database/factories/TemplateFactory.php`)
```php
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['landing', 'homepage', 'form', 'email', 'social']),
            'audience_type' => $this->faker->randomElement(['individual', 'institution', 'employer', 'general']),
            'campaign_type' => $this->faker->randomElement([
                'onboarding', 'event_promotion', 'donation', 'networking', 
                'career_services', 'recruiting', 'leadership', 'marketing'
            ]),
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => $this->faker->sentence(),
                            'subtitle' => $this->faker->sentence(),
                            'cta_text' => 'Get Started',
                            'background_image' => $this->faker->imageUrl()
                        ]
                    ]
                ]
            ],
            'default_config' => [],
            'performance_metrics' => [
                'conversion_rate' => $this->faker->randomFloat(2, 0, 100),
                'avg_load_time' => $this->faker->randomFloat(2, 0.1, 5),
                'bounce_rate' => $this->faker->randomFloat(2, 0, 100)
            ],
            'preview_image' => $this->faker->imageUrl(),
            'preview_url' => $this->faker->url(),
            'version' => 1,
            'is_active' => true,
            'is_premium' => false,
            'usage_count' => $this->faker->numberBetween(0, 1000),
            'last_used_at' => $this->faker->dateTimeThisYear(),
            'tags' => $this->faker->words(3),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function landing(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'landing',
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

### Seeders (`database/seeders/TemplateSeeder.php`)
```php
class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample templates for different categories
        $categories = ['landing', 'homepage', 'form', 'email', 'social'];
        $audiences = ['individual', 'institution', 'employer', 'general'];
        $campaigns = [
            'onboarding', 'event_promotion', 'donation', 'networking',
            'career_services', 'recruiting', 'leadership', 'marketing'
        ];

        foreach ($categories as $category) {
            foreach ($audiences as $audience) {
                foreach (array_slice($campaigns, 0, 2) as $campaign) { // Limit combinations
                    Template::factory()
                        ->create([
                            'name' => ucfirst($category) . ' ' . ucfirst($audience) . ' ' . ucfirst(str_replace('_', ' ', $campaign)),
                            'category' => $category,
                            'audience_type' => $audience,
                            'campaign_type' => $campaign,
                            'is_premium' => rand(0, 10) > 8, // 20% premium templates
                        ]);
                }
            }
        }

        // Create landing pages using templates
        $templates = Template::limit(10)->get();
        foreach ($templates as $template) {
            LandingPage::factory()
                ->count(rand(1, 3))
                ->create([
                    'template_id' => $template->id,
                    'tenant_id' => $template->tenant_id,
                ]);
        }
    }
}
```

## Test Coverage Requirements

### Minimum Coverage Targets
- **Models**: 95% coverage
- **Services**: 90% coverage
- **Controllers**: 85% coverage
- **API Endpoints**: 80% coverage
- **Frontend Components**: 75% coverage

### Coverage Measurement
```xml
<!-- phpunit.xml -->
<phpunit>
    <coverage>
        <include>
            <directory suffix=".php">app/Models</directory>
            <directory suffix=".php">app/Services</directory>
            <directory suffix=".php">app/Http/Controllers</directory>
        </include>
        <exclude>
            <directory suffix=".php">app/Models/Vendor</directory>
            <file>app/Models/BaseModel.php</file>
        </exclude>
        <report>
            <clover outputFile="coverage.xml"/>
            <html outputDirectory="coverage-html"/>
        </report>
    </coverage>
</phpunit>
```

## Continuous Integration Testing

### GitHub Actions Workflow (`.github/workflows/tests.yml`)
```yaml
name: Run Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:13
        env:
          POSTGRES_PASSWORD: password
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, pdo, pgsql
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHPUnit tests
        run: |
          cp .env.example .env
          php artisan key:generate
          vendor/bin/phpunit --coverage-clover=coverage.xml

      - name: Run Pest tests
        run: vendor/bin/pest

      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
          flags: unittests
          name: codecov-umbrella

  browser-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Chrome Driver
        uses: browser-actions/setup-chrome@latest

      - name: Run Dusk tests
        run: |
          php artisan dusk:chrome-driver
          php artisan dusk

  static-analysis:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: PHPStan
        run: vendor/bin/phpstan analyse

      - name: PHP-CS-Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Test Environment Configuration

### Testing Database Setup (`.env.testing`)
```env
APP_ENV=testing
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=template_testing
DB_USERNAME=postgres
DB_PASSWORD=password

CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

MAIL_MAILER=array
```

### Parallel Testing Configuration
```bash
# Run tests in parallel for faster execution
php artisan test --parallel --processes=4

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Browser

# Run tests with coverage
php artisan test --coverage --min=80
```

## Test Maintenance Strategy

### Regular Test Reviews
- Monthly review of test coverage reports
- Quarterly update of test data factories
- Biannual performance testing runs
- Annual security testing audits

### Test Data Management
- Use database transactions for test isolation
- Clean up test data after each test
- Use separate databases for different test types
- Implement test data fixtures for consistent testing

### Flaky Test Prevention
- Avoid time-dependent assertions
- Use proper test data setup and teardown
- Mock external dependencies
- Use deterministic test data
- Implement retry mechanisms for intermittent failures

This comprehensive testing strategy ensures the template creation system is robust, secure, and performs well under various conditions while maintaining high code quality standards.