<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if posts table already exists
        if (! Schema::hasTable('posts')) {
            // Create the posts table if it doesn't exist
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id'); // Store user ID without foreign key constraint
                $table->text('content');
                $table->json('media_urls')->nullable();
                $table->enum('post_type', ['text', 'media', 'career_update', 'achievement', 'event', 'poll', 'article_share'])->default('text');
                $table->enum('visibility', ['public', 'circles', 'groups', 'connections', 'private'])->default('public');
                $table->json('circle_ids')->nullable();
                $table->json('group_ids')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Indexes for timeline performance
                $table->index(['created_at', 'user_id'], 'idx_posts_timeline');
                $table->index('user_id', 'idx_posts_user');
                $table->index('post_type', 'idx_posts_type');
                $table->index('visibility', 'idx_posts_visibility');
            });
        } else {
            // Table exists, add missing columns and indexes
            Schema::table('posts', function (Blueprint $table) {
                // Add soft deletes if not present
                if (! Schema::hasColumn('posts', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            // Add missing indexes (using try-catch to handle existing indexes)
            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_timeline ON posts (created_at DESC, user_id)');
            } catch (Exception $e) {
                // Index might already exist
            }

            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_user ON posts (user_id)');
            } catch (Exception $e) {
                // Index might already exist
            }

            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_type ON posts (post_type)');
            } catch (Exception $e) {
                // Index might already exist
            }

            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_visibility ON posts (visibility)');
            } catch (Exception $e) {
                // Index might already exist
            }
        }

        // Add GIN indexes for JSON arrays if using PostgreSQL
        if (config('database.default') === 'pgsql') {
            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_circle_ids_gin ON posts USING GIN (circle_ids)');
            } catch (Exception $e) {
                // Index might already exist
            }

            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_group_ids_gin ON posts USING GIN (group_ids)');
            } catch (Exception $e) {
                // Index might already exist
            }

            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_metadata_gin ON posts USING GIN (metadata)');
            } catch (Exception $e) {
                // Index might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop GIN indexes first if they exist (PostgreSQL)
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_posts_circle_ids_gin');
            DB::statement('DROP INDEX IF EXISTS idx_posts_group_ids_gin');
            DB::statement('DROP INDEX IF EXISTS idx_posts_metadata_gin');
        }

        Schema::dropIfExists('posts');
    }
};