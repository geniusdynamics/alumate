<?php

namespace Tests\Feature;

use App\Services\Homepage\DeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeploymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private DeploymentService $deploymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deploymentService = new DeploymentService;
    }

    /** @test */
    public function it_can_log_deployment_start()
    {
        $deploymentData = [
            'version' => '2.0.0',
            'deployed_by' => 'test_user',
            'commit_hash' => 'abc123',
        ];

        $deploymentId = $this->deploymentService->logDeploymentStart($deploymentData);

        $this->assertNotEmpty($deploymentId);

        $deployment = DB::table('homepage_deployment_logs')
            ->where('deployment_id', $deploymentId)
            ->first();

        $this->assertNotNull($deployment);
        $this->assertEquals('pending', $deployment->status);
        $this->assertEquals('2.0.0', $deployment->version);
        $this->assertEquals('test_user', $deployment->deployed_by);
        $this->assertEquals('abc123', $deployment->commit_hash);
    }

    /** @test */
    public function it_can_update_deployment_status()
    {
        $deploymentId = $this->deploymentService->logDeploymentStart([
            'version' => '2.0.0',
        ]);

        $this->deploymentService->updateDeploymentStatus($deploymentId, 'in_progress');

        $deployment = DB::table('homepage_deployment_logs')
            ->where('deployment_id', $deploymentId)
            ->first();

        $this->assertEquals('in_progress', $deployment->status);
    }

    /** @test */
    public function it_can_complete_deployment_with_duration()
    {
        $deploymentId = $this->deploymentService->logDeploymentStart([
            'version' => '2.0.0',
        ]);

        // Simulate some time passing
        sleep(1);

        $this->deploymentService->updateDeploymentStatus($deploymentId, 'completed');

        $deployment = DB::table('homepage_deployment_logs')
            ->where('deployment_id', $deploymentId)
            ->first();

        $this->assertEquals('completed', $deployment->status);
        $this->assertNotNull($deployment->completed_at);
        $this->assertGreaterThan(0, $deployment->duration_seconds);
    }

    /** @test */
    public function it_can_run_homepage_migrations()
    {
        $deploymentId = $this->deploymentService->logDeploymentStart([
            'version' => '2.0.0',
        ]);

        $results = $this->deploymentService->runHomepageMigrations($deploymentId);

        $this->assertIsArray($results);

        // Check that deployment status was updated
        $deployment = DB::table('homepage_deployment_logs')
            ->where('deployment_id', $deploymentId)
            ->first();

        $this->assertNotNull($deployment->migration_results);
    }

    /** @test */
    public function it_can_verify_deployment()
    {
        $deploymentId = $this->deploymentService->logDeploymentStart([
            'version' => '2.0.0',
        ]);

        $results = $this->deploymentService->verifyDeployment($deploymentId);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('database', $results);
        $this->assertArrayHasKey('assets', $results);
        $this->assertArrayHasKey('routes', $results);
        $this->assertArrayHasKey('performance', $results);

        // Check that each verification has a status
        foreach ($results as $verification) {
            $this->assertArrayHasKey('status', $verification);
            $this->assertContains($verification['status'], ['passed', 'failed']);
        }
    }

    /** @test */
    public function health_check_endpoint_returns_correct_structure()
    {
        $response = $this->get('/health-check/homepage');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database' => ['status', 'message'],
                'cache' => ['status', 'message'],
                'storage' => ['status', 'message'],
                'homepage_assets' => ['status', 'message'],
                'homepage_routes' => ['status', 'message'],
            ],
            'version',
            'environment',
        ]);
    }

    /** @test */
    public function health_check_database_verification_works()
    {
        $response = $this->get('/health-check/homepage');

        $data = $response->json();
        $this->assertEquals('healthy', $data['checks']['database']['status']);
        $this->assertArrayHasKey('response_time', $data['checks']['database']);
    }

    /** @test */
    public function health_check_cache_verification_works()
    {
        $response = $this->get('/health-check/homepage');

        $data = $response->json();
        $this->assertEquals('healthy', $data['checks']['cache']['status']);
        $this->assertArrayHasKey('driver', $data['checks']['cache']);
    }

    /** @test */
    public function health_check_storage_verification_works()
    {
        $response = $this->get('/health-check/homepage');

        $data = $response->json();
        $this->assertEquals('healthy', $data['checks']['storage']['status']);
        $this->assertArrayHasKey('driver', $data['checks']['storage']);
    }

    /** @test */
    public function health_check_routes_verification_works()
    {
        $response = $this->get('/health-check/homepage');

        $data = $response->json();
        $this->assertEquals('healthy', $data['checks']['homepage_routes']['status']);
        $this->assertArrayHasKey('routes_count', $data['checks']['homepage_routes']);
    }

    /** @test */
    public function deployment_handles_migration_failures()
    {
        $deploymentId = $this->deploymentService->logDeploymentStart([
            'version' => '2.0.0',
        ]);

        // Mock a migration failure by using an invalid migration path
        try {
            Artisan::call('migrate', [
                '--path' => 'invalid/path',
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            $this->deploymentService->updateDeploymentStatus($deploymentId, 'failed', [
                'error_message' => $e->getMessage(),
            ]);
        }

        $deployment = DB::table('homepage_deployment_logs')
            ->where('deployment_id', $deploymentId)
            ->first();

        $this->assertEquals('failed', $deployment->status);
        $this->assertNotNull($deployment->error_message);
    }

    /** @test */
    public function security_headers_middleware_adds_correct_headers()
    {
        $response = $this->get('/');

        // Check for security headers
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options');
        $response->assertHeader('X-XSS-Protection');
        $response->assertHeader('Referrer-Policy');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies');
        $response->assertHeader('X-Download-Options');
    }

    /** @test */
    public function performance_metrics_can_be_recorded()
    {
        DB::table('homepage_performance_metrics')->insert([
            'metric_type' => 'page_load',
            'metric_name' => 'homepage_load_time',
            'value' => 1250.5,
            'unit' => 'ms',
            'environment' => 'testing',
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metric = DB::table('homepage_performance_metrics')
            ->where('metric_name', 'homepage_load_time')
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals('page_load', $metric->metric_type);
        $this->assertEquals(1250.5, $metric->value);
        $this->assertEquals('ms', $metric->unit);
    }
}
