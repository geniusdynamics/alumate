<?php

namespace Tests\Unit\Services;

use App\Models\EmailSequence;
use App\Models\SequenceEmail;
use App\Models\SequenceEnrollment;
use App\Models\Lead;
use App\Models\EmailTemplate;
use App\Services\EmailSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

/**
 * Unit tests for EmailSequenceService
 */
class EmailSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailSequenceService $service;
    private EmailSequence $sequence;
    private Lead $lead;
    private EmailTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock tenant
        $tenant = Mockery::mock('alias:App\Models\Tenant');
        $tenant->id = 1;
        $this->app->instance('tenant', $tenant);

        // Create service instance
        $this->service = new EmailSequenceService();

        // Create test data
        $this->lead = Lead::factory()->create(['tenant_id' => 1]);
        $this->template = EmailTemplate::factory()->create(['tenant_id' => 1]);

        // Mock Cache facade
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });
        Cache::shouldReceive('forget')->andReturn(true);
    }

    /**
     * Test service instantiation
     */
    public function test_service_can_be_instantiated()
    {
        $this->assertInstanceOf(EmailSequenceService::class, $this->service);
    }

    /**
     * Test creating a sequence
     */
    public function test_create_sequence()
    {
        $sequenceData = [
            'name' => 'Welcome Sequence',
            'description' => 'New user onboarding',
            'audience_type' => 'individual',
            'trigger_type' => 'form_submission',
            'is_active' => true,
        ];

        $sequence = $this->service->createSequence($sequenceData);

        $this->assertInstanceOf(EmailSequence::class, $sequence);
        $this->assertEquals('Welcome Sequence', $sequence->name);
        $this->assertEquals('individual', $sequence->audience_type);
        $this->assertEquals('form_submission', $sequence->trigger_type);
        $this->assertEquals(1, $sequence->tenant_id);
    }

    /**
     * Test creating sequence with emails
     */
    public function test_create_sequence_with_emails()
    {
        $sequenceData = [
            'name' => 'Test Sequence',
            'audience_type' => 'individual',
            'trigger_type' => 'manual',
            'is_active' => true,
            'emails' => [
                [
                    'template_id' => $this->template->id,
                    'subject_line' => 'Welcome Email',
                    'delay_hours' => 0,
                    'send_order' => 1,
                ],
                [
                    'template_id' => $this->template->id,
                    'subject_line' => 'Follow Up',
                    'delay_hours' => 24,
                    'send_order' => 2,
                ],
            ],
        ];

        $sequence = $this->service->createSequence($sequenceData);

        $this->assertEquals(2, $sequence->emails()->count());
        $emails = $sequence->emails->sortBy('send_order');

        $this->assertEquals('Welcome Email', $emails->first()->subject_line);
        $this->assertEquals(0, $emails->first()->delay_hours);
        $this->assertEquals('Follow Up', $emails->last()->subject_line);
        $this->assertEquals(24, $emails->last()->delay_hours);
    }

    /**
     * Test updating a sequence
     */
    public function test_update_sequence()
    {
        $sequence = EmailSequence::factory()->create([
            'tenant_id' => 1,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $updatedSequence = $this->service->updateSequence($sequence->id, $updateData);

        $this->assertEquals('Updated Name', $updatedSequence->name);
        $this->assertEquals('Updated description', $updatedSequence->description);
    }

    /**
     * Test deleting a sequence
     */
    public function test_delete_sequence()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);

        $result = $this->service->deleteSequence($sequence->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('email_sequences', ['id' => $sequence->id]);
    }

    /**
     * Test getting sequence by ID
     */
    public function test_get_sequence_by_id()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);

        $retrievedSequence = $this->service->getSequenceById($sequence->id);

        $this->assertInstanceOf(EmailSequence::class, $retrievedSequence);
        $this->assertEquals($sequence->id, $retrievedSequence->id);
    }

    /**
     * Test getting sequence by ID throws exception for non-existent sequence
     */
    public function test_get_sequence_by_id_throws_exception_for_non_existent()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->getSequenceById(999999);
    }

    /**
     * Test getting all sequences
     */
    public function test_get_all_sequences()
    {
        EmailSequence::factory()->count(3)->create(['tenant_id' => 1]);

        $sequences = $this->service->getAllSequences();

        $this->assertCount(3, $sequences);
    }

    /**
     * Test enrolling lead in sequence
     */
    public function test_enroll_lead_in_sequence()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);

        $enrollment = $this->service->enrollLead($sequence->id, $this->lead->id);

        $this->assertInstanceOf(SequenceEnrollment::class, $enrollment);
        $this->assertEquals($sequence->id, $enrollment->sequence_id);
        $this->assertEquals($this->lead->id, $enrollment->lead_id);
        $this->assertEquals('active', $enrollment->status);
        $this->assertEquals(0, $enrollment->current_step);
    }

    /**
     * Test enrolling already enrolled lead throws exception
     */
    public function test_enroll_already_enrolled_lead_throws_exception()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);
        SequenceEnrollment::factory()->create([
            'sequence_id' => $sequence->id,
            'lead_id' => $this->lead->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Lead is already enrolled in this sequence');

        $this->service->enrollLead($sequence->id, $this->lead->id);
    }

    /**
     * Test processing sequence progression
     */
    public function test_process_sequence_progression()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);
        SequenceEmail::factory()->create([
            'sequence_id' => $sequence->id,
            'send_order' => 1,
            'delay_hours' => 0,
        ]);

        $enrollment = SequenceEnrollment::factory()->create([
            'sequence_id' => $sequence->id,
            'lead_id' => $this->lead->id,
            'current_step' => 0,
        ]);

        $result = $this->service->processSequenceProgression($enrollment->id);

        $this->assertTrue($result);
        $enrollment->refresh();
        $this->assertEquals(1, $enrollment->current_step);
    }

    /**
     * Test pausing enrollment
     */
    public function test_pause_enrollment()
    {
        $enrollment = SequenceEnrollment::factory()->create([
            'status' => 'active',
        ]);

        $result = $this->service->pauseEnrollment($enrollment->id);

        $this->assertTrue($result);
        $enrollment->refresh();
        $this->assertEquals('paused', $enrollment->status);
    }

    /**
     * Test resuming enrollment
     */
    public function test_resume_enrollment()
    {
        $enrollment = SequenceEnrollment::factory()->create([
            'status' => 'paused',
        ]);

        $result = $this->service->resumeEnrollment($enrollment->id);

        $this->assertTrue($result);
        $enrollment->refresh();
        $this->assertEquals('active', $enrollment->status);
    }

    /**
     * Test unsubscribing from sequence
     */
    public function test_unsubscribe_from_sequence()
    {
        $enrollment = SequenceEnrollment::factory()->create([
            'status' => 'active',
        ]);

        $result = $this->service->unsubscribeFromSequence($enrollment->id);

        $this->assertTrue($result);
        $enrollment->refresh();
        $this->assertEquals('unsubscribed', $enrollment->status);
    }

    /**
     * Test validating sequence configuration
     */
    public function test_validate_sequence_configuration()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);

        $result = $this->service->validateSequenceConfiguration($sequence->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
    }

    /**
     * Test getting sequence statistics
     */
    public function test_get_sequence_stats()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);

        SequenceEnrollment::factory()->count(5)->create([
            'sequence_id' => $sequence->id,
            'status' => 'completed',
        ]);

        SequenceEnrollment::factory()->count(3)->create([
            'sequence_id' => $sequence->id,
            'status' => 'active',
        ]);

        $stats = $this->service->getSequenceStats($sequence->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enrollments', $stats);
        $this->assertArrayHasKey('performance', $stats);
        $this->assertEquals(8, $stats['enrollments']['total']);
        $this->assertEquals(5, $stats['enrollments']['completed']);
        $this->assertEquals(3, $stats['enrollments']['active']);
    }

    /**
     * Test duplicating sequence
     */
    public function test_duplicate_sequence()
    {
        $originalSequence = EmailSequence::factory()->create([
            'tenant_id' => 1,
            'name' => 'Original Sequence',
        ]);

        SequenceEmail::factory()->count(2)->create([
            'sequence_id' => $originalSequence->id,
        ]);

        $duplicate = $this->service->duplicateSequence($originalSequence->id, 'Duplicated Sequence');

        $this->assertInstanceOf(EmailSequence::class, $duplicate);
        $this->assertEquals('Duplicated Sequence', $duplicate->name);
        $this->assertEquals(2, $duplicate->emails()->count());
        $this->assertFalse($duplicate->is_active); // Should be inactive by default
    }

    /**
     * Test sequence data validation
     */
    public function test_validate_sequence_data()
    {
        // Valid data should not throw exception
        $validData = [
            'name' => 'Test Sequence',
            'audience_type' => 'individual',
            'trigger_type' => 'manual',
        ];

        // This should not throw an exception
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateSequenceData');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $validData, true);
        $this->assertNull($result);
    }

    /**
     * Test sequence data validation with invalid data
     */
    public function test_validate_sequence_data_with_invalid_data()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateSequenceData');
        $method->setAccessible(true);

        $invalidData = [
            'name' => '', // Invalid: empty name
            'audience_type' => 'invalid_type', // Invalid: wrong audience type
        ];

        $method->invoke($this->service, $invalidData, true);
    }

    /**
     * Test cache clearing
     */
    public function test_cache_clearing()
    {
        $sequence = EmailSequence::factory()->create(['tenant_id' => 1]);

        // Mock cache expectations
        Cache::shouldReceive('forget')
            ->with('email_sequences_sequence_' . $sequence->id)
            ->once();

        Cache::shouldReceive('forget')
            ->with('email_sequences_all')
            ->once();

        // Access private method via reflection
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('clearSequenceCache');
        $method->setAccessible(true);

        $method->invoke($this->service, $sequence->id);
    }

    /**
     * Test database transaction rollback on error
     */
    public function test_database_transaction_rollback_on_error()
    {
        // Mock DB to throw exception
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        Log::shouldReceive('error')->once();

        $this->expectException(\Exception::class);

        // This should trigger a rollback
        $this->service->createSequence([
            'name' => 'Test',
            'audience_type' => 'individual',
            'trigger_type' => 'manual',
        ]);
    }

    /**
     * Test tenant isolation
     */
    public function test_tenant_isolation()
    {
        // Create sequence for different tenant
        $otherTenantSequence = EmailSequence::factory()->create(['tenant_id' => 2]);

        // Should not be able to access sequence from different tenant
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->getSequenceById($otherTenantSequence->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}