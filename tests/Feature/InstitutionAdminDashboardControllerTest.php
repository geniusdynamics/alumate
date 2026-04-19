<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\TestCase;

/**
 * Institution Admin Dashboard Controller Test
 *
 * Tests dashboard data accuracy and tenant isolation for
 * InstitutionAdminDashboardController.
 */
class InstitutionAdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected TenantContextService $tenantContextService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContextService = app(TenantContextService::class);
    }

    protected function tearDown(): void
    {
        $this->tenantContextService->clearContext();
        Tenancy::end();
        parent::tearDown();
    }

    /**
     * Test that dashboard returns accurate total graduates count.
     */
    public function test_dashboard_returns_accurate_total_graduates_count(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create graduates in tenant schema
            Graduate::factory()->count(5)->create();
            Graduate::factory()->count(3)->create();
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert total graduates count is accurate
        $this->assertEquals(8, $stats['total_graduates']);
    }

    /**
     * Test that dashboard returns accurate employed graduates count.
     */
    public function test_dashboard_returns_accurate_employed_graduates_count(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create graduates with different employment statuses
            Graduate::factory()->count(3)->create(['employment_status' => 'employed']);
            Graduate::factory()->count(2)->create(['employment_status' => 'unemployed']);
            Graduate::factory()->count(1)->create(['employment_status' => 'seeking']);
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert employed graduates count is accurate
        $this->assertEquals(3, $stats['employed_graduates']);
    }

    /**
     * Test that dashboard returns accurate active jobs count.
     */
    public function test_dashboard_returns_accurate_active_jobs_count(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Set tenant context for Course model
        $this->tenantContextService->setTenant($tenant->id);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create courses in tenant schema
            $course1 = Course::factory()->create();
            $course2 = Course::factory()->create();
            $course3 = Course::factory()->create();
        } finally {
            Tenancy::end();
            $this->tenantContextService->clearContext();
        }

        // Create jobs linked to tenant's courses
        Job::factory()->create([
            'course_id' => $course1->id,
            'status' => 'active',
        ]);
        Job::factory()->create([
            'course_id' => $course2->id,
            'status' => 'active',
        ]);
        Job::factory()->create([
            'course_id' => $course3->id,
            'status' => 'pending_approval',
        ]);
        Job::factory()->create([
            'course_id' => $course1->id,
            'status' => 'filled',
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert active jobs count is accurate (only active jobs for tenant's courses)
        $this->assertEquals(2, $stats['active_jobs']);
    }

    /**
     * Test that dashboard returns accurate pending applications count.
     */
    public function test_dashboard_returns_accurate_pending_applications_count(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create admin user for tenant1
        $admin = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        // Initialize tenant1 context
        Tenancy::initialize($tenant1);

        try {
            // Create graduates in tenant1 schema
            $graduate1 = Graduate::factory()->create(['tenant_id' => $tenant1->id]);
            $graduate2 = Graduate::factory()->create(['tenant_id' => $tenant1->id]);
        } finally {
            Tenancy::end();
        }

        // Initialize tenant2 context
        Tenancy::initialize($tenant2);

        try {
            // Create graduate in tenant2 schema (should not be counted)
            $graduate3 = Graduate::factory()->create(['tenant_id' => $tenant2->id]);
        } finally {
            Tenancy::end();
        }

        // Create applications for graduates
        JobApplication::factory()->create([
            'graduate_id' => $graduate1->id,
            'status' => 'pending',
        ]);
        JobApplication::factory()->create([
            'graduate_id' => $graduate2->id,
            'status' => 'pending',
        ]);
        JobApplication::factory()->create([
            'graduate_id' => $graduate3->id,
            'status' => 'pending',
        ]);
        JobApplication::factory()->create([
            'graduate_id' => $graduate1->id,
            'status' => 'reviewing',
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert pending applications count is accurate (only from tenant1's graduates)
        $this->assertEquals(2, $stats['pending_applications']);
    }

    /**
     * Test that dashboard enforces tenant isolation for graduates.
     */
    public function test_dashboard_enforces_tenant_isolation_for_graduates(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create admin user for tenant1
        $admin1 = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        // Create admin user for tenant2
        $admin2 = User::factory()->create([
            'institution_id' => $tenant2->id,
        ]);

        // Initialize tenant1 context
        Tenancy::initialize($tenant1);

        try {
            // Create graduates in tenant1 schema
            Graduate::factory()->count(5)->create();
        } finally {
            Tenancy::end();
        }

        // Initialize tenant2 context
        Tenancy::initialize($tenant2);

        try {
            // Create graduates in tenant2 schema
            Graduate::factory()->count(3)->create();
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin1
        Auth::login($admin1);

        // Call dashboard index for admin1
        $response1 = $this->actingAs($admin1)
            ->get(route('institution-admin.dashboard'));

        $response1->assertStatus(200);

        // Get stats from response
        $stats1 = $response1->viewData('stats');

        // Assert admin1 only sees their tenant's graduates
        $this->assertEquals(5, $stats1['total_graduates']);

        // Authenticate as admin2
        Auth::login($admin2);

        // Call dashboard index for admin2
        $response2 = $this->actingAs($admin2)
            ->get(route('institution-admin.dashboard'));

        $response2->assertStatus(200);

        // Get stats from response
        $stats2 = $response2->viewData('stats');

        // Assert admin2 only sees their tenant's graduates
        $this->assertEquals(3, $stats2['total_graduates']);
    }

    /**
     * Test that dashboard enforces tenant isolation for jobs.
     */
    public function test_dashboard_enforces_tenant_isolation_for_jobs(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create admin user for tenant1
        $admin1 = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        // Create admin user for tenant2
        $admin2 = User::factory()->create([
            'institution_id' => $tenant2->id,
        ]);

        // Set tenant context for Course model
        $this->tenantContextService->setTenant($tenant1->id);

        // Initialize tenant1 context
        Tenancy::initialize($tenant1);

        try {
            // Create courses in tenant1 schema
            $course1 = Course::factory()->create();
            $course2 = Course::factory()->create();
        } finally {
            Tenancy::end();
            $this->tenantContextService->clearContext();
        }

        // Set tenant context for Course model
        $this->tenantContextService->setTenant($tenant2->id);

        // Initialize tenant2 context
        Tenancy::initialize($tenant2);

        try {
            // Create courses in tenant2 schema
            $course3 = Course::factory()->create();
        } finally {
            Tenancy::end();
            $this->tenantContextService->clearContext();
        }

        // Create jobs for all courses
        Job::factory()->create([
            'course_id' => $course1->id,
            'status' => 'active',
        ]);
        Job::factory()->create([
            'course_id' => $course2->id,
            'status' => 'active',
        ]);
        Job::factory()->create([
            'course_id' => $course3->id,
            'status' => 'active',
        ]);

        // Authenticate as admin1
        Auth::login($admin1);

        // Call dashboard index for admin1
        $response1 = $this->actingAs($admin1)
            ->get(route('institution-admin.dashboard'));

        $response1->assertStatus(200);

        // Get stats from response
        $stats1 = $response1->viewData('stats');

        // Assert admin1 only sees jobs for their tenant's courses
        $this->assertEquals(2, $stats1['active_jobs']);

        // Authenticate as admin2
        Auth::login($admin2);

        // Call dashboard index for admin2
        $response2 = $this->actingAs($admin2)
            ->get(route('institution-admin.dashboard'));

        $response2->assertStatus(200);

        // Get stats from response
        $stats2 = $response2->viewData('stats');

        // Assert admin2 only sees jobs for their tenant's courses
        $this->assertEquals(1, $stats2['active_jobs']);
    }

    /**
     * Test that dashboard enforces tenant isolation for applications.
     */
    public function test_dashboard_enforces_tenant_isolation_for_applications(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create admin user for tenant1
        $admin1 = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);

        // Create admin user for tenant2
        $admin2 = User::factory()->create([
            'institution_id' => $tenant2->id,
        ]);

        // Initialize tenant1 context
        Tenancy::initialize($tenant1);

        try {
            // Create graduates in tenant1 schema
            $graduate1 = Graduate::factory()->create(['tenant_id' => $tenant1->id]);
        } finally {
            Tenancy::end();
        }

        // Initialize tenant2 context
        Tenancy::initialize($tenant2);

        try {
            // Create graduate in tenant2 schema
            $graduate2 = Graduate::factory()->create(['tenant_id' => $tenant2->id]);
        } finally {
            Tenancy::end();
        }

        // Create applications for both graduates
        JobApplication::factory()->count(3)->create([
            'graduate_id' => $graduate1->id,
            'status' => 'pending',
        ]);
        JobApplication::factory()->count(2)->create([
            'graduate_id' => $graduate2->id,
            'status' => 'pending',
        ]);

        // Authenticate as admin1
        Auth::login($admin1);

        // Call dashboard index for admin1
        $response1 = $this->actingAs($admin1)
            ->get(route('institution-admin.dashboard'));

        $response1->assertStatus(200);

        // Get stats from response
        $stats1 = $response1->viewData('stats');

        // Assert admin1 only sees applications for their tenant's graduates
        $this->assertEquals(3, $stats1['pending_applications']);

        // Authenticate as admin2
        Auth::login($admin2);

        // Call dashboard index for admin2
        $response2 = $this->actingAs($admin2)
            ->get(route('institution-admin.dashboard'));

        $response2->assertStatus(200);

        // Get stats from response
        $stats2 = $response2->viewData('stats');

        // Assert admin2 only sees applications for their tenant's graduates
        $this->assertEquals(2, $stats2['pending_applications']);
    }

    /**
     * Test that dashboard returns zero when tenant has no graduates.
     */
    public function test_dashboard_returns_zero_when_tenant_has_no_graduates(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert all graduate-related stats are zero
        $this->assertEquals(0, $stats['total_graduates']);
        $this->assertEquals(0, $stats['employed_graduates']);
    }

    /**
     * Test that dashboard returns zero when tenant has no courses.
     */
    public function test_dashboard_returns_zero_when_tenant_has_no_courses(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert active jobs is zero (no courses)
        $this->assertEquals(0, $stats['active_jobs']);
    }

    /**
     * Test that dashboard returns zero when tenant has no applications.
     */
    public function test_dashboard_returns_zero_when_tenant_has_no_applications(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert pending applications is zero
        $this->assertEquals(0, $stats['pending_applications']);
    }

    /**
     * Test that dashboard handles missing tenant gracefully.
     */
    public function test_dashboard_handles_missing_tenant_gracefully(): void
    {
        // Create admin user without institution
        $admin = User::factory()->create([
            'institution_id' => null,
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert all stats are zero when tenant is missing
        $this->assertEquals(0, $stats['total_graduates']);
        $this->assertEquals(0, $stats['employed_graduates']);
        $this->assertEquals(0, $stats['total_courses']);
        $this->assertEquals(0, $stats['active_jobs']);
        $this->assertEquals(0, $stats['pending_applications']);
        $this->assertEquals(0, $stats['staff_members']);
    }

    /**
     * Test that dashboard counts only active jobs.
     */
    public function test_dashboard_counts_only_active_jobs(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Set tenant context for Course model
        $this->tenantContextService->setTenant($tenant->id);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create course in tenant schema
            $course = Course::factory()->create();
        } finally {
            Tenancy::end();
            $this->tenantContextService->clearContext();
        }

        // Create jobs with different statuses
        Job::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        Job::factory()->create([
            'course_id' => $course->id,
            'status' => 'pending_approval',
        ]);
        Job::factory()->create([
            'course_id' => $course->id,
            'status' => 'filled',
        ]);
        Job::factory()->create([
            'course_id' => $course->id,
            'status' => 'paused',
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert only active jobs are counted
        $this->assertEquals(1, $stats['active_jobs']);
    }

    /**
     * Test that dashboard counts only pending applications.
     */
    public function test_dashboard_counts_only_pending_applications(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create graduate in tenant schema
            $graduate = Graduate::factory()->create(['tenant_id' => $tenant->id]);
        } finally {
            Tenancy::end();
        }

        // Create applications with different statuses
        JobApplication::factory()->create([
            'graduate_id' => $graduate->id,
            'status' => 'pending',
        ]);
        JobApplication::factory()->create([
            'graduate_id' => $graduate->id,
            'status' => 'reviewing',
        ]);
        JobApplication::factory()->create([
            'graduate_id' => $graduate->id,
            'status' => 'interviewing',
        ]);
        JobApplication::factory()->create([
            'graduate_id' => $graduate->id,
            'status' => 'hired',
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        // Get stats from response
        $stats = $response->viewData('stats');

        // Assert only pending applications are counted
        $this->assertEquals(1, $stats['pending_applications']);
    }

    /**
     * Test that getTimeToEmployment returns accurate data.
     */
    public function test_get_time_to_employment_returns_accurate_data(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create graduates with employment data
            $graduate1 = Graduate::factory()->create([
                'employment_status' => 'employed',
                'graduation_date' => now()->subMonths(3),
                'employment_start_date' => now()->subMonths(2)->addDays(15),
            ]);
            $graduate2 = Graduate::factory()->create([
                'employment_status' => 'employed',
                'graduation_date' => now()->subMonths(6),
                'employment_start_date' => now()->subMonths(5),
            ]);
            $graduate3 = Graduate::factory()->create([
                'employment_status' => 'employed',
                'graduation_date' => now()->subYear(),
                'employment_start_date' => now()->subMonths(11),
            ]);
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin
        Auth::login($admin);

        // Call analytics endpoint
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.analytics'));

        $response->assertStatus(200);

        // Get analytics from response
        $analytics = $response->viewData('analytics');

        // Assert time to employment data is returned
        $this->assertArrayHasKey('timeToEmployment', $analytics);
        $timeToEmployment = $analytics['timeToEmployment'];

        $this->assertArrayHasKey('average_days', $timeToEmployment);
        $this->assertArrayHasKey('median_days', $timeToEmployment);
        $this->assertArrayHasKey('under_3_months_percentage', $timeToEmployment);
        $this->assertArrayHasKey('under_6_months_percentage', $timeToEmployment);

        // Verify calculations are accurate (should be > 0 with data)
        $this->assertGreaterThan(0, $timeToEmployment['average_days']);
        $this->assertGreaterThan(0, $timeToEmployment['median_days']);
    }

    /**
     * Test that getTimeToEmployment returns zeros when no employed graduates.
     */
    public function test_get_time_to_employment_returns_zeros_when_no_employed_graduates(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create unemployed graduates only
            Graduate::factory()->count(3)->create([
                'employment_status' => 'unemployed',
            ]);
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin
        Auth::login($admin);

        // Call analytics endpoint
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.analytics'));

        $response->assertStatus(200);

        // Get analytics from response
        $analytics = $response->viewData('analytics');
        $timeToEmployment = $analytics['timeToEmployment'];

        // Assert all values are zero
        $this->assertEquals(0, $timeToEmployment['average_days']);
        $this->assertEquals(0, $timeToEmployment['median_days']);
        $this->assertEquals(0, $timeToEmployment['under_3_months_percentage']);
        $this->assertEquals(0, $timeToEmployment['under_6_months_percentage']);
    }

    /**
     * Test that getSalaryProgression returns accurate data.
     */
    public function test_get_salary_progression_returns_accurate_data(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create employed graduates with salaries
            Graduate::factory()->create([
                'employment_status' => 'employed',
                'current_salary' => 50000,
                'graduation_date' => now()->subYear(),
            ]);
            Graduate::factory()->create([
                'employment_status' => 'employed',
                'current_salary' => 55000,
                'graduation_date' => now()->subYear(),
            ]);
            Graduate::factory()->create([
                'employment_status' => 'employed',
                'current_salary' => 70000,
                'graduation_date' => now()->subYears(3),
            ]);
            Graduate::factory()->create([
                'employment_status' => 'employed',
                'current_salary' => 85000,
                'graduation_date' => now()->subYears(5),
            ]);
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin
        Auth::login($admin);

        // Call analytics endpoint
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.analytics'));

        $response->assertStatus(200);

        // Get analytics from response
        $analytics = $response->viewData('analytics');

        // Assert salary progression data is returned
        $this->assertArrayHasKey('salaryProgression', $analytics);
        $salaryProgression = $analytics['salaryProgression'];

        $this->assertArrayHasKey('year_1', $salaryProgression);
        $this->assertArrayHasKey('year_3', $salaryProgression);
        $this->assertArrayHasKey('year_5', $salaryProgression);

        // Verify year_1 has salary data
        $this->assertGreaterThan(0, $salaryProgression['year_1']['average']);
        $this->assertGreaterThan(0, $salaryProgression['year_1']['median']);
    }

    /**
     * Test that getSalaryProgression returns zeros when no employed graduates.
     */
    public function test_get_salary_progression_returns_zeros_when_no_employed_graduates(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            // Create unemployed graduates only
            Graduate::factory()->count(3)->create([
                'employment_status' => 'unemployed',
            ]);
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin
        Auth::login($admin);

        // Call analytics endpoint
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.analytics'));

        $response->assertStatus(200);

        // Get analytics from response
        $analytics = $response->viewData('analytics');
        $salaryProgression = $analytics['salaryProgression'];

        // Assert all values are zero
        $this->assertEquals(0, $salaryProgression['year_1']['average']);
        $this->assertEquals(0, $salaryProgression['year_1']['median']);
        $this->assertEquals(0, $salaryProgression['year_3']['average']);
        $this->assertEquals(0, $salaryProgression['year_3']['median']);
        $this->assertEquals(0, $salaryProgression['year_5']['average']);
        $this->assertEquals(0, $salaryProgression['year_5']['median']);
    }

    /**
     * Test that analytics methods use tenant context properly.
     */
    public function test_analytics_methods_use_tenant_context_properly(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create admin users
        $admin1 = User::factory()->create([
            'institution_id' => $tenant1->id,
        ]);
        $admin2 = User::factory()->create([
            'institution_id' => $tenant2->id,
        ]);

        // Initialize tenant1 context
        Tenancy::initialize($tenant1);

        try {
            Graduate::factory()->count(5)->create();
        } finally {
            Tenancy::end();
        }

        // Initialize tenant2 context
        Tenancy::initialize($tenant2);

        try {
            Graduate::factory()->count(3)->create();
        } finally {
            Tenancy::end();
        }

        // Authenticate as admin1
        Auth::login($admin1);

        // Call analytics for admin1
        $response1 = $this->actingAs($admin1)
            ->get(route('institution-admin.analytics'));

        $analytics1 = $response1->viewData('analytics');

        // Verify tenant1 sees only their graduates
        $graduatesByYear1 = $analytics1['graduatesByYear'];
        $totalGraduates1 = $graduatesByYear1->sum('count');
        $this->assertEquals(5, $totalGraduates1);

        // Authenticate as admin2
        Auth::login($admin2);

        // Call analytics for admin2
        $response2 = $this->actingAs($admin2)
            ->get(route('institution-admin.analytics'));

        $analytics2 = $response2->viewData('analytics');

        // Verify tenant2 sees only their graduates
        $graduatesByYear2 = $analytics2['graduatesByYear'];
        $totalGraduates2 = $graduatesByYear2->sum('count');
        $this->assertEquals(3, $totalGraduates2);
    }

    /**
     * Test that active jobs excludes expired job postings.
     */
    public function test_active_jobs_excludes_expired_postings(): void
    {
        // Create tenant
        $tenant = Tenant::factory()->create();

        // Create admin user for tenant
        $admin = User::factory()->create([
            'institution_id' => $tenant->id,
        ]);

        // Set tenant context for Course model
        $this->tenantContextService->setTenant($tenant->id);

        // Initialize tenant context
        Tenancy::initialize($tenant);

        try {
            $course = Course::factory()->create();
        } finally {
            Tenancy::end();
            $this->tenantContextService->clearContext();
        }

        // Create active job with future deadline
        Job::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'application_deadline' => now()->addDays(30),
        ]);

        // Create active job with past deadline (should be excluded)
        Job::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'application_deadline' => now()->subDays(1),
        ]);

        // Authenticate as admin
        Auth::login($admin);

        // Call dashboard index
        $response = $this->actingAs($admin)
            ->get(route('institution-admin.dashboard'));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');

        // Assert only 1 active job (with future deadline)
        $this->assertEquals(1, $stats['active_jobs']);
    }
}
