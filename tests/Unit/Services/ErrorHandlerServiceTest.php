<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ErrorHandlerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErrorHandlerServiceTest extends TestCase
{
    use RefreshDatabase;

    private ErrorHandlerService $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandler = new ErrorHandlerService();
    }

    public function test_handle_generic_error()
    {
        $exception = new \Exception('Test error message');

        $result = $this->errorHandler->handleError($exception);

        $this->assertIsArray($result);
        $this->assertEquals('application_error', $result['type']);
        $this->assertEquals(500, $result['status_code']);
        $this->assertArrayHasKey('suggestions', $result);
    }

    public function test_handle_validation_error()
    {
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['field' => ''],
            ['field' => 'required']
        );

        $exception = new \Illuminate\Validation\ValidationException($validator);

        $result = $this->errorHandler->handleValidationError($exception);

        $this->assertEquals('validation_error', $result['type']);
        $this->assertEquals(422, $result['status_code']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_handle_template_error()
    {
        $exception = new \Exception('Template validation failed');
        $context = ['template_id' => 123, 'user_id' => 456];

        $result = $this->errorHandler->handleTemplateError($exception, $context);

        $this->assertEquals('template_error', $result['type']);
        $this->assertEquals('validation_error', $result['error_type']);
        $this->assertEquals(123, $result['template_id']);
        $this->assertEquals(422, $result['status_code']);
    }

    public function test_handle_database_error()
    {
        $exception = new \Illuminate\Database\QueryException(
            'SELECT * FROM test',
            [],
            new \Exception('Test database error')
        );

        $result = $this->errorHandler->handleDatabaseError($exception);

        $this->assertEquals('database_error', $result['type']);
        $this->assertEquals(500, $result['status_code']);
        $this->assertIsString($result['message']);
    }

    public function test_handle_file_upload_error()
    {
        $exception = new \Exception('File too large');
        $context = ['filename' => 'test.jpg', 'size' => 10000000];

        $result = $this->errorHandler->handleFileUploadError($exception, $context);

        $this->assertEquals('file_upload_error', $result['type']);
        $this->assertEquals(400, $result['status_code']);
        $this->assertEquals('test.jpg', $result['filename']);
    }

    public function test_generate_recovery_suggestions()
    {
        $exception = new \Illuminate\Database\QueryException(
            'SELECT * FROM test',
            [],
            [],
            new \Exception('Test database error')
        );

        $suggestions = $this->errorHandler->generateRecoverySuggestions($exception);

        $this->assertIsArray($suggestions);
        $this->assertContains('Check database connection and table structure', $suggestions);
    }

    public function test_get_error_stats()
    {
        $stats = $this->errorHandler->getErrorStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_errors', $stats);
        $this->assertArrayHasKey('errors_by_type', $stats);
        $this->assertArrayHasKey('recent_errors', $stats);
    }

    public function test_clear_error_stats()
    {
        // This test verifies the method exists and doesn't throw errors
        $this->errorHandler->clearErrorStats();
        $this->assertTrue(true);
    }

    public function test_categorize_template_error()
    {
        $reflection = new \ReflectionClass($this->errorHandler);
        $method = $reflection->getMethod('categorizeTemplateError');
        $method->setAccessible(true);

        $validationException = new \Exception('Template validation failed');
        $result = $method->invoke($this->errorHandler, $validationException);
        $this->assertEquals('validation_error', $result);

        $permissionException = new \Exception('Permission denied for template');
        $result = $method->invoke($this->errorHandler, $permissionException);
        $this->assertEquals('permission_error', $result);

        $generalException = new \Exception('Some other error');
        $result = $method->invoke($this->errorHandler, $generalException);
        $this->assertEquals('general_error', $result);
    }

    public function test_get_template_error_message()
    {
        $reflection = new \ReflectionClass($this->errorHandler);
        $method = $reflection->getMethod('getTemplateErrorMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->errorHandler, 'validation_error');
        $this->assertStringContainsString('validation failed', strtolower($result));

        $result = $method->invoke($this->errorHandler, 'unknown_error');
        $this->assertStringContainsString('error occurred', strtolower($result));
    }

    public function test_get_http_status_code()
    {
        $reflection = new \ReflectionClass($this->errorHandler);
        $method = $reflection->getMethod('getHttpStatusCode');
        $method->setAccessible(true);

        $validationException = new \Illuminate\Validation\ValidationException(
            \Illuminate\Support\Facades\Validator::make([], [])
        );
        $result = $method->invoke($this->errorHandler, $validationException);
        $this->assertEquals(422, $result);

        $notFoundException = new \Illuminate\Database\Eloquent\ModelNotFoundException();
        $result = $method->invoke($this->errorHandler, $notFoundException);
        $this->assertEquals(404, $result);

        $genericException = new \Exception('Test');
        $result = $method->invoke($this->errorHandler, $genericException);
        $this->assertEquals(500, $result);
    }

    public function test_sanitize_sql()
    {
        $reflection = new \ReflectionClass($this->errorHandler);
        $method = $reflection->getMethod('sanitizeSql');
        $method->setAccessible(true);

        $sql = "SELECT * FROM users WHERE email = 'test@example.com' AND password = 'secret123'";
        $result = $method->invoke($this->errorHandler, $sql);

        $this->assertStringNotContainsString('test@example.com', $result);
        $this->assertStringNotContainsString('secret123', $result);
        $this->assertStringContainsString('[HIDDEN]', $result);
    }
}