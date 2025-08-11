<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $tenancy = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabase();
        $this->setupRolesAndPermissions();

        if ($this->tenancy) {
            $this->setupTenancy();
        }
    }

    protected function setupDatabase(): void
    {
        // Ensure we're using SQLite in memory for tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        // Run migrations
        Artisan::call('migrate:fresh');
    }

    protected function setupRolesAndPermissions(): void
    {
        // Create basic roles
        $roles = ['super-admin', 'institution-admin', 'employer', 'graduate'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create basic permissions
        $permissions = [
            'manage-institutions', 'manage-users', 'manage-graduates',
            'manage-courses', 'post-jobs', 'view-applications', 'approve-employers',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    protected function setupTenancy(): void
    {
        // Create a test tenant
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Institution',
            'address' => '123 Test Street',
            'contact_information' => ['email' => 'test@institution.edu'],
            'plan' => 'basic',
            'status' => 'active',
        ]);

        // Initialize tenant
        tenancy()->initialize($tenant);
    }

    protected function createUserWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    protected function actingAsSuperAdmin(array $attributes = []): User
    {
        $user = $this->createUserWithRole('super-admin', $attributes);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsInstitutionAdmin(array $attributes = []): User
    {
        $user = $this->createUserWithRole('institution-admin', $attributes);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsEmployer(array $attributes = []): User
    {
        $user = $this->createUserWithRole('employer', $attributes);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsGraduate(array $attributes = []): User
    {
        $user = $this->createUserWithRole('graduate', $attributes);
        $this->actingAs($user);

        return $user;
    }

    protected function assertDatabaseTableExists(string $table): void
    {
        $this->assertTrue(
            Schema::hasTable($table),
            "Failed asserting that table '{$table}' exists."
        );
    }

    protected function assertDatabaseColumnExists(string $table, string $column): void
    {
        $this->assertTrue(
            Schema::hasColumn($table, $column),
            "Failed asserting that column '{$column}' exists in table '{$table}'."
        );
    }

    protected function assertValidationError(string $field, $response): void
    {
        $response->assertSessionHasErrors($field);
    }

    protected function assertSuccessResponse($response): void
    {
        $response->assertStatus(200);
    }

    protected function assertRedirectResponse($response): void
    {
        $response->assertStatus(302);
    }

    protected function tearDown(): void
    {
        if ($this->tenancy && tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }
}
