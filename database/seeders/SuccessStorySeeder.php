<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuccessStorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some featured success stories
        \App\Models\SuccessStory::factory()
            ->count(5)
            ->featured()
            ->create();

        // Create regular published stories
        \App\Models\SuccessStory::factory()
            ->count(15)
            ->published()
            ->create();

        // Create some draft stories
        \App\Models\SuccessStory::factory()
            ->count(5)
            ->draft()
            ->create();
    }
}
