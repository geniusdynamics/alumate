<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreatePostsTableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_posts_table_with_correct_structure()
    {
        // The migration should have run during test setup
        $this->assertTrue(Schema::hasTable('posts'));
    }

    /** @test */
    public function it_has_all_required_columns()
    {
        $columns = [
            'id',
            'user_id',
            'content',
            'media_urls',
            'post_type',
            'visibility',
            'circle_ids',
            'group_ids',
            'metadata',
            'created_at',
            'updated_at',
            'deleted_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('posts', $column),
                "Column '{$column}' does not exist in posts table"
            );
        }
    }

    /** @test */
    public function it_has_correct_column_types()
    {
        $connection = Schema::getConnection();
        $doctrineTable = $connection->getDoctrineSchemaManager()->listTableDetails('posts');

        // Check specific column types
        $this->assertEquals('bigint', $doctrineTable->getColumn('id')->getType()->getName());
        $this->assertEquals('bigint', $doctrineTable->getColumn('user_id')->getType()->getName());
        $this->assertEquals('text', $doctrineTable->getColumn('content')->getType()->getName());
        
        // JSON columns might be reported differently depending on database
        $this->assertContains(
            $doctrineTable->getColumn('media_urls')->getType()->getName(),
            ['json', 'text', 'longtext']
        );
        
        $this->assertContains(
            $doctrineTable->getColumn('circle_ids')->getType()->getName(),
            ['json', 'text', 'longtext']
        );
        
        $this->assertContains(
            $doctrineTable->getColumn('group_ids')->getType()->getName(),
            ['json', 'text', 'longtext']
        );
        
        $this->assertContains(
            $doctrineTable->getColumn('metadata')->getType()->getName(),
            ['json', 'text', 'longtext']
        );
    }

    /** @test */
    public function it_has_required_indexes()
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('posts');
        
        $indexNames = array_keys($indexes);
        
        // Check for timeline performance index
        $this->assertContains('idx_posts_timeline', $indexNames);
        $this->assertContains('idx_posts_user', $indexNames);
        $this->assertContains('idx_posts_type', $indexNames);
        $this->assertContains('idx_posts_visibility', $indexNames);
    }

    /** @test */
    public function it_has_foreign_key_constraint()
    {
        $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('posts');
        
        $this->assertCount(1, $foreignKeys);
        
        $userForeignKey = $foreignKeys[0];
        $this->assertEquals(['user_id'], $userForeignKey->getLocalColumns());
        $this->assertEquals('users', $userForeignKey->getForeignTableName());
        $this->assertEquals(['id'], $userForeignKey->getForeignColumns());
    }

    /** @test */
    public function it_has_soft_deletes()
    {
        $this->assertTrue(Schema::hasColumn('posts', 'deleted_at'));
    }

    /** @test */
    public function migration_can_be_rolled_back()
    {
        // Rollback the migration
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Verify table is dropped
        $this->assertFalse(Schema::hasTable('posts'));
        
        // Re-run migration for cleanup
        $this->artisan('migrate');
    }
}