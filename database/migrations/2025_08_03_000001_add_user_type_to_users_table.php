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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->nullable()->after('status');
                $table->index('user_type');
            }
        });

        // Update existing users with user_type based on their roles
        DB::statement("
            UPDATE users 
            SET user_type = CASE 
                WHEN EXISTS (
                    SELECT 1 FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = users.id 
                    AND mhr.model_type = 'App\\Models\\User' 
                    AND r.name = 'super-admin'
                ) THEN 'super-admin'
                WHEN EXISTS (
                    SELECT 1 FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = users.id 
                    AND mhr.model_type = 'App\\Models\\User' 
                    AND r.name = 'institution-admin'
                ) THEN 'institution-admin'
                WHEN EXISTS (
                    SELECT 1 FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = users.id 
                    AND mhr.model_type = 'App\\Models\\User' 
                    AND r.name = 'tutor'
                ) THEN 'tutor'
                WHEN EXISTS (
                    SELECT 1 FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = users.id 
                    AND mhr.model_type = 'App\\Models\\User' 
                    AND r.name = 'employer'
                ) THEN 'employer'
                WHEN EXISTS (
                    SELECT 1 FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = users.id 
                    AND mhr.model_type = 'App\\Models\\User' 
                    AND r.name = 'graduate'
                ) THEN 'graduate'
                WHEN EXISTS (
                    SELECT 1 FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = users.id 
                    AND mhr.model_type = 'App\\Models\\User' 
                    AND r.name = 'student'
                ) THEN 'student'
                WHEN EXISTS (
                    SELECT 1 FROM model_has_roles mhr 
                    JOIN roles r ON mhr.role_id = r.id 
                    WHERE mhr.model_id = users.id 
                    AND mhr.model_type = 'App\\Models\\User' 
                    AND r.name = 'alumni'
                ) THEN 'alumni'
                ELSE 'user'
            END
            WHERE user_type IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_type']);
            $table->dropColumn('user_type');
        });
    }
};
