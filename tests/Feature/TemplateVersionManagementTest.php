<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TemplateVersionManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $testUser;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Version Control University',
            'domain' => 'version-university.com',
        ]);

        $this->testUser = User::factory()->create([
            'name' => 'Version Admin',
            'email' => 'admin@version-university.com',
            'tenant_id' => $this->institution->id,
        ]);
    }

    public function test_template_version_creation_and_tracking()
    {
        // Create base template
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Version Test Template',
            'version' => 1,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Original Title',
                            'subtitle' => 'Original subtitle',
                        ]
                    ]
                ]
            ],
        ]);

        // Create version 2.0
        $versionData1 = [
            'template_id' => $template->id,
            'version_name' => 'Version 2.0',
            'changes' => 'Enhanced hero section with call-to-action button',
            'structure_changes' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Enhanced Title v2',
                            'subtitle' => 'Improved subtitle v2',
                            'cta_text' => 'Get Started Now',
                            'cta_url' => '/start',
                        ]
                    ]
                ]
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/versions', $versionData1);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id', 'template_id', 'version_name', 'changes',
                        'structure_changes', 'created_at'
                    ]
                ]);

        // Verify version tracking
        $template->refresh();
        $this->assertEquals(2, $template->version); // Version should be incremented

        // Create version 3.0
        $versionData2 = [
            'template_id' => $template->id,
            'version_name' => 'Version 3.0 - Major Update',
            'changes' => 'Added testimonials section, improved mobile responsiveness, enhanced SEO',
            'structure_changes' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Premium Title v3',
                            'subtitle' => 'Professional subtitle v3',
                            'cta_text' => 'Start Free Trial',
                            'cta_url' => '/trial',
                        ]
                    ],
                    [
                        'type' => 'testimonials',
                        'config' => [
                            'title' => 'What Our Users Say',
                            'items' => [
                                [
                                    'quote' => 'Amazing platform!',
                                    'author' => 'John Doe',
                                    'position' => 'CEO',
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/versions', $versionData2);

        $response->assertStatus(201);

        // Verify version history retrieval
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/versions");

        $response->assertStatus(200);
        $versions = $response->json('data');

        $this->assertCount(2, $versions);
        $this->assertEquals('Version 3.0 - Major Update', $versions[0]['version_name']);
        $this->assertEquals('Version 2.0', $versions[1]['version_name']);
    }

    public function test_template_version_rollback()
    {
        $originalStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => ['title' => 'Original Hero']
                ]
            ]
        ];

        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Rollback Test Template',
            'structure' => $originalStructure,
            'version' => 1,
        ]);

        // Create a modified version
        $modifiedStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => ['title' => 'Modified Hero']
                ],
                [
                    'type' => 'text',
                    'config' => ['content' => 'New section']
                ]
            ]
        ];

        $template->update(['structure' => $modifiedStructure, 'version' => 2]);

        // Rollback to previous version
        $response = $this->actingAs($this->testUser)
            ->postJson("/api/templates/{$template->id}/rollback", [
                'target_version' => 1,
            ]);

        $response->assertStatus(200);

        $template->refresh();
        $this->assertEquals(1, $template->version);
        $this->assertEquals($originalStructure, $template->structure);
    }

    public function test_template_version_comparison()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Comparison Test Template',
            'structure' => [
                'sections' => [
                    ['type' => 'hero', 'config' => ['title' => 'V1 Title']]
                ]
            ],
            'version' => 1,
        ]);

        // Create version 2
        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/versions', [
                'template_id' => $template->id,
                'version_name' => 'Version 2',
                'changes' => 'Updated hero title',
                'structure_changes' => [
                    'sections' => [
                        ['type' => 'hero', 'config' => ['title' => 'V2 Title']]
                    ]
                ],
            ]);

        // Compare versions
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/versions/compare", [
                'from_version' => 1,
                'to_version' => 2,
            ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'differences' => [
                            'added', 'removed', 'modified'
                        ],
                        'from_version', 'to_version'
                    ]
                ]);

        $comparison = $response->json('data');

        // Should detect the title change
        $this->assertTrue(count($comparison['differences']['modified']) > 0);
    }

    public function test_template_migration_between_versions()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Migration Test Template',
            'version' => 1,
            'structure' => [
                'version' => '1.0',
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Legacy Hero Component']
                    ]
                ]
            ],
        ]);

        // Simulate a structure migration from v1 to v2 format
        $migrationData = [
            'target_version' => '2.0',
            'migration_rules' => [
                'hero' => [
                    'title' => 'headline', // Rename 'title' to 'headline'
                    'add_cta' => true, // Add CTA section
                ]
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson("/api/templates/{$template->id}/migrate", $migrationData);

        $response->assertStatus(200);

        $template->refresh();

        // Verify migration applied
        $this->assertEquals('2.0', $template->structure['version'] ?? null);
        $this->assertArrayHasKey('headline', $template->structure['sections'][0]['config']);
        $this->assertArrayNotHasKey('title', $template->structure['sections'][0]['config']);
    }

    public function test_template_version_access_control()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Access Control Template',
        ]);

        // Create version as owner
        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/versions', [
                'template_id' => $template->id,
                'version_name' => 'Version 2',
                'changes' => 'Access control test',
            ]);

        $response->assertStatus(201);

        // Create another user (same tenant)
        $sameTenantUser = User::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'email' => 'user2@version-university.com',
        ]);

        // Same tenant user can create versions
        $response = $this->actingAs($sameTenantUser)
            ->postJson('/api/templates/versions', [
                'template_id' => $template->id,
                'version_name' => 'Version 3',
                'changes' => 'By team member',
            ]);

        $response->assertStatus(201);

        // Create user from different tenant
        $otherTenantUser = User::factory()->create([
            'email' => 'external@other-university.com',
            'tenant_id' => Institution::factory()->create(['domain' => 'other.com'])->id,
        ]);

        // Different tenant user cannot create versions
        $response = $this->actingAs($otherTenantUser)
            ->postJson('/api/templates/versions', [
                'template_id' => $template->id,
                'version_name' => 'Unauthorized Version',
                'changes' => 'Should be blocked',
            ]);

        $response->assertStatus(403);
    }

    public function test_bulk_template_version_operations()
    {
        // Create multiple templates
        $templates = Template::factory()->count(3)->create([
            'tenant_id' => $this->testUser->tenant_id,
        ]);

        // Bulk version creation
        $bulkVersionData = [
            'templates' => $templates->pluck('id')->toArray(),
            'version_name' => 'Bulk Update v2.0',
            'changes' => 'Bulk version update for maintenance',
            'apply_changes' => [
                'structure' => [
                    'add_responsive' => true, // Add responsive classes
                ]
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/versions/bulk', $bulkVersionData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'processed_count' => 3,
                        'success_count' => 3,
                        'failure_count' => 0,
                    ]
                ]);

        // Verify all templates were updated
        foreach ($templates as $template) {
            $template->refresh();
            $this->assertEquals(2, $template->version);
        }
    }

    public function test_template_version_auto_save_and_draft_management()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Auto-save Test Template',
        ]);

        // Enable auto-save
        $response = $this->actingAs($this->testUser)
            ->postJson("/api/templates/{$template->id}/auto-save/enable");

        $response->assertStatus(200);

        // Make several auto-saved changes
        $changes = [
            ['path' => 'structure.sections.0.config.title', 'value' => 'Auto-save 1'],
            ['path' => 'structure.sections.0.config.subtitle', 'value' => 'Auto-save subtitle'],
            ['path' => 'default_config.theme.primary_color', 'value' => '#ff0000'],
        ];

        foreach ($changes as $change) {
            $response = $this->actingAs($this->testUser)
                ->postJson("/api/templates/{$template->id}/auto-save", $change);

            $response->assertStatus(200);
        }

        // List auto-save drafts
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/auto-save");

        $response->assertStatus(200);
        $this->assertTrue(count($response->json('data')) > 0);

        // Apply auto-save draft to create new version
        $drafts = $response->json('data');
        $latestDraft = $drafts[0];

        $response = $this->actingAs($this->testUser)
            ->postJson("/api/templates/{$template->id}/auto-save/apply", [
                'draft_id' => $latestDraft['id'],
                'version_name' => 'Applied Auto-save Changes',
            ]);

        $response->assertStatus(201);

        $template->refresh();
        $this->assertEquals(2, $template->version); // Version incremented
    }

    public function test_template_version_performance_monitoring()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Performance Test Template',
        ]);

        // Create several versions
        for ($i = 2; $i <= 5; $i++) {
            $response = $this->actingAs($this->testUser)
                ->postJson('/api/templates/versions', [
                    'template_id' => $template->id,
                    'version_name' => "Version {$i}.0",
                    'changes' => "Performance test version {$i}",
                ]);

            $response->assertStatus(201);
        }

        // Check version loading performance
        $startTime = microtime(true);

        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/versions");

        $loadTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Performance should be under 200ms for 5 versions
        $this->assertLessThan(0.2, $loadTime);

        $versions = $response->json('data');
        $this->assertCount(4, $versions); // 4 versions created (v2, v3, v4, v5)
    }

    public function test_template_version_conflict_resolution()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Conflict Test Template',
            'structure' => [
                'sections' => [
                    ['type' => 'hero', 'config' => ['title' => 'Original']]
                ]
            ],
            'version' => 1,
        ]);

        // Simulate concurrent modifications
        $user2 = User::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'email' => 'user2@version-university.com',
        ]);

        // Both users modify the same template
        $change1 = $this->actingAs($this->testUser)
            ->postJson('/api/templates/versions', [
                'template_id' => $template->id,
                'version_name' => 'User1 Change',
                'changes' => 'Changed by user 1',
                'structure_changes' => [
                    'sections' => [
                        ['type' => 'hero', 'config' => ['title' => 'User1 Version']]
                    ]
                ],
            ]);

        $change2 = $this->actingAs($user2)
            ->postJson('/api/templates/versions', [
                'template_id' => $template->id,
                'version_name' => 'User2 Change',
                'changes' => 'Changed by user 2',
                'structure_changes' => [
                    'sections' => [
                        ['type' => 'hero', 'config' => ['title' => 'User2 Version']]
                    ]
                ],
            ]);

        $change1->assertStatus(201);
        $change2->assertStatus(201);

        // Check version history shows both changes
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/versions");

        $response->assertStatus(200);
        $versions = $response->json('data');

        $this->assertCount(2, $versions);
        $this->assertEquals(3, $template->fresh()->version); // Version incremented twice
    }
}