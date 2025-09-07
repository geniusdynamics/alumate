<?php

use App\Models\Component;
use App\Models\ComponentVersion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['institution_id' => $this->tenant->id]);
    $this->actingAs($this->user);
    
    Storage::fake('local');
    Cache::flush();
});

describe('GrapeJS Integration Regression Testing', function () {
    it('maintains component functionality after system updates', function () {
        // Create baseline component with known configuration
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'version' => '1.0.0',
            'config' => [
                'headline' => 'Alumni Network',
                'subheading' => 'Connect with graduates worldwide',
                'audienceType' => 'individual',
                'backgroundType' => 'image',
                'ctaButtons' => [
                    ['text' => 'Join Now', 'url' => '/signup', 'style' => 'primary']
                ]
            ]
        ]);

        // Capture baseline functionality
        $baselineResponse = $this->getJson("/api/components/{$component->id}/grapejs-block");
        $baselineResponse->assertOk();
        $baselineData = $baselineResponse->json('data');

        // Store baseline for comparison
        $baselineChecksum = md5(json_encode($baselineData));

        // Simulate system update by creating a new version
        ComponentVersion::factory()->create([
            'component_id' => $component->id,
            'version_number' => 2,
            'config' => $component->config,
            'created_by' => $this->user->id
        ]);

        // Test regression scenarios
        $regressionTests = [
            'block_generation' => fn() => $this->getJson("/api/components/{$component->id}/grapejs-block"),
            'trait_configuration' => fn() => $this->getJson("/api/components/{$component->id}/grapejs-traits/validate"),
            'serialization' => fn() => $this->postJson('/api/components/serialize-to-grapejs', [
                'component_ids' => [$component->id]
            ]),
            'compatibility_check' => fn() => $this->getJson("/api/components/{$component->id}/grapejs-compatibility")
        ];

        $regressionResults = [];
        foreach ($regressionTests as $testName => $testFunction) {
            $response = $testFunction();
            $response->assertOk();
            
            $regressionResults[$testName] = [
                'passed' => $response->isOk(),
                'data' => $response->json('data'),
                'response_time' => $response->headers->get('X-Response-Time', 0)
            ];
        }

        // Verify no regression in block generation
        $newBlockResponse = $this->getJson("/api/components/{$component->id}/grapejs-block");
        $newBlockResponse->assertOk();
        $newBlockData = $newBlockResponse->json('data');
        $newChecksum = md5(json_encode($newBlockData));

        expect($newChecksum)->toBe($baselineChecksum, 'Block generation should produce identical results');

        // Verify all regression tests passed
        foreach ($regressionResults as $testName => $result) {
            expect($result['passed'])->toBeTrue("Regression test '{$testName}' should pass");
        }
    });

    it('validates backward compatibility with older component versions', function () {
        // Create component with legacy configuration format
        $legacyComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'version' => '0.9.0', // Old version
            'config' => [
                // Legacy format
                'form_title' => 'Contact Us', // Old property name
                'form_fields' => [ // Old property name
                    [
                        'field_type' => 'text', // Old property name
                        'field_name' => 'name',
                        'field_label' => 'Full Name',
                        'is_required' => true // Old property name
                    ]
                ],
                'submit_button_text' => 'Send Message' // Old property name
            ]
        ]);

        $response = $this->postJson("/api/components/{$legacyComponent->id}/grapejs-compatibility/backward", [
            'target_versions' => ['0.9.0', '1.0.0', '1.5.0', '2.0.0'],
            'migration_test' => true,
            'preserve_functionality' => true
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'backward_compatible',
                    'version_compatibility',
                    'migration_required',
                    'migration_path',
                    'breaking_changes',
                    'deprecated_features'
                ]
            ]);

        $compatibilityResults = $response->json('data');
        
        expect($compatibilityResults['backward_compatible'])->toBeTrue();
        expect($compatibilityResults['breaking_changes'])->toBeEmpty();
        
        // Test migration if required
        if ($compatibilityResults['migration_required']) {
            $migrationResponse = $this->postJson("/api/components/{$legacyComponent->id}/migrate", [
                'target_version' => '2.0.0',
                'preserve_data' => true
            ]);
            
            $migrationResponse->assertOk();
            
            // Verify component still works after migration
            $postMigrationResponse = $this->getJson("/api/components/{$legacyComponent->id}/grapejs-block");
            $postMigrationResponse->assertOk();
        }
    });

    it('tests integration stability under concurrent load', function () {
        // Create multiple components for concurrent testing
        $components = Component::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/components/grapejs-stability-test', [
            'component_ids' => $components->pluck('id')->toArray(),
            'concurrent_operations' => 5,
            'test_duration' => 10, // seconds
            'operations' => [
                'block_generation',
                'serialization',
                'trait_validation',
                'compatibility_check'
            ],
            'stress_test' => true
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'stability_score',
                    'error_rate',
                    'average_response_time',
                    'peak_memory_usage',
                    'failed_operations',
                    'performance_degradation',
                    'concurrent_operation_results'
                ]
            ]);

        $stabilityResults = $response->json('data');
        
        expect($stabilityResults['stability_score'])->toBeGreaterThan(90); // 90% stability minimum
        expect($stabilityResults['error_rate'])->toBeLessThan(0.05); // Less than 5% error rate
        expect($stabilityResults['performance_degradation'])->toBeLessThan(0.2); // Less than 20% degradation
        expect($stabilityResults['failed_operations'])->toBeLessThan(5); // Less than 5 failed operations
    });

    it('validates data integrity during complex operations', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Data Integrity Test',
                'subheading' => 'Testing data preservation',
                'complexData' => [
                    'nested' => [
                        'deeply' => [
                            'structured' => [
                                'data' => 'value',
                                'numbers' => [1, 2, 3, 4, 5],
                                'boolean' => true
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Calculate initial data checksum
        $initialChecksum = md5(json_encode($component->config));

        // Perform multiple operations that could affect data integrity
        $operations = [
            'serialize_deserialize' => function () use ($component) {
                $serializeResponse = $this->postJson('/api/components/serialize-to-grapejs', [
                    'component_ids' => [$component->id]
                ]);
                $serializeResponse->assertOk();
                
                $serializedData = $serializeResponse->json('data');
                
                $deserializeResponse = $this->postJson('/api/components/deserialize-from-grapejs', [
                    'grapejs_data' => $serializedData,
                    'validate_only' => true
                ]);
                $deserializeResponse->assertOk();
                
                return $deserializeResponse->json('data');
            },
            
            'version_create_restore' => function () use ($component) {
                $versionResponse = $this->postJson("/api/components/{$component->id}/versions", [
                    'description' => 'Integrity test version'
                ]);
                $versionResponse->assertOk();
                
                $versionId = $versionResponse->json('data.version.id');
                
                $restoreResponse = $this->postJson("/api/components/{$component->id}/versions/{$versionId}/restore");
                $restoreResponse->assertOk();
                
                return $restoreResponse->json('data');
            },
            
            'export_import' => function () use ($component) {
                $exportResponse = $this->postJson("/api/components/{$component->id}/export", [
                    'format' => 'grapejs'
                ]);
                $exportResponse->assertOk();
                
                $exportData = $exportResponse->json('data');
                
                $importResponse = $this->postJson('/api/components/import', [
                    'export_data' => $exportData,
                    'validate_only' => true
                ]);
                $importResponse->assertOk();
                
                return $importResponse->json('data');
            }
        ];

        $integrityResults = [];
        foreach ($operations as $operationName => $operation) {
            try {
                $result = $operation();
                
                // Refresh component to check for data changes
                $component->refresh();
                $postOperationChecksum = md5(json_encode($component->config));
                
                $integrityResults[$operationName] = [
                    'success' => true,
                    'data_integrity_maintained' => $postOperationChecksum === $initialChecksum,
                    'checksum_before' => $initialChecksum,
                    'checksum_after' => $postOperationChecksum
                ];
            } catch (Exception $e) {
                $integrityResults[$operationName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data_integrity_maintained' => false
                ];
            }
        }

        // Verify data integrity was maintained across all operations
        foreach ($integrityResults as $operationName => $result) {
            expect($result['success'])->toBeTrue("Operation '{$operationName}' should succeed");
            expect($result['data_integrity_maintained'])->toBeTrue("Data integrity should be maintained during '{$operationName}'");
        }
    });

    it('tests component schema evolution compatibility', function () {
        // Create components with different schema versions
        $schemaVersions = [
            '1.0' => [
                'headline' => 'Version 1.0 Component',
                'description' => 'Basic configuration'
            ],
            '1.1' => [
                'headline' => 'Version 1.1 Component',
                'description' => 'Added responsive support',
                'responsive' => [
                    'mobile' => ['padding' => '20px']
                ]
            ],
            '2.0' => [
                'headline' => 'Version 2.0 Component',
                'description' => 'Complete redesign',
                'responsive' => [
                    'desktop' => ['padding' => '40px'],
                    'tablet' => ['padding' => '30px'],
                    'mobile' => ['padding' => '20px']
                ],
                'accessibility' => [
                    'aria_labels' => true,
                    'keyboard_navigation' => true
                ]
            ]
        ];

        $components = [];
        foreach ($schemaVersions as $version => $config) {
            $components[$version] = Component::factory()->create([
                'tenant_id' => $this->tenant->id,
                'category' => 'hero',
                'version' => $version,
                'config' => $config
            ]);
        }

        // Test cross-version compatibility
        foreach ($components as $version => $component) {
            $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/schema-evolution", [
                'test_all_versions' => true,
                'validate_migration_paths' => true
            ]);

            $response->assertOk();
            
            $schemaResults = $response->json('data');
            expect($schemaResults['schema_compatible'])->toBeTrue("Schema version {$version} should be compatible");
            expect($schemaResults['migration_paths'])->toBeArray();
            
            // Test that component can be converted to GrapeJS format regardless of schema version
            $blockResponse = $this->getJson("/api/components/{$component->id}/grapejs-block");
            $blockResponse->assertOk();
        }
    });

    it('validates API response consistency across versions', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'is_active' => true
        ]);

        // Test multiple API endpoints for consistent response structure
        $apiEndpoints = [
            'block_generation' => "/api/components/{$component->id}/grapejs-block",
            'trait_validation' => "/api/components/{$component->id}/grapejs-traits/validate",
            'compatibility_check' => "/api/components/{$component->id}/grapejs-compatibility",
            'serialization' => '/api/components/serialize-to-grapejs'
        ];

        $responseStructures = [];
        foreach ($apiEndpoints as $endpointName => $endpoint) {
            if ($endpointName === 'serialization') {
                $response = $this->postJson($endpoint, ['component_ids' => [$component->id]]);
            } else {
                $response = $this->getJson($endpoint);
            }
            
            $response->assertOk();
            
            $responseData = $response->json();
            $responseStructures[$endpointName] = [
                'has_success_field' => isset($responseData['success']),
                'has_data_field' => isset($responseData['data']),
                'has_message_field' => isset($responseData['message']),
                'success_value' => $responseData['success'] ?? null,
                'data_structure' => $this->getDataStructure($responseData['data'] ?? [])
            ];
        }

        // Verify consistent response structure
        foreach ($responseStructures as $endpointName => $structure) {
            expect($structure['has_success_field'])->toBeTrue("Endpoint '{$endpointName}' should have 'success' field");
            expect($structure['has_data_field'])->toBeTrue("Endpoint '{$endpointName}' should have 'data' field");
            expect($structure['success_value'])->toBeTrue("Endpoint '{$endpointName}' should return success: true");
        }
    });

    it('tests error handling consistency', function () {
        // Test various error scenarios
        $errorScenarios = [
            'invalid_component_id' => [
                'endpoint' => '/api/components/99999/grapejs-block',
                'method' => 'GET',
                'expected_status' => 404
            ],
            'invalid_serialization_data' => [
                'endpoint' => '/api/components/serialize-to-grapejs',
                'method' => 'POST',
                'data' => ['component_ids' => [99999]],
                'expected_status' => 422
            ],
            'malformed_grapejs_data' => [
                'endpoint' => '/api/components/deserialize-from-grapejs',
                'method' => 'POST',
                'data' => ['grapejs_data' => 'invalid_data'],
                'expected_status' => 422
            ]
        ];

        foreach ($errorScenarios as $scenarioName => $scenario) {
            if ($scenario['method'] === 'POST') {
                $response = $this->postJson($scenario['endpoint'], $scenario['data'] ?? []);
            } else {
                $response = $this->getJson($scenario['endpoint']);
            }
            
            $response->assertStatus($scenario['expected_status']);
            
            $responseData = $response->json();
            expect($responseData)->toHaveKey('success', false);
            expect($responseData)->toHaveKey('message');
            
            // Verify error message is descriptive
            expect($responseData['message'])->not->toBeEmpty("Error scenario '{$scenarioName}' should have descriptive message");
        }
    });
});

