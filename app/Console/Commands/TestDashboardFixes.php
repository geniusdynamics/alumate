<?php

namespace App\Console\Commands;

use App\Http\Controllers\EmployerDashboardController;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Console\Command;

class TestDashboardFixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:dashboard-fixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all dashboard fixes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Dashboard Fixes...');
        $this->line('');

        // Test 1: Institution Admin Role
        $this->testInstitutionAdminRole();

        // Test 2: Employer Dashboard Statistics
        $this->testEmployerDashboard();

        // Test 3: Graduate Dashboard
        $this->testGraduateDashboard();

        $this->line('');
        $this->info('All tests completed!');
    }

    private function testInstitutionAdminRole()
    {
        $this->info('=== Testing Institution Admin Role ===');

        $user = User::where('email', 'admin@tech-institute.edu')->first();
        if (! $user) {
            $this->error('Institution admin user not found');

            return;
        }

        $hasRole = $user->hasRole('institution-admin');
        if ($hasRole) {
            $this->info('✓ Institution admin has correct role');
        } else {
            $this->error('✗ Institution admin missing role');
        }

        $this->line('');
    }

    private function testEmployerDashboard()
    {
        $this->info('=== Testing Employer Dashboard ===');

        $user = User::where('email', 'techcorp@company.com')->first();
        if (! $user) {
            $this->error('Employer user not found');

            return;
        }

        $employer = Employer::where('user_id', $user->id)->first();
        if (! $employer) {
            $this->error('Employer profile not found');

            return;
        }

        try {
            // Test the getDashboardStatistics method
            $controller = new EmployerDashboardController;
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('getDashboardStatistics');
            $method->setAccessible(true);

            $stats = $method->invoke($controller, $employer);

            $this->info('✓ Employer dashboard statistics generated successfully');
            $this->line('  - Total jobs posted: '.$stats['total_jobs_posted']);
            $this->line('  - Active jobs: '.$stats['active_jobs']);
            $this->line('  - Total applications: '.$stats['total_applications']);

        } catch (\Exception $e) {
            $this->error('✗ Employer dashboard failed: '.$e->getMessage());
        }

        $this->line('');
    }

    private function testGraduateDashboard()
    {
        $this->info('=== Testing Graduate Dashboard ===');

        $user = User::where('email', 'john.smith@student.edu')->first();
        if (! $user) {
            $this->error('Graduate user not found');

            return;
        }

        try {
            // Test JobApplication graduate relationship
            $applications = JobApplication::with('graduate')->limit(1)->get();
            $this->info('✓ JobApplication graduate relationship works');

            // Test graduate lookup
            $graduate = Graduate::where('user_id', $user->id)->first();
            if ($graduate) {
                $this->info('✓ Graduate profile found for user');
            } else {
                $this->warn('! Graduate profile not found for user (this might be expected)');
            }

        } catch (\Exception $e) {
            $this->error('✗ Graduate dashboard test failed: '.$e->getMessage());
        }

        $this->line('');
    }
}
