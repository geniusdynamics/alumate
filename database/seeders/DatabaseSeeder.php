<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            GraduateTrackingSeeder::class,
            DemoUsersSeeder::class,
            HomepageNavigationSeeder::class,
            
            // Template Creation System Seeders
            BrandConfigSeeder::class,
            TemplateSeeder::class,
            LandingPageSeeder::class,
        ]);
    }
}
