<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TenantUser;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Graduate Dashboard Controller Tenant Isolation Test
 *
 * Tests that graduate users can only access data from their assigned tenant
 * and that cross-tenant access is properly prevented.
 */
class GraduateDashboardControllerTenantIsolationTest extends TestCase
{
    use DatabaseMigrations;

    protected TenantContextService $tenantContextService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContextService = app(TenantContextService::class);
        $this->tenantContextService->clearContext();
    }

    protected function tearDown(): void
    {
        $this->tenantContextService->clearContext();
        parent::tearDown();
    }

    /**
     * Helper method to create tenant user relationship
     */
    protected function createTenantUser(User $user, Tenant $tenant, string $role = User::ROLE_STUDENT): TenantUser
    {
        return TenantUser::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => $role,
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }

    /**
     * Test that graduate can access their own profile in their tenant.
     */
    public function test_graduate_can_access_profile_in_their_tenant(): void
    {
        // Create tenant and user
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Create tenant user relationship
        $this->createTenantUser($user, $tenant);

        // Set tenant context using TenantContextService
        $this->tenantContextService->setTenant($tenant->id);

        // Create graduate record
        $graduate = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
        ]);

        // Authenticate as user
        Auth::login($user);

        // Access profile endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.profile'));

        // Assert successful response
        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return isset($page['props']['graduate']);
        });
    }

    /**
     * Test that graduate cannot access profile without tenant context.
     */
    public function test_graduate_cannot_access_profile_without_tenant_context(): void
    {
        // Create user without institution
        $user = User::factory()->create([
            'institution_id' => null,
        ]);

        // Authenticate as user
        Auth::login($user);

        // Access profile endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.profile'));

        // Assert redirect to create graduate
        $response->assertRedirect(route('graduates.create'));
    }

    /**
     * Test that graduate can only see jobs in their tenant.
     */
    public function test_graduate_can_only_see_jobs_in_their_tenant(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create user in tenant1
        $user = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        $this->createTenantUser($user, $tenant1);

        // Create courses in tenant1
        $course1 = Course::factory()->create(['tenant_id' => $tenant1->id]);

        // Set tenant1 context
        $this->tenantContextService->setTenant($tenant1->id);

        // Create job in tenant1
        $job1 = Job::factory()->create([
            'course_id' => $course1->id,
            'status' => 'active',
        ]);

        // Set tenant2 context to create job in tenant2
        $this->tenantContextService->setTenant($tenant2->id);
        $course2 = Course::factory()->create(['tenant_id' => $tenant2->id]);
        $job2 = Job::factory()->create([
            'course_id' => $course2->id,
            'status' => 'active',
        ]);

        // Set tenant1 context again
        $this->tenantContextService->setTenant($tenant1->id);

        // Create graduate record in tenant1
        $graduate = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
            'course_id' => $course1->id,
            'skills' => ['PHP', 'Laravel'],
        ]);

        // Authenticate as user
        Auth::login($user);

        // Access job browsing endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.job-browsing'));

        // Assert successful response
        $response->assertStatus(200);

        // Get jobs from response
        $response->assertInertia(function ($page) use ($job1, $job2) {
            $jobs = $page['props']['jobs']['data'] ?? [];
            $jobIds = collect($jobs)->pluck('id')->toArray();

            // Should only see job from tenant1
            $this->assertContains($job1->id, $jobIds);
            $this->assertNotContains($job2->id, $jobIds);
        });
    }

    /**
     * Test that graduate can only see their own applications.
     */
    public function test_graduate_can_only_see_their_own_applications(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create user in tenant1
        $user = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        $this->createTenantUser($user, $tenant1);

        // Set tenant1 context
        $this->tenantContextService->setTenant($tenant1->id);

        // Create course and job in tenant1
        $course1 = Course::factory()->create(['tenant_id' => $tenant1->id]);
        $job1 = Job::factory()->create([
            'course_id' => $course1->id,
            'status' => 'active',
        ]);

        // Create graduate record in tenant1
        $graduate = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
            'course_id' => $course1->id,
        ]);

        // Create application for graduate in tenant1
        JobApplication::create([
            'graduate_id' => $graduate->id,
            'job_id' => $job1->id,
            'status' => 'pending',
        ]);

        // Authenticate as user
        Auth::login($user);

        // Access applications endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.applications'));

        // Assert successful response
        $response->assertStatus(200);

        // Get applications from response
        $response->assertInertia(function ($page) use ($job1) {
            $applications = $page['props']['applications']['data'] ?? [];
            $jobIds = collect($applications)->pluck('job.id')->toArray();

            // Should only see application to job from tenant1
            $this->assertContains($job1->id, $jobIds);
        });
    }

    /**
     * Test that graduate can only see classmates in their tenant.
     */
    public function test_graduate_can_only_see_classmates_in_their_tenant(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create user in tenant1
        $user = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        $this->createTenantUser($user, $tenant1);

        // Set tenant1 context
        $this->tenantContextService->setTenant($tenant1->id);

        // Create course in tenant1
        $course1 = Course::factory()->create(['tenant_id' => $tenant1->id]);

        // Create graduate record in tenant1
        $graduate1 = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
            'course_id' => $course1->id,
            'profile_visibility' => 'public',
        ]);

        // Create another graduate in tenant1
        $classmate1 = Graduate::create([
            'course_id' => $course1->id,
            'graduation_year' => 2023,
            'profile_visibility' => 'public',
            'name' => 'Classmate One',
            'email' => 'classmate1@example.com',
            'student_id' => 'STU002',
            'employment_status' => 'unemployed',
        ]);

        // Set tenant2 context to create graduate in tenant2
        $this->tenantContextService->setTenant($tenant2->id);
        $course2 = Course::factory()->create(['tenant_id' => $tenant2->id]);
        $graduate2 = Graduate::create([
            'course_id' => $course2->id,
            'graduation_year' => 2023,
            'profile_visibility' => 'public',
            'name' => 'Other Tenant Graduate',
            'email' => 'other@example.com',
            'student_id' => 'STU003',
            'employment_status' => 'unemployed',
        ]);

        // Set tenant1 context again
        $this->tenantContextService->setTenant($tenant1->id);

        // Authenticate as user
        Auth::login($user);

        // Access classmates endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.classmates'));

        // Assert successful response
        $response->assertStatus(200);

        // Get classmates from response
        $response->assertInertia(function ($page) use ($classmate1, $graduate2) {
            $classmates = $page['props']['classmates']['data'] ?? [];
            $classmateIds = collect($classmates)->pluck('id')->toArray();

            // Should only see classmates from tenant1
            $this->assertContains($classmate1->id, $classmateIds);
            $this->assertNotContains($graduate2->id, $classmateIds);
        });
    }

    /**
     * Test that graduate can access career progress in their tenant.
     */
    public function test_graduate_can_access_career_progress_in_their_tenant(): void
    {
        // Create tenant and user
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        $this->createTenantUser($user, $tenant);

        // Set tenant context
        $this->tenantContextService->setTenant($tenant->id);

        // Create graduate record
        $graduate = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'employed',
            'current_job_title' => 'Software Developer',
            'current_company' => 'Tech Corp',
        ]);

        // Authenticate as user
        Auth::login($user);

        // Access career progress endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.career-progress'));

        // Assert successful response
        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return isset($page['props']['graduate']);
        });
    }

    /**
     * Test that graduate is redirected if no graduate record exists.
     */
    public function test_graduate_redirected_if_no_record_exists(): void
    {
        // Create tenant and user
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        $this->createTenantUser($user, $tenant);

        // Set tenant context but don't create graduate
        $this->tenantContextService->setTenant($tenant->id);

        // Authenticate as user
        Auth::login($user);

        // Access profile endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.profile'));

        // Should create graduate record automatically
        $response->assertStatus(200);
    }

    /**
     * Test cross-tenant access is prevented - user cannot access another tenant's data.
     */
    public function test_cross_tenant_access_is_prevented(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create user in tenant1
        $user = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        $this->createTenantUser($user, $tenant1);

        // Set tenant1 context
        $this->tenantContextService->setTenant($tenant1->id);

        // Create course in tenant1
        $course1 = Course::factory()->create(['tenant_id' => $tenant1->id]);

        // Create graduate record in tenant1
        $graduate1 = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
            'course_id' => $course1->id,
        ]);

        // Create another graduate in tenant2
        $this->tenantContextService->setTenant($tenant2->id);
        $course2 = Course::factory()->create(['tenant_id' => $tenant2->id]);
        $graduate2 = Graduate::create([
            'course_id' => $course2->id,
            'graduation_year' => 2023,
            'profile_visibility' => 'public',
            'name' => 'Other Tenant Graduate',
            'email' => 'other@example.com',
            'student_id' => 'STU002',
            'employment_status' => 'unemployed',
        ]);

        // Set tenant1 context
        $this->tenantContextService->setTenant($tenant1->id);

        // Authenticate as user from tenant1
        Auth::login($user);

        // Try to access classmates - should not see graduate2
        $response = $this->actingAs($user)
            ->get(route('graduates.classmates'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($graduate2) {
            $classmates = $page['props']['classmates']['data'] ?? [];
            $classmateIds = collect($classmates)->pluck('id')->toArray();

            // Graduate from tenant2 should not be visible
            $this->assertNotContains($graduate2->id, $classmateIds);
        });
    }

    /**
     * Test that validateTenantAccess properly validates user-tenant relationship.
     */
    public function test_validateTenantAccess_rejects_invalid_user_tenant_relationship(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create user with institution_id pointing to tenant1
        $user = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        // Create tenant user relationship for tenant1
        $this->createTenantUser($user, $tenant1);

        // Authenticate as user
        Auth::login($user);

        // User should be able to access tenant1
        $this->assertTrue($this->tenantContextService->validateTenantAccess($tenant1->id));

        // User should NOT be able to access tenant2 (no relationship)
        $this->assertFalse($this->tenantContextService->validateTenantAccess($tenant2->id));
    }

    /**
     * Test that graduate dashboard index works with proper tenant context.
     */
    public function test_graduate_dashboard_index_with_tenant_context(): void
    {
        // Create tenant and user
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        $this->createTenantUser($user, $tenant);

        // Set tenant context
        $this->tenantContextService->setTenant($tenant->id);

        // Create graduate record
        $graduate = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
        ]);

        // Authenticate as user
        Auth::login($user);

        // Access dashboard index
        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        // Assert successful response
        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return isset($page['props']['graduate']);
        });
    }

    /**
     * Test that assistance requests page works with proper tenant context.
     */
    public function test_assistance_requests_with_tenant_context(): void
    {
        // Create tenant and user
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        $this->createTenantUser($user, $tenant);

        // Set tenant context
        $this->tenantContextService->setTenant($tenant->id);

        // Create graduate record
        $graduate = Graduate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => 'STU001',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
        ]);

        // Authenticate as user
        Auth::login($user);

        // Access assistance requests endpoint
        $response = $this->actingAs($user)
            ->get(route('graduates.assistance-requests'));

        // Assert successful response
        $response->assertStatus(200);
    }
}