describe('GrapeJS Performance Regression Testing', function () {
    it('monitors performance degradation over time', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'is_active' => true
        ]);

        // Simulate performance baseline
        $baselineMetrics = [
            'block_generation_time' => 45, // milliseconds
            'serialization_time' => 120,
            'memory_usage' => 2048, // KB
            'query_count' => 3
        ];

        // Test current performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $this->getJson("/api/components/{$component->id}/grapejs-block");
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $response->assertOk();
        
        $currentMetrics = [
            'block_generation_time' => ($endTime - $startTime) * 1000,
            'memory_usage' => ($endMemory - $startMemory) / 1024
        ];

        // Check for performance regression
        $performanceThreshold = 1.5; // 50% degradation threshold
        
        expect($currentMetrics['block_generation_time'])->toBeLessThan(
            $baselineMetrics['block_generation_time'] * $performanceThreshold,
            'Block generation time should not degrade significantly'
        );
        
        expect($currentMetrics['memory_usage'])->toBeLessThan(
            $baselineMetrics['memory_usage'] * $performanceThreshold,
            'Memory usage should not increase significantly'
        );
    });

    it('validates memory leak prevention', function () {
        $components = Component::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $initialMemory = memory_get_usage();
        
        // Perform multiple operations that could cause memory leaks
        for ($i = 0; $i < 5; $i++) {
            foreach ($components as $component) {
                $response = $this->getJson("/api/components/{$component->id}/grapejs-block");
                $response->assertOk();
            }
            
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be minimal (less than 5MB)
        expect($memoryIncrease)->toBeLessThan(5 * 1024 * 1024, 'Memory usage should not increase significantly over multiple operations');
    });
});

// Helper method to analyze data structure
function getDataStructure(array $data, int $depth = 0): array
{
    if ($depth > 3) { // Prevent infinite recursion
        return ['type' => 'deep_nested'];
    }
    
    $structure = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $structure[$key] = [
                'type' => 'array',
                'count' => count($value),
                'structure' => $this->getDataStructure($value, $depth + 1)
            ];
        } else {
            $structure[$key] = [
                'type' => gettype($value),
                'has_value' => !empty($value)
            ];
        }
    }
    
    return $structure;
}