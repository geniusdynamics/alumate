<?php

namespace Database\Seeders;

use App\Models\HomepageNavigationItem;
use Illuminate\Database\Seeder;

class HomepageNavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the table first
        HomepageNavigationItem::query()->delete();

        // Main Items
        $home = HomepageNavigationItem::create(['title' => 'Home', 'url' => route('home', [], false), 'order' => 1, 'type' => 'link']);
        $jobs = HomepageNavigationItem::create(['title' => 'Jobs', 'url' => route('jobs.public.index', [], false), 'order' => 2, 'type' => 'link']);

        $alumni = HomepageNavigationItem::create(['title' => 'Alumni', 'url' => '#', 'order' => 3, 'type' => 'dropdown']);
        $about = HomepageNavigationItem::create(['title' => 'About', 'url' => '#', 'order' => 4, 'type' => 'dropdown']);

        // Children of Alumni
        HomepageNavigationItem::create([
            'parent_id' => $alumni->id,
            'title' => 'Alumni Directory',
            'url' => route('alumni.public.directory', [], false),
            'order' => 1,
            'type' => 'link',
        ]);
        HomepageNavigationItem::create([
            'parent_id' => $alumni->id,
            'title' => 'Alumni Map',
            'url' => route('alumni.public.map', [], false),
            'order' => 2,
            'type' => 'link',
        ]);
        HomepageNavigationItem::create([
            'parent_id' => $alumni->id,
            'title' => 'Success Stories',
            'url' => route('stories.public.index', [], false),
            'order' => 3,
            'type' => 'link',
        ]);

        // Children of About
        HomepageNavigationItem::create([
            'parent_id' => $about->id,
            'title' => 'Features',
            'url' => '#features',
            'order' => 1,
            'type' => 'link',
        ]);
        HomepageNavigationItem::create([
            'parent_id' => $about->id,
            'title' => 'Pricing',
            'url' => '#pricing',
            'order' => 2,
            'type' => 'link',
        ]);
        HomepageNavigationItem::create([
            'parent_id' => $about->id,
            'title' => 'Contact',
            'url' => '/contact',
            'order' => 3,
            'type' => 'link',
        ]);
    }
}
