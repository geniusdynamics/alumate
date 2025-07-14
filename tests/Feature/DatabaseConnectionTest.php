<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    /**
     * Test that the database connection is pgsql.
     *
     * @return void
     */
    public function test_database_connection_is_pgsql()
    {
        $this->assertEquals('pgsql', DB::connection()->getDriverName());
    }
}
