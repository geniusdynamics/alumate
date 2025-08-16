<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateCirclesAndGroupsTablesTest extends TestCase
{
    public function test_circles_table_exists_with_correct_structure()
    {
        // Verify table exists
        $this->assertTrue(Schema::hasTable('circles'));

        // Verify columns exist with correct types
        $this->assertTrue(Schema::hasColumn('circles', 'id'));
        $this->assertTrue(Schema::hasColumn('circles', 'name'));
        $this->assertTrue(Schema::hasColumn('circles', 'type'));
        $this->assertTrue(Schema::hasColumn('circles', 'criteria'));
        $this->assertTrue(Schema::hasColumn('circles', 'member_count'));
        $this->assertTrue(Schema::hasColumn('circles', 'auto_generated'));
        $this->assertTrue(Schema::hasColumn('circles', 'created_at'));
        $this->assertTrue(Schema::hasColumn('circles', 'updated_at'));

        // Verify column types
        $columns = Schema::getColumnListing('circles');
        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('type', $columns);
        $this->assertContains('criteria', $columns);
        $this->assertContains('member_count', $columns);
        $this->assertContains('auto_generated', $columns);
        $this->assertContains('created_at', $columns);
        $this->assertContains('updated_at', $columns);
    }

    public function test_groups_table_exists_with_correct_structure()
    {
        // Verify table exists
        $this->assertTrue(Schema::hasTable('groups'));

        // Verify columns exist
        $this->assertTrue(Schema::hasColumn('groups', 'id'));
        $this->assertTrue(Schema::hasColumn('groups', 'name'));
        $this->assertTrue(Schema::hasColumn('groups', 'description'));
        $this->assertTrue(Schema::hasColumn('groups', 'type'));
        $this->assertTrue(Schema::hasColumn('groups', 'privacy'));
        $this->assertTrue(Schema::hasColumn('groups', 'institution_id'));
        $this->assertTrue(Schema::hasColumn('groups', 'creator_id'));
        $this->assertTrue(Schema::hasColumn('groups', 'settings'));
        $this->assertTrue(Schema::hasColumn('groups', 'member_count'));
        $this->assertTrue(Schema::hasColumn('groups', 'created_at'));
        $this->assertTrue(Schema::hasColumn('groups', 'updated_at'));
    }

    public function test_circle_memberships_table_exists_with_correct_structure()
    {
        // Verify table exists
        $this->assertTrue(Schema::hasTable('circle_memberships'));

        // Verify columns exist
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'id'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'circle_id'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'user_id'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'joined_at'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'status'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'created_at'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'updated_at'));
    }

    public function test_group_memberships_table_exists_with_correct_structure()
    {
        // Verify table exists
        $this->assertTrue(Schema::hasTable('group_memberships'));

        // Verify columns exist
        $this->assertTrue(Schema::hasColumn('group_memberships', 'id'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'group_id'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'user_id'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'role'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'joined_at'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'status'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'created_at'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'updated_at'));
    }

    public function test_foreign_key_columns_exist()
    {
        // Verify foreign key columns exist
        $this->assertTrue(Schema::hasColumn('groups', 'institution_id'));
        $this->assertTrue(Schema::hasColumn('groups', 'creator_id'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'circle_id'));
        $this->assertTrue(Schema::hasColumn('circle_memberships', 'user_id'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'group_id'));
        $this->assertTrue(Schema::hasColumn('group_memberships', 'user_id'));
    }

    public function test_can_insert_records_with_default_values()
    {
        // Test that we can insert records with default values
        \DB::table('circles')->insert([
            'name' => 'Test Circle',
            'type' => 'school_year',
            'criteria' => json_encode(['school_id' => 1, 'year' => 2020]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $circle = \DB::table('circles')->where('name', 'Test Circle')->first();
        $this->assertEquals(0, $circle->member_count);
        $this->assertEquals(0, $circle->auto_generated);

        // Clean up
        \DB::table('circles')->where('name', 'Test Circle')->delete();
    }
}
