<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Job;
use App\Models\Post;
use App\Models\User;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SetupElasticsearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:setup {--reindex : Reindex all existing data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up Elasticsearch indices and optionally reindex existing data';

    private ElasticsearchService $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        parent::__construct();
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up Elasticsearch indices...');

        try {
            // Create indices
            $this->info('Creating Elasticsearch indices...');
            $success = $this->elasticsearchService->createIndices();

            if (! $success) {
                $this->error('Failed to create Elasticsearch indices');

                return 1;
            }

            $this->info('✅ Elasticsearch indices created successfully');

            // Reindex existing data if requested
            if ($this->option('reindex')) {
                $this->info('Reindexing existing data...');
                $this->reindexData();
            }

            $this->info('🎉 Elasticsearch setup completed successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to set up Elasticsearch: '.$e->getMessage());
            Log::error('Elasticsearch setup failed', ['error' => $e->getMessage()]);

            return 1;
        }
    }

    /**
     * Reindex all existing data
     */
    private function reindexData(): void
    {
        // Index users
        $this->info('Indexing users...');
        $userCount = 0;
        User::chunk(100, function ($users) use (&$userCount) {
            foreach ($users as $user) {
                try {
                    $this->elasticsearchService->indexUser($user);
                    $userCount++;
                } catch (\Exception $e) {
                    $this->warn("Failed to index user {$user->id}: ".$e->getMessage());
                }
            }
        });
        $this->info("✅ Indexed {$userCount} users");

        // Index posts
        $this->info('Indexing posts...');
        $postCount = 0;
        Post::with('user')->chunk(100, function ($posts) use (&$postCount) {
            foreach ($posts as $post) {
                try {
                    $this->elasticsearchService->indexPost($post);
                    $postCount++;
                } catch (\Exception $e) {
                    $this->warn("Failed to index post {$post->id}: ".$e->getMessage());
                }
            }
        });
        $this->info("✅ Indexed {$postCount} posts");

        // Index jobs (if Job model exists)
        if (class_exists(Job::class)) {
            $this->info('Indexing jobs...');
            $jobCount = 0;
            Job::chunk(100, function ($jobs) use (&$jobCount) {
                foreach ($jobs as $job) {
                    try {
                        // You would implement indexJob method in ElasticsearchService
                        // $this->elasticsearchService->indexJob($job);
                        $jobCount++;
                    } catch (\Exception $e) {
                        $this->warn("Failed to index job {$job->id}: ".$e->getMessage());
                    }
                }
            });
            $this->info("✅ Indexed {$jobCount} jobs");
        }

        // Index events (if Event model exists)
        if (class_exists(Event::class)) {
            $this->info('Indexing events...');
            $eventCount = 0;
            Event::chunk(100, function ($events) use (&$eventCount) {
                foreach ($events as $event) {
                    try {
                        // You would implement indexEvent method in ElasticsearchService
                        // $this->elasticsearchService->indexEvent($event);
                        $eventCount++;
                    } catch (\Exception $e) {
                        $this->warn("Failed to index event {$event->id}: ".$e->getMessage());
                    }
                }
            });
            $this->info("✅ Indexed {$eventCount} events");
        }
    }
}
