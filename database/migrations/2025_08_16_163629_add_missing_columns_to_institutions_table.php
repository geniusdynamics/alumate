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
        Schema::table('institutions', function (Blueprint $table) {
            // Add soft deletes if not exists
            if (!Schema::hasColumn('institutions', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Add other commonly needed columns
            if (!Schema::hasColumn('institutions', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            
            if (!Schema::hasColumn('institutions', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['is_active', 'status']);
        });
    }
};
