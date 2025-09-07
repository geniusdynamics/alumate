<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip creating circles and groups tables as they should be in central database
        // Skip creating circle_memberships and group_memberships tables as they reference central database tables
        
        // In a multi-tenant setup, these tables should exist only in the central database
        // Tenant databases should not have direct foreign key references to central database tables
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to do here as we're not creating any tables
    }
};