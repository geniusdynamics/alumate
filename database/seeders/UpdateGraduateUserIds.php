<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

class UpdateGraduateUserIds extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            Tenancy::initialize($tenant);

            try {
                // Get all graduates without user_id
                $graduates = DB::table('graduates')->whereNull('user_id')->get();

                foreach ($graduates as $graduate) {
                    // Find a user with the same email in the central database
                    $user = User::where('email', $graduate->email)->first();

                    if ($user) {
                        // Update the graduate with the user_id
                        DB::table('graduates')
                            ->where('id', $graduate->id)
                            ->update(['user_id' => $user->id]);

                        $this->command->info("Updated graduate {$graduate->name} with user_id {$user->id}");
                    } else {
                        $this->command->warn("No user found for graduate {$graduate->name} ({$graduate->email})");
                    }
                }
            } finally {
                Tenancy::end();
            }
        }
    }
}
