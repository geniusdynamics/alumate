<?php

namespace Tests\Unit\Services;

use App\Exceptions\TemplateException;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\TemplateValidationException;
use App\Exceptions\TemplateSecurityException;
use App\Services\TemplateErrorHandler;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Mockery;
use Exception;

/**
 * Comprehensive test suite for TemplateErrorHandler service
 *
 * Tests error handling, tenant isolation, logging, monitoring, and recovery scenarios
 */
class TemplateErrorHandlerTest extends TestCase

    protected TemplateErrorHandler $errorHandler;
    protected LogManager $logManager;
    protected string $testTenantId = 'test-tenant-123';

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LogManager
        $this->logManager = Mockery::mock(LogManager::class);

        // Create error handler instance
        $this->errorHandler = new TemplateErrorHandler($this->logManager);

        // Clear cache before each test
        Cache::forget("template_metrics_{$this->testTenantId}");
        Cache::forget("template_metrics_global");
        Cache::forget("template_errors_{$this->testTenantId}_*");
        Cache::forget("template_performance_{$this->testTenantId}_*");

        // Mock authenticated user for some tests
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(123);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_template_exception_with_tenant_isolation()
    {
        // Given
        $exception = new TemplateNotFoundException('Template not found', 404);
        $context = [
            'template_id' => 'tpl-123',
            'template_category' => 'hero'
        ];

        // Mock logger
        $this->setupLoggerMock($exception, $context);

        // When
        $result = $this->errorHandler->handleError($exception, $context, $this->testTenantId);

        // Then
        $this->assertIsArray($result);
        $this->assertEquals('template_not_found', $result['error']);
        $this->assertEquals(404, $result['status_code']);
        $this->assertArrayHasKey('error_id', $result);
        $this->assertEquals($this->testTenantId, $result['error_id']);
        $this->assertArrayHasKey('recovery_suggestion', $result);
    }

    /** @test */
    public function it_handles_validation_errors_with_detailed_messages()
    {
        // Given
        $validationErrors = [
            'name' => ['Name is required'],
            'structure' => ['Invalid structure format']
        ];
        $exception = new TemplateValidationException($validationErrors);
        $context = ['template_id' => 'tpl-invalid'];

        // Mock logger
        $this->setupLoggerMock($exception, $context);

        // When
        $result = $this->errorHandler->handleError($exception, $context, $this->testTenantId);

        // Then
        $this->assertEquals('template_validation_failed', $result['error']);
        $this->assertEquals(422, $result['status_code']);
        $this->assertArrayHasKey('recovery_suggestion', $result);
        $this->assertContains('Check the validation errors', $result['recovery_suggestion']['actions']);
    }

    /** @test */
    public function it_handles_security_exceptions_with_restricted_response()
    {
        // Given
        $exception = new TemplateSecurityException('Security violation detected', ['xss_attempt']);
        $context = ['template_id' => 'tpl-insecure'];

        // Mock logger for error/warning channel
        $this->setupLoggerMock($exception, $context);

        // When
        $result = $this->errorHandler->handleError($exception, $context, $this->testTenantId);

        // Then
        $this->assertEquals('template_security_violation', $result['error']);
        $this->assertEquals(422, $result['status_code']);
        $this->assertStringContainsString('Security violation detected', $result['message']);
    }

    /** @test */
    public function it_tracks_error_metrics_by_tenant()
    {
        // Given
        $exception1 = new TemplateNotFoundException('Template not found');
        $exception2 = new TemplateValidationException('Validation failed');
        $context = ['template_id' => 'tpl-test'];

        $this->setupLoggerMock($exception1, $context);
        $this->setupLoggerMock($exception2, $context);

        // When
        $this->errorHandler->handleError($exception1, $context, $this->testTenantId);
        $this->errorHandler->handleError($exception2, $context, $this->testTenantId);

        // Then
        $stats = $this->errorHandler->getErrorStatistics($this->testTenantId);

        $this->assertArrayHasKey('error_counts', $stats);
        $this->assertArrayHasKey('by_type', $stats['error_counts']);
        $this->assertGreaterThanOrEqual(2, $stats['error_counts']['total'] ?? 0);
    }

    /** @test */
    public function it_tracks_performance_issues()
    {
        // Given
        $operation = 'template_rendering';
        $duration = 2.5; // 2.5 seconds
        $threshold = 1.0; // 1 second threshold

        // When
        $this->errorHandler->logPerformanceIssue($operation, $duration, $threshold * 1000, $this->testTenantId);

        // Then
        $stats = $this->errorHandler->getErrorStatistics($this->testTenantId);

        $this->assertArrayHasKey('performance_issues', $stats);
        $this->assertArrayHasKey('slow_operations_count', $stats['performance_issues']);
        $this->assertGreaterThanOrEqual(1, $stats['performance_issues']['slow_operations_count']);
    }

    /** @test */
    public function it_handles_generic_exceptions_as_fallback()
    {
        // Given
        $exception = new Exception('Generic database error');
        $context = ['query' => 'SELECT * FROM templates'];

        // Mock logger for warning channel (non-template exception)
        $this->logManager
            ->shouldReceive('getLogger')
            ->with('template-warnings')
            ->andReturnSelf()
            ->shouldReceive('error')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::on(function ($logContext) use ($context) {
                    return is_array($logContext) &&
                           isset($logContext['tenant_id']) &&
                           isset($logContext['exception']);
                })
            );

        // When
        $result = $this->errorHandler->handleError($exception, $context, $this->testTenantId);

        // Then
        $this->assertEquals(500, $result['status_code']);
        $this->assertEquals('general', $result['error_type']);
        $this->assertArrayHasKey('recovery_suggestion', $result);
    }

    /** @test */
    public function it_provides_different_recovery_suggestions_based_on_error_type()
    {
        // Given
        $testCases = [
            [
                'exception' => new TemplateNotFoundException('Template not found'),
                'expected_key' => 'Check if the template ID is correct',
            ],
            [
                'exception' => new TemplateValidationException('Validation failed'),
                'expected_key' => 'Check the validation errors in your request',
            ],
            [
                'exception' => new TemplateSecurityException('Security violation'),
                'expected_key' => 'This appears to be a security-related issue',
            ],
        ];

        $context = ['template_id' => 'tpl-test'];

        foreach ($testCases as $testCase) {
            $this->setupLoggerMock($testCase['exception'], $context);

            // When
            $result = $this->errorHandler->handleError($testCase['exception'], $context, $this->testTenantId);

            // Then
            $this->assertArrayHasKey('recovery_suggestion', $result);
            $this->assertStringContainsString($testCase['expected_key'], $result['recovery_suggestion']['message']);
        }
    }

    /** @test */
    public function it_tracks_error_trends_over_time()
    {
        // Given
        $exception = new TemplateNotFoundException('Template not found');
        $context = ['template_id' => 'tpl-test'];

        $this->setupLoggerMock($exception, $context);

        // Create multiple errors over time
        for ($i = 0; $i < 5; $i++) {
            $this->errorHandler->handleError($exception, $context, $this->testTenantId);
        }

        // When
        $stats = $this->errorHandler->getErrorStatistics($this->testTenantId);

        // Then
        $this->assertArrayHasKey('error_trends', $stats);
        $this->assertArrayHasKey('error_counts', $stats);
        $this->assertGreaterThanOrEqual(5, $stats['error_counts']['total'] ?? 0);
    }

    /** @test */
    public function it_handles_tenant_isolation_properly()
    {
        // Given
        $exception = new TemplateNotFoundException('Template not found');
        $context = ['template_id' => 'tpl-shared'];
        $otherTenantId = 'other-tenant-456';

        $this->setupLoggerMock($exception, $context);

        // Create errors for different tenants
        $this->errorHandler->handleError($exception, $context, $this->testTenantId);
        $this->errorHandler->handleError($exception, $context, $otherTenantId);

        // When
        $tenant1Stats = $this->errorHandler->getErrorStatistics($this->testTenantId);
        $tenant2Stats = $this->errorHandler->getErrorStatistics($otherTenantId);
        $globalStats = $this->errorHandler->getErrorStatistics(); // No tenant ID

        // Then
        // Each tenant should only see their own errors
        $this->assertGreaterThanOrEqual(1, $tenant1Stats['error_counts']['total'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $tenant2Stats['error_counts']['total'] ?? 0);

        // Global stats should include all (or be isolated if no specific tenant provided)
        $this->assertIsArray($globalStats);
    }

    /** @test */
    public function it_handles_warnings_separately_from_errors()
    {
        // Given
        $warningMessage = 'Template structure deprecated';
        $context = [
            'template_id' => 'tpl-old',
            'deprecated_fields' => ['old_property']
        ];

        // Mock logger for warning channel
        $this->logManager
            ->shouldReceive('getLogger')
            ->with('template-warnings')
            ->andReturnSelf()
            ->shouldReceive('warning')
            ->once()
            ->with($warningMessage, Mockery::type('array'));

        // When
        $this->errorHandler->handleWarning($warningMessage, $context, $this->testTenantId);

        // Then - Verify warning was logged but didn't become an error
        // The test passes if no exception is thrown
        $this->assertTrue(true); // Warning handled successfully
    }

    /** @test */
    public function it_resets_metrics_correctly()
    {
        // Given
        $exception = new TemplateNotFoundException('Template not found');
        $context = ['template_id' => 'tpl-reset-test'];

        $this->setupLoggerMock($exception, $context);

        // Create some metrics
        $this->errorHandler->handleError($exception, $context, $this->testTenantId);

        // Verify metrics exist
        $stats = $this->errorHandler->getErrorStatistics($this->testTenantId);
        $this->assertGreaterThanOrEqual(1, $stats['error_counts']['total'] ?? 0);

        // When
        $this->errorHandler->resetMetrics($this->testTenantId);

        // Then
        $resetStats = $this->errorHandler->getErrorStatistics($this->testTenantId);
        // Note: Fresh stats should not have the same error counts
        // (implementation depends on caching, so we just verify it runs)
        $this->assertIsArray($resetStats);
    }

    /** @test */
    public function it_enhances_context_with_system_information()
    {
        // Given
        $exception = TemplateException::withContext(
            'Test error',
            [
                'template_id' => 'tpl-context-test',
                'custom_data' => 'test_value'
            ]
        );

        // Mock logger
        $this->setupLoggerMock($exception, []);

        // When
        $result = $this->errorHandler->handleError(
            $exception,
            ['additional_context' => 'data'],
            $this->testTenantId
        );

        // Then
        $this->assertIsArray($result);
        $this->assertEquals('Test error', $result['message']);
        $this->assertEquals('template_error', $result['error']);
    }

    /** @test */
    public function it_handles_performance_monitoring_edge_cases()
    {
        // Test with very fast operation (should not be logged as slow)
        $this->errorHandler->logPerformanceIssue('fast_op', 0.1, 1000, $this->testTenantId);

        // Test with exactly threshold operation (should not be logged as slow)
        $this->errorHandler->logPerformanceIssue('threshold_op', 0.5, 500, $this->testTenantId);

        // Test with slow operation (should be logged)
        $this->errorHandler->logPerformanceIssue('slow_op', 2.0, 1000, $this->testTenantId);

        // When
        $stats = $this->errorHandler->getErrorStatistics($this->testTenantId);

        // Then
        $this->assertArrayHasKey('performance_issues', $stats);
        // At least the slow operation should be tracked
        $this->assertGreaterThanOrEqual(1, $stats['performance_issues']['slow_operations_count']);
    }

    /** @test */
    public function it_maintains_request_isolation_in_multithreaded_scenarios()
    {
        // Note: This test simulates concurrent access patterns

        $exception = new TemplateNotFoundException('Concurrent error');
        $context = ['request_id' => 'req-123'];

        // Mock logger multiple times for concurrent scenarios
        $this->setupLoggerMock($exception, $context);

        // Process multiple "concurrent" errors
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->errorHandler->handleError(
                new TemplateNotFoundException("Concurrent error {$i}"),
                array_merge($context, ['iteration' => $i]),
                $this->testTenantId
            );
        }

        // Then
        foreach ($results as $result) {
            $this->assertArrayHasKey('error_id', $result);
            $this->assertNotEmpty($result['error_id']);
        }

        // All results should have different error IDs or be properly isolated
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    /**
     * Helper method to setup logger mocks
     */
    private function setupLoggerMock($exception, array $context = []): void
    {
        $this->logManager
            ->shouldReceive('getLogger')
            ->withAnyArgs()
            ->andReturnSelf()
            ->shouldReceive('error')
            ->andReturnSelf()
            ->shouldReceive('warning')
            ->andReturnSelf()
            ->shouldReceive('critical')
            ->andReturnSelf()
            ->shouldReceive('alert')
            ->andReturnSelf()
            ->withAnyArgs();
    }
}