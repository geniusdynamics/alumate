<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Analytics\CohortAnalysisController;
use App\Models\Cohort;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Services\Analytics\CohortAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Cohort Analysis Controller Test
 *
 * Tests for cohort analysis API endpoints including CRUD operations,
 * metrics retrieval, comparison, trends, and insights.
 */
class CohortAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    protected CohortAnalysisController $controller;
    protected CohortAnalysisService $cohortAnalysisService;
    protected Tenant $tenant;
    protected User $tenantAdmin;
    protected User $staff;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cohortAnalysisService = app(CohortAnalysisService::class);
        $this->controller = new CohortAnalysisController($this->cohortAnalysisService);
        
        // Create tenant and users for testing
        $this->tenant = Tenant::factory()->create();
        
        $this->tenantAdmin = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $this->tenantAdmin->id,
            'tenant_id' => $this->tenant->id,
            'role' => User::ROLE_TENANT_ADMIN,
            'is_active' => true,
        ]);
        
        $this->staff = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $this->staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
        
        $this->student = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $this->student->id,
            'tenant_id' => $this->tenant->id,
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);
    }

    // ===============================
    // CRUD Operation Tests
    // ===============================

    /**
     * Test listing all cohorts for authenticated admin user.
     */
    public function test_index_returns_cohort_list_for_admin(): void
    {
        Auth::login($this->tenantAdmin);
        
        // Create some cohorts
        Cohort::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->index();
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('cohorts', $data['data']);
        $this->assertArrayHasKey('pagination', $data['data']);
        $this->assertCount(3, $data['data']['cohorts']);
    }

    /**
     * Test listing cohorts for staff user.
     */
    public function test_index_returns_cohort_list_for_staff(): void
    {
        Auth::login($this->staff);
        
        Cohort::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->index();
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']['cohorts']);
    }

    /**
     * Test unauthenticated user cannot list cohorts.
     */
    public function test_index_returns_401_for_unauthenticated_user(): void
    {
        Auth::logout();
        
        $response = $this->controller->index();
        
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test student cannot list cohorts.
     */
    public function test_index_returns_403_for_student(): void
    {
        Auth::login($this->student);
        
        $response = $this->controller->index();
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test creating a new cohort.
     */
    public function test_store_creates_new_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $request = request()->merge([
            'name' => 'Class of 2024',
            'criteria' => [
                'grad_year' => 2024,
                'degree' => 'Bachelor',
            ],
            'description' => 'Cohort for graduating class of 2024',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('cohort', $data['data']);
        $this->assertEquals('Class of 2024', $data['data']['cohort']['name']);
        $this->assertDatabaseHas('cohorts', [
            'name' => 'Class of 2024',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test store validation fails with missing name.
     */
    public function test_store_validation_fails_without_name(): void
    {
        Auth::login($this->tenantAdmin);
        
        $request = request()->merge([
            'criteria' => [
                'grad_year' => 2024,
            ],
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test store validation fails with empty criteria.
     */
    public function test_store_validation_fails_with_empty_criteria(): void
    {
        Auth::login($this->tenantAdmin);
        
        $request = request()->merge([
            'name' => 'Test Cohort',
            'criteria' => [],
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test staff can create cohorts.
     */
    public function test_store_allows_staff_to_create_cohort(): void
    {
        Auth::login($this->staff);
        
        $request = request()->merge([
            'name' => 'Staff Created Cohort',
            'criteria' => [
                'acquisition_source' => 'organic',
            ],
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Test getting cohort details.
     */
    public function test_show_returns_cohort_details(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->show($cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('cohort', $data['data']);
        $this->assertEquals($cohort->id, $data['data']['cohort']['id']);
        $this->assertEquals($cohort->name, $data['data']['cohort']['name']);
    }

    /**
     * Test show returns 404 for non-existent cohort.
     */
    public function test_show_returns_404_for_nonexistent_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $response = $this->controller->show(99999);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test updating a cohort.
     */
    public function test_update_modifies_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Name',
        ]);
        
        $request = request()->merge([
            'name' => 'Updated Name',
            'criteria' => [
                'grad_year' => 2023,
            ],
        ]);
        
        $response = $this->controller->update($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Updated Name', $data['data']['cohort']['name']);
        
        $this->assertDatabaseHas('cohorts', [
            'id' => $cohort->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test staff can update their own cohort.
     */
    public function test_staff_can_update_own_cohort(): void
    {
        Auth::login($this->staff);
        
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->staff->id,
        ]);
        
        $request = request()->merge([
            'name' => 'Staff Updated',
            'criteria' => [],
        ]);
        
        $response = $this->controller->update($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test deleting a cohort.
     */
    public function test_destroy_deletes_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->destroy($cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertDatabaseMissing('cohorts', ['id' => $cohort->id]);
    }

    /**
     * Test staff cannot delete cohort they didn't create.
     */
    public function test_staff_cannot_delete_others_cohort(): void
    {
        Auth::login($this->staff);
        
        $otherStaff = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $otherStaff->id,
            'tenant_id' => $this->tenant->id,
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
        
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $otherStaff->id,
        ]);
        
        $response = $this->controller->destroy($cohort->id);
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertDatabaseHas('cohorts', ['id' => $cohort->id]);
    }

    // ===============================
    // Cohort Metrics Tests
    // ===============================

    /**
     * Test getting retention metrics.
     */
    public function test_retention_returns_retention_metrics(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'periods' => 12,
            'interval' => 'monthly',
        ]);
        
        $response = $this->controller->retention($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('retention', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
    }

    /**
     * Test retention with custom parameters.
     */
    public function test_retention_with_custom_parameters(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'periods' => 6,
            'interval' => 'weekly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'cohort_size' => 100,
        ]);
        
        $response = $this->controller->retention($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test getting engagement metrics.
     */
    public function test_engagement_returns_engagement_metrics(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'metrics' => ['posts', 'comments', 'shares', 'likes'],
            'period' => 'monthly',
        ]);
        
        $response = $this->controller->engagement($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('engagement', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
    }

    /**
     * Test engagement with invalid metrics.
     */
    public function test_engagement_validation_fails_with_invalid_metrics(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'metrics' => ['invalid_metric'],
            'period' => 'monthly',
        ]);
        
        $response = $this->controller->engagement($request, $cohort->id);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test getting conversion metrics.
     */
    public function test_conversion_returns_conversion_metrics(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'funnel_steps' => [
                ['name' => 'Sign Up', 'action' => 'register'],
                ['name' => 'Profile Complete', 'action' => 'complete_profile'],
                ['name' => 'First Event', 'action' => 'attend_event'],
            ],
            'period' => 'monthly',
        ]);
        
        $response = $this->controller->conversion($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('conversion', $data['data']);
        $this->assertArrayHasKey('funnel', $data['data']);
    }

    // ===============================
    // Cohort Comparison Tests
    // ===============================

    /**
     * Test comparing multiple cohorts.
     */
    public function test_compare_returns_comparison_data(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort1 = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        $cohort2 = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'cohort_ids' => [$cohort1->id, $cohort2->id],
            'metrics' => ['retention', 'engagement', 'conversion'],
            'period' => 'monthly',
        ]);
        
        $response = $this->controller->compare($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('comparison', $data['data']);
        $this->assertArrayHasKey('cohorts', $data['data']);
    }

    /**
     * Test compare validation fails with insufficient cohorts.
     */
    public function test_compare_validation_fails_with_single_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'cohort_ids' => [$cohort->id],
            'metrics' => ['retention'],
        ]);
        
        $response = $this->controller->compare($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test compare validation fails with duplicate cohort IDs.
     */
    public function test_compare_validation_fails_with_duplicate_cohorts(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'cohort_ids' => [$cohort->id, $cohort->id],
            'metrics' => ['retention'],
        ]);
        
        $response = $this->controller->compare($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    // ===============================
    // Trend Analysis Tests
    // ===============================

    /**
     * Test getting trend analysis.
     */
    public function test_trends_returns_trend_analysis(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'metrics' => ['retention', 'engagement'],
            'period' => 'monthly',
            'intervals' => 12,
            'compare_previous' => true,
        ]);
        
        $response = $this->controller->trends($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('trends', $data['data']);
        $this->assertArrayHasKey('forecast', $data['data']);
    }

    /**
     * Test trends with statistical analysis.
     */
    public function test_trends_with_statistical_analysis(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'metrics' => ['retention'],
            'period' => 'monthly',
            'include_statistical_analysis' => true,
            'include_forecast' => true,
            'confidence_level' => 0.95,
        ]);
        
        $response = $this->controller->trends($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    // ===============================
    // Insights Tests
    // ===============================

    /**
     * Test getting automated insights.
     */
    public function test_insights_returns_automated_insights(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->insights($cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('insights', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
    }

    /**
     * Test insights with priority filter.
     */
    public function test_insights_with_priority_filter(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'priority_levels' => ['high', 'medium'],
            'limit' => 10,
        ]);
        
        $response = $this->controller->insights($cohort->id, $request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    // ===============================
    // Tenant Isolation Tests
    // ===============================

    /**
     * Test user cannot access cohort from different tenant.
     */
    public function test_user_cannot_access_other_tenant_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $otherTenant = Tenant::factory()->create();
        $otherCohort = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $response = $this->controller->show($otherCohort->id);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test user cannot update cohort from different tenant.
     */
    public function test_user_cannot_update_other_tenant_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $otherTenant = Tenant::factory()->create();
        $otherCohort = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $request = request()->merge([
            'name' => 'Hacked Name',
            'criteria' => [],
        ]);
        
        $response = $this->controller->update($request, $otherCohort->id);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test user cannot delete cohort from different tenant.
     */
    public function test_user_cannot_delete_other_tenant_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $otherTenant = Tenant::factory()->create();
        $otherCohort = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $response = $this->controller->destroy($otherCohort->id);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test user cannot get metrics for other tenant cohort.
     */
    public function test_user_cannot_get_metrics_for_other_tenant_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $otherTenant = Tenant::factory()->create();
        $otherCohort = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $request = request()->merge(['periods' => 6]);
        
        $response = $this->controller->retention($request, $otherCohort->id);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test user cannot compare cohorts from different tenants.
     */
    public function test_user_cannot_compare_different_tenant_cohorts(): void
    {
        Auth::login($this->tenantAdmin);
        
        $otherTenant = Tenant::factory()->create();
        $cohort1 = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        $cohort2 = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $request = request()->merge([
            'cohort_ids' => [$cohort1->id, $cohort2->id],
            'metrics' => ['retention'],
        ]);
        
        $response = $this->controller->compare($request);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    // ===============================
    // Error Handling Tests
    // ===============================

    /**
     * Test handling non-existent cohort gracefully.
     */
    public function test_retention_handles_nonexistent_cohort(): void
    {
        Auth::login($this->tenantAdmin);
        
        $request = request()->merge(['periods' => 6]);
        
        $response = $this->controller->retention($request, 99999);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test handling invalid date range.
     */
    public function test_trends_handles_invalid_date_range(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'period' => 'monthly',
            'start_date' => '2024-06-30',
            'end_date' => '2024-01-01', // End before start
        ]);
        
        $response = $this->controller->trends($request, $cohort->id);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test handling invalid period value.
     */
    public function test_engagement_handles_invalid_period(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'metrics' => ['posts'],
            'period' => 'invalid_period',
        ]);
        
        $response = $this->controller->engagement($request, $cohort->id);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    // ===============================
    // Super Admin Tests
    // ===============================

    /**
     * Test super admin can access cohorts from any tenant.
     */
    public function test_super_admin_can_access_any_tenant_cohort(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Auth::login($superAdmin);
        
        $otherTenant = Tenant::factory()->create();
        $otherCohort = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $response = $this->controller->show($otherCohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test super admin can delete any cohort.
     */
    public function test_super_admin_can_delete_any_cohort(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Auth::login($superAdmin);
        
        $otherTenant = Tenant::factory()->create();
        $otherCohort = Cohort::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $response = $this->controller->destroy($otherCohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseMissing('cohorts', ['id' => $otherCohort->id]);
    }

    /**
     * Test super admin can compare cohorts across tenants.
     */
    public function test_super_admin_can_compare_across_tenants(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Auth::login($superAdmin);
        
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $cohort1 = Cohort::factory()->create(['tenant_id' => $tenant1->id]);
        $cohort2 = Cohort::factory()->create(['tenant_id' => $tenant2->id]);
        
        $request = request()->merge([
            'cohort_ids' => [$cohort1->id, $cohort2->id],
            'metrics' => ['retention'],
        ]);
        
        $response = $this->controller->compare($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    // ===============================
    // Request Validation Tests
    // ===============================

    /**
     * Test validation fails with invalid date format.
     */
    public function test_retention_validates_date_format(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'periods' => 6,
            'start_date' => 'invalid-date',
            'end_date' => 'also-invalid',
        ]);
        
        $response = $this->controller->retention($request, $cohort->id);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test validation fails with negative periods.
     */
    public function test_retention_validates_periods_range(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'periods' => -5,
            'interval' => 'monthly',
        ]);
        
        $response = $this->controller->retention($request, $cohort->id);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test validation fails with empty cohort IDs for comparison.
     */
    public function test_compare_validates_cohort_ids_not_empty(): void
    {
        Auth::login($this->tenantAdmin);
        
        $request = request()->merge([
            'cohort_ids' => [],
            'metrics' => ['retention'],
        ]);
        
        $response = $this->controller->compare($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test validation accepts valid confidence level.
     */
    public function test_trends_accepts_valid_confidence_level(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'metrics' => ['retention'],
            'confidence_level' => 0.95,
        ]);
        
        $response = $this->controller->trends($request, $cohort->id);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test validation rejects invalid confidence level.
     */
    public function test_trends_rejects_invalid_confidence_level(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge([
            'metrics' => ['retention'],
            'confidence_level' => 1.5, // > 1.0
        ]);
        
        $response = $this->controller->trends($request, $cohort->id);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    // ===============================
    // Response Structure Tests
    // ===============================

    /**
     * Test index response structure.
     */
    public function test_index_response_structure(): void
    {
        Auth::login($this->tenantAdmin);
        
        Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->index();
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('cohorts', $data['data']);
        $this->assertArrayHasKey('pagination', $data['data']);
        $this->assertArrayHasKey('meta', $data['data']);
    }

    /**
     * Test show response structure.
     */
    public function test_show_response_structure(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->show($cohort->id);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('cohort', $data['data']);
        $this->assertArrayHasKey('id', $data['data']['cohort']);
        $this->assertArrayHasKey('name', $data['data']['cohort']);
        $this->assertArrayHasKey('criteria_json', $data['data']['cohort']);
        $this->assertArrayHasKey('members_count', $data['data']['cohort']);
        $this->assertArrayHasKey('created_at', $data['data']['cohort']);
    }

    /**
     * Test retention response structure.
     */
    public function test_retention_response_structure(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $request = request()->merge(['periods' => 6]);
        
        $response = $this->controller->retention($request, $cohort->id);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('retention', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
        $this->assertArrayHasKey('periods', $data['data']);
        $this->assertArrayHasKey('interval', $data['data']);
    }

    /**
     * Test insights response structure.
     */
    public function test_insights_response_structure(): void
    {
        Auth::login($this->tenantAdmin);
        
        $cohort = Cohort::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->controller->insights($cohort->id);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('insights', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
        $this->assertArrayHasKey('generated_at', $data['data']);
    }
}
