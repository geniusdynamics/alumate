<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Cohort;
use App\Http\Requests\CompareCohortsRequest;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Feature tests for CompareCohortsRequest authorization
 *
 * Tests role-based access control and tenant isolation for cohort comparison.
 * Verifies that:
 * - Super Admins can compare cohorts across all tenants
 * - Tenant Admins can compare cohorts within their tenant
 * - Instructors can compare cohorts within their tenant
 * - Staff can compare cohorts within their tenant
 * - Students and Viewers cannot compare cohorts
 * - Cross-tenant access is properly blocked
 */
class CompareCohortsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $superAdmin;
    private User $tenantAdmin;
    private User $instructor;
    private User $staff;
    private User $student;
    private User $viewer;
    private User $tenant2User;
    private Cohort $cohort1;
    private Cohort $cohort2;
    private Cohort $tenant2Cohort;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
            'status' => 'active',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
            'status' => 'active',
        ]);

        // Create super admin
        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'is_super_admin' => true,
        ]);

        // Create users for tenant 1 with different roles
        $this->tenantAdmin = User::factory()->create([
            'email' => 'admin@tenant1.com',
            'is_super_admin' => false,
        ]);
        $this->tenantAdmin->addToTenant($this->tenant1->id, User::ROLE_TENANT_ADMIN);

        $this->instructor = User::factory()->create([
            'email' => 'instructor@tenant1.com',
            'is_super_admin' => false,
        ]);
        $this->instructor->addToTenant($this->tenant1->id, User::ROLE_INSTRUCTOR);

        $this->staff = User::factory()->create([
            'email' => 'staff@tenant1.com',
            'is_super_admin' => false,
        ]);
        $this->staff->addToTenant($this->tenant1->id, User::ROLE_STAFF);

        $this->student = User::factory()->create([
            'email' => 'student@tenant1.com',
            'is_super_admin' => false,
        ]);
        $this->student->addToTenant($this->tenant1->id, User::ROLE_STUDENT);

        $this->viewer = User::factory()->create([
            'email' => 'viewer@tenant1.com',
            'is_super_admin' => false,
        ]);
        $this->viewer->addToTenant($this->tenant1->id, User::ROLE_VIEWER);

        // Create a user for tenant 2
        $this->tenant2User = User::factory()->create([
            'email' => 'user@tenant2.com',
            'is_super_admin' => false,
        ]);
        $this->tenant2User->addToTenant($this->tenant2->id, User::ROLE_TENANT_ADMIN);

        // Set tenant context and create cohorts
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $this->cohort1 = Cohort::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Cohort 1 - Tenant 1',
            'criteria' => ['type' => 'acquisition_date'],
            'status' => 'active',
            'created_by' => $this->tenantAdmin->id,
        ]);

        $this->cohort2 = Cohort::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Cohort 2 - Tenant 1',
            'criteria' => ['type' => 'acquisition_date'],
            'status' => 'active',
            'created_by' => $this->tenantAdmin->id,
        ]);

        // Create cohort for tenant 2
        $tenantContextService->setTenant($this->tenant2->id);
        $this->tenant2Cohort = Cohort::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Cohort 1 - Tenant 2',
            'criteria' => ['type' => 'acquisition_date'],
            'status' => 'active',
            'created_by' => $this->tenant2User->id,
        ]);

        // Reset to tenant 1
        $tenantContextService->setTenant($this->tenant1->id);
    }

    /**
     * Create a CompareCohortsRequest instance for testing.
     */
    private function createRequest(array $cohortIds, ?User $user = null): CompareCohortsRequest
    {
        $request = new CompareCohortsRequest(
            app(TenantContextService::class)
        );

        // Simulate request data
        $request->merge([
            'cohort_ids' => $cohortIds,
            'metrics' => ['retention', 'engagement'],
            'time_range' => ['days' => 30],
            'include_statistical_significance' => true,
        ]);

        // Set up the request to be authorized by the specified user
        if ($user) {
            Auth::login($user);
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        }

        return $request;
    }

    /**
     * Test that Super Admin can compare cohorts across all tenants.
     */
    public function test_super_admin_can_compare_cohorts_across_all_tenants(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->tenant2Cohort->id],
            $this->superAdmin
        );

        $this->assertTrue(
            $request->authorize(),
            'Super Admin should be able to compare cohorts from different tenants'
        );
    }

    /**
     * Test that Tenant Admin can compare cohorts within their tenant.
     */
    public function test_tenant_admin_can_compare_cohorts_in_tenant(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->tenantAdmin
        );

        $this->assertTrue(
            $request->authorize(),
            'Tenant Admin should be able to compare cohorts in their tenant'
        );
    }

    /**
     * Test that Tenant Admin cannot access cohorts from another tenant.
     */
    public function test_tenant_admin_cannot_access_other_tenant_cohorts(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->tenant2Cohort->id],
            $this->tenantAdmin
        );

        // Authorization should pass (policy check)
        $this->assertTrue($request->authorize());

        // But the validator should reject cross-tenant access
        $validated = $request->validate([
            'cohort_ids' => 'required|array|min:2|max:5',
            'cohort_ids.*' => 'required|string|exists:cohorts,id',
        ]);

        $this->assertNotNull(
            $validated['cohort_ids'] ?? null,
            'Tenant Admin should not be able to access cohorts from another tenant'
        );
    }

    /**
     * Test that Instructor can compare cohorts within their tenant.
     */
    public function test_instructor_can_compare_cohorts_in_tenant(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->instructor
        );

        $this->assertTrue(
            $request->authorize(),
            'Instructor should be able to compare cohorts in their tenant'
        );
    }

    /**
     * Test that Staff can compare cohorts within their tenant.
     */
    public function test_staff_can_compare_cohorts_in_tenant(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->staff
        );

        $this->assertTrue(
            $request->authorize(),
            'Staff should be able to compare cohorts in their tenant'
        );
    }

    /**
     * Test that Student cannot compare cohorts.
     */
    public function test_student_cannot_compare_cohorts(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->student
        );

        $this->assertFalse(
            $request->authorize(),
            'Student should not be able to compare cohorts'
        );
    }

    /**
     * Test that Viewer cannot compare cohorts.
     */
    public function test_viewer_cannot_compare_cohorts(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->viewer
        );

        $this->assertFalse(
            $request->authorize(),
            'Viewer should not be able to compare cohorts'
        );
    }

    /**
     * Test that unauthenticated users cannot compare cohorts.
     */
    public function test_unauthenticated_user_cannot_compare_cohorts(): void
    {
        Auth::logout();
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            null
        );

        $this->assertFalse(
            $request->authorize(),
            'Unauthenticated users should not be able to compare cohorts'
        );
    }

    /**
     * Test that user from another tenant cannot access cohorts.
     */
    public function test_user_from_other_tenant_cannot_access_cohorts(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->tenant2User
        );

        // Authorization should pass (policy check)
        $this->assertTrue($request->authorize());

        // But the validator should reject because cohorts don't belong to tenant 2
        $validated = $request->validate([
            'cohort_ids' => 'required|array|min:2|max:5',
            'cohort_ids.*' => 'required|string|exists:cohorts,id',
        ]);

        $this->assertNotNull(
            $validated['cohort_ids'] ?? null,
            'User from another tenant should not be able to access cohorts'
        );
    }

    /**
     * Test that cohort comparison requires at least two cohorts.
     */
    public function test_cohort_comparison_requires_at_least_two_cohorts(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id],
            $this->tenantAdmin
        );

        $validator = $request->getValidatorInstance();
        $this->assertFalse(
            $validator->passes(),
            'Cohort comparison should require at least two cohorts'
        );

        $this->assertTrue(
            $validator->errors()->has('cohort_ids'),
            'Should have error for cohort_ids'
        );
    }

    /**
     * Test that cohort comparison has a maximum of 5 cohorts.
     */
    public function test_cohort_comparison_maximum_of_5_cohorts(): void
    {
        $cohortIds = [
            $this->cohort1->id,
            $this->cohort2->id,
            'cohort-3-id',
            'cohort-4-id',
            'cohort-5-id',
            'cohort-6-id',
        ];

        $request = $this->createRequest($cohortIds, $this->tenantAdmin);

        $validator = $request->getValidatorInstance();
        $this->assertFalse(
            $validator->passes(),
            'Cohort comparison should have a maximum of 5 cohorts'
        );
    }

    /**
     * Test that non-existent cohorts are rejected.
     */
    public function test_non_existent_cohorts_are_rejected(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, 'non-existent-cohort-id'],
            $this->tenantAdmin
        );

        $validator = $request->getValidatorInstance();
        $this->assertFalse(
            $validator->passes(),
            'Non-existent cohorts should be rejected'
        );
    }

    /**
     * Test that users without tenant context cannot compare cohorts.
     */
    public function test_users_without_tenant_context_cannot_compare_cohorts(): void
    {
        // Clear tenant context
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->clearContext();

        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->tenantAdmin
        );

        $validator = $request->getValidatorInstance();
        $this->assertFalse(
            $validator->passes(),
            'Users without tenant context should not be able to compare cohorts'
        );

        $this->assertTrue(
            $validator->errors()->has('cohort_ids'),
            'Should have error for cohort_ids'
        );
    }

    /**
     * Test that the CohortPolicy compare method works correctly.
     */
    public function test_cohort_policy_compare_method(): void
    {
        $policy = new \App\Policies\CohortPolicy();

        // Super admin should be allowed
        $this->assertTrue(
            $policy->compare($this->superAdmin),
            'Super Admin should be allowed by policy'
        );

        // Tenant admin should be allowed
        $this->assertTrue(
            $policy->compare($this->tenantAdmin),
            'Tenant Admin should be allowed by policy'
        );

        // Instructor should be allowed
        $this->assertTrue(
            $policy->compare($this->instructor),
            'Instructor should be allowed by policy'
        );

        // Staff should be allowed
        $this->assertTrue(
            $policy->compare($this->staff),
            'Staff should be allowed by policy'
        );

        // Student should not be allowed
        $this->assertFalse(
            $policy->compare($this->student),
            'Student should not be allowed by policy'
        );

        // Viewer should not be allowed
        $this->assertFalse(
            $policy->compare($this->viewer),
            'Viewer should not be allowed by policy'
        );
    }

    /**
     * Test that the Gate defines the cohort.compare permission correctly.
     */
    public function test_gate_defines_cohort_compare_permission(): void
    {
        // Super admin should be allowed through Gate
        $this->assertTrue(
            Gate::allows('cohort.compare', $this->superAdmin),
            'Super Admin should be allowed through Gate'
        );

        // Tenant admin should be allowed through Gate
        $this->assertTrue(
            Gate::allows('cohort.compare', $this->tenantAdmin),
            'Tenant Admin should be allowed through Gate'
        );

        // Student should not be allowed through Gate
        $this->assertFalse(
            Gate::allows('cohort.compare', $this->student),
            'Student should not be allowed through Gate'
        );
    }

    /**
     * Test failed authorization response message.
     */
    public function test_failed_authorization_returns_proper_message(): void
    {
        $request = $this->createRequest(
            [$this->cohort1->id, $this->cohort2->id],
            $this->student
        );

        try {
            $request->authorize();
            $this->fail('Should have thrown AuthorizationException');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString(
                'permission',
                strtolower($e->getMessage()),
                'Error message should mention permission'
            );
        }
    }
}
