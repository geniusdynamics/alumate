<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ElasticsearchIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:index 
                            {action : The action to perform (create, reindex, delete)}
                            {--force : Force the action without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Elasticsearch index for alumni search';

    protected ElasticsearchService $elasticsearchService;

    /**
     * Create a new command instance.
     */
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
        $action = $this->argument('action');

        return match ($action) {
            'create' => $this->createIndex(),
            'reindex' => $this->reindexUsers(),
            'delete' => $this->deleteIndex(),
            default => $this->error("Invalid action: {$action}. Use: create, reindex, or delete")
        };
    }

    /**
     * Create the Elasticsearch index
     */
    protected function createIndex(): int
    {
        $this->info('Creating Elasticsearch index...');

        try {
            $result = $this->elasticsearchService->createIndex();

            if ($result) {
                $this->info('✅ Elasticsearch index created successfully!');
                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to create Elasticsearch index');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error creating index: {$e->getMessage()}");
            Log::error('Elasticsearch index creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Reindex all users
     */
    protected function reindexUsers(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will reindex all users. Continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Starting user reindexing...');

        // First, create/update the index
        $this->info('Ensuring index exists...');
        $this->elasticsearchService->createIndex();

        // Get total user count
        $totalUsers = User::count();
        $this->info("Found {$totalUsers} users to index");

        if ($totalUsers === 0) {
            $this->info('No users found to index.');
            return Command::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        $indexed = 0;
        $failed = 0;

        // Process users in chunks to avoid memory issues
        User::chunk(100, function ($users) use ($progressBar, &$indexed, &$failed) {
            foreach ($users as $user) {
                try {
                    $result = $this->elasticsearchService->indexUser($user);
                    
                    if ($result) {
                        $indexed++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Failed to index user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Reindexing completed!");
        $this->info("   - Successfully indexed: {$indexed} users");
        
        if ($failed > 0) {
            $this->warn("   - Failed to index: {$failed} users");
            $this->warn("   Check the logs for details about failed indexing operations.");
        }

        return Command::SUCCESS;
    }

    /**
     * Delete the Elasticsearch index
     */
    protected function deleteIndex(): int
    {
        if (!$this->option('force')) {
            $this->warn('⚠️  This will permanently delete the Elasticsearch index and all search data!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Deleting Elasticsearch index...');

        try {
            // We'll need to add a deleteIndex method to the service
            $indexName = config('elasticsearch.indices.alumni.name');
            
            // For now, we'll use a simple approach
            $this->warn('Index deletion not implemented in service. Please delete manually if needed.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error deleting index: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}