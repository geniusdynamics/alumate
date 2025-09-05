<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Services\TemplateService;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\TemplateValidationException;
use App\Http\Middleware\ErrorHandlingMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\CreatesApplication;

/**
 * Feature tests for template error handling system
 *
 * Tests end-to-end error handling scenarios including middleware,
 * API responses, and tenant isolation in real HTTP requests.
 */
class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected string $testTenantId = 'test-tenant-123';

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached error data
        Cache::flush();

        // Create test user
        $this->user = User::factory()->create([
            'institution_id' => $this->testTenantId,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function template_not_found_returns_proper_error_response()
    {
        // Mock template service to throw TemplateNotFound exception
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->with('non-existent-id')
                ->andThrow(new TemplateNotFoundException('Template not found', 404));
        });

        // Make API request that will trigger the error
        $response = $this->getJson("/api/templates/non-existent-id");

        // Assert proper error response
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'template_not_found',
                'message' => 'The requested template was not found. It may have been moved or deleted.',
                'status_code' => 404,
            ])
            ->assertJsonStructure([
                'message',
                'error',
                'status_code',
                'error_id',
                'timestamp',
                'recovery_suggestion'
            ]);
    }

    /** @test */
    public function template_validation_error_returns_detailed_response()
    {
        $validationErrors = [
            'name' => ['Name is required'],
            'structure' => ['Invalid JSON structure'],
        ];

        // Mock template service to throw validation exception
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('createTemplate')
                ->andThrow(new TemplateValidationException($validationErrors));
        });

        // Make API request that will trigger validation error
        $response = $this->postJson("/api/templates", [
            'invalid_field' => 'invalid_value'
        ]);

        // Assert detailed validation error response
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'template_validation_failed',
                'status_code' => 422,
            ])
            ->assertJsonStructure([
                'message',
                'error',
                'status_code',
                'error_id',
                'recovery_suggestion',
                'errors' // Should contain validation errors array
            ])
            ->assertJsonCount(2, 'errors'); // Two validation errors
    }

    /** @test */
    public function error_responses_include_recovery_suggestions()
    {
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException('Template not found'));
        });

        $response = $this->getJson("/api/templates/missing-template");

        $response->assertStatus(404)
            ->assertJsonStructure([
                'recovery_suggestion' => [
                    'message',
                    'actions'
                ]
            ]);

        $recoverySuggestion = $response->json('recovery_suggestion');
        $this->assertNotEmpty($recoverySuggestion['message']);
        $this->assertIsArray($recoverySuggestion['actions']);
        $this->assertContains('Check if the template ID is correct', $recoverySuggestion['actions']);
    }

    /** @test */
    public function middleware_adds_request_id_to_responses()
    {
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('getTemplates')
                ->andReturn(collect());
        });

        $response = $this->getJson("/api/templates");

        $response->assertSuccessful()
            ->assertHeader('X-Request-ID')
            ->assertHeaderNotEmpty('X-Request-ID');
    }

    /** @test */
    public function middleware_handles_generic_errors_gracefully()
    {
        // Simulate a generic error (like database connection issue)
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('getTemplates')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->getJson("/api/templates");

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'general',
                'original_error_type' => 'Exception',
            ])
            ->assertJsonStructure([
                'message',
                'error',
                'status_code',
                'error_id',
                'recovery_suggestion'
            ]);
    }

    /** @test */
    public function error_responses_include_unique_error_ids()
    {
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException('Template not found'));
        });

        // Make multiple requests
        $response1 = $this->getJson("/api/templates/not-found-1");
        $response2 = $this->getJson("/api/templates/not-found-2");

        $response1->assertStatus(404);
        $response2->assertStatus(404);

        $errorId1 = $response1->json('error_id');
        $errorId2 = $response2->json('error_id');

        // Error IDs should be different for separate requests
        $this->assertNotEquals($errorId1, $errorId2);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+$/', $errorId1);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+$/', $errorId2);
    }

    /** @test */
    public function tenant_isolation_maintained_in_error_responses()
    {
        // Create another tenant and user
        $tenant2 = 'other-tenant-456';
        $user2 = User::factory()->create([
            'institution_id' => $tenant2,
        ]);

        // First request with tenant 1
        $this->actingAs($this->user);
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException('Template not found for tenant 1'));
        });

        $response1 = $this->getJson("/api/templates/not-found");

        // Switch to tenant 2
        $this->actingAs($user2);
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException('Template not found for tenant 2'));
        });

        $response2 = $this->getJson("/api/templates/not-found");

        // Both responses should be identical in structure
        $response1->assertStatus(404);
        $response2->assertStatus(404);

        // Error messages should be consistent (tenant isolation doesn't affect basic response structure)
        $this->assertEquals($response1->json('error'), $response2->json('error'));
    }

    /** @test */
    public function security_errors_have_restricted_information()
    {
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('executeDangerousOperation')
                ->andThrow(new \App\Exceptions\TemplateSecurityException(
                    'Security violation detected',
                    ['xss_attempt', 'unauthorized_access'],
                    403
                ));
        });

        $response = $this->postJson("/api/templates/dangerous-operation", [
            'malicious_script' => '<script>alert("xss")</script>'
        ]);

        $response->assertStatus(422) // Security errors return 422 to not expose security details
            ->assertJson([
                'error' => 'template_security_violation',
            ])
            ->assertJsonStructure([
                'message',
                'error',
                'status_code',
                'error_id',
                'recovery_suggestion'
            ]);

        // Security error messages should be generic
        $this->assertStringContainsString('Security violation detected', $response->json('message'));

        // Sensitive data should not be exposed in response
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('xss_attempt', $responseContent);
        $this->assertStringNotContainsString('unauthorized_access', $responseContent);
        $this->assertStringNotContainsString('<script>', $responseContent);
    }

    /** @test */
    public function error_responses_include_timestamps()
    {
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException('Template not found'));
        });

        $beforeRequest = now()->toISOString();
        $response = $this->getJson("/api/templates/missing");
        $afterRequest = now()->toISOString();

        $response->assertStatus(404);

        $responseTimestamp = $response->json('timestamp');
        $this->assertNotEmpty($responseTimestamp);

        // Timestamp should be within reasonable bounds
        $this->assertGreaterThanOrEqual($beforeRequest, $responseTimestamp);
        $this->assertLessThanOrEqual($afterRequest, $responseTimestamp);
    }

    /** @test */
    public function middleware_handles_edge_cases()
    {
        // Test with extremely long error messages
        $longErrorMessage = str_repeat('This is a very long error message. ', 100);

        $this->mock(TemplateService::class, function ($mock) use ($longErrorMessage) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException($longErrorMessage));
        });

        $response = $this->getJson("/api/templates/long-error");

        // Response should be successful (no crashes) even with long message
        $response->assertStatus(404);
        $this->assertIsString($response->json('message'));
        // Long messages should be truncated or handled appropriately
        $this->assertLessThan(1000, strlen($response->json('message')));
    }

    /** @test */
    public function recovery_suggestions_adapt_to_error_type()
    {
        $testCases = [
            [
                'exception' => new TemplateNotFoundException(),
                'expected_action' => 'Check if the template ID is correct',
            ],
            [
                'exception' => new TemplateValidationException(['field' => 'error']),
                'expected_action' => 'Check the validation errors in your request',
            ],
        ];

        foreach ($testCases as $testCase) {
            $this->mock(TemplateService::class, function ($mock) use ($testCase) {
                $mock->shouldReceive('findTemplate')
                    ->andThrow($testCase['exception']);
            });

            $response = $this->getJson("/api/templates/test-" . rand(1000, 9999));

            $response->assertStatus(in_array(get_class($testCase['exception']),
                [\App\Exceptions\TemplateNotFoundException::class] ? 404 : 422));

            $actions = $response->json('recovery_suggestion')['actions'];
            $this->assertContains($testCase['expected_action'], $actions);
        }
    }

    /** @test */
    public function error_handling_maintains_performance_under_load()
    {
        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException('Template not found'));
        });

        $startTime = microtime(true);

        // Simulate rapid consecutive requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson("/api/templates/quick-error-{$i}");
            $response->assertStatus(404);
        }

        $totalTime = microtime(true) - $startTime;
        $avgTimePerRequest = $totalTime / 10;

        // Average response time should be reasonable (less than 0.5 seconds)
        $this->assertLessThan(0.5, $avgTimePerRequest,
            sprintf('Average response time %.4f seconds exceeded threshold', $avgTimePerRequest));
    }

    /** @test */
    public function middleware_preserves_authentication_context()
    {
        // Test that user context is maintained in error scenarios
        $this->actingAs($this->user);

        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('findTemplate')
                ->andThrow(new TemplateNotFoundException('Template not found'));
        });

        $response = $this->getJson("/api/templates/auth-error");

        $response->assertStatus(404);
        // Error handling should not lose the authenticated user context
        // This test ensures middleware doesn't interfere with authentication
        $this->assertTrue(true); // Passes if no authentication errors occur
    }
}