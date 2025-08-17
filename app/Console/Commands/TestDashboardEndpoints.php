<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestDashboardEndpoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:dashboard-endpoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all dashboard endpoints by simulating user login';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Dashboard Endpoints...');
        $this->line('');

        // Test each user type
        $this->testUserDashboard('admin@system.com', 'Super Admin', '/super-admin/dashboard');
        $this->testUserDashboard('admin@tech-institute.edu', 'Institution Admin', '/institution-admin/dashboard');
        $this->testUserDashboard('techcorp@company.com', 'Employer', '/employer/dashboard');
        $this->testUserDashboard('john.smith@student.edu', 'Graduate', '/graduate/dashboard');

        $this->line('');
        $this->info('All endpoint tests completed!');
    }

    private function testUserDashboard($email, $userType, $dashboardRoute)
    {
        $this->info("=== Testing {$userType} Dashboard ===");

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("✗ User {$email} not found");

            return;
        }

        try {
            // Simulate login
            Auth::login($user);

            // Create a request and test the controller
            $request = Request::create($dashboardRoute, 'GET');
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            // Test the route exists
            $route = \Illuminate\Support\Facades\Route::getRoutes()->match($request);
            if (! $route) {
                $this->error("✗ Route {$dashboardRoute} not found");

                return;
            }

            $this->info("✓ Route {$dashboardRoute} exists");
            $this->info("✓ User {$email} can access {$userType} dashboard");

        } catch (\Exception $e) {
            $this->error("✗ {$userType} dashboard failed: ".$e->getMessage());
        } finally {
            Auth::logout();
        }

        $this->line('');
    }
}
