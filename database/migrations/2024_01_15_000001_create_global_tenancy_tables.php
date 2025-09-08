<?php
// ABOUTME: Database migration to create global tables for hybrid tenancy architecture
// ABOUTME: Sets up global_users, user_tenant_memberships, and global_courses tables for cross-tenant relationships

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create global_users table for cross-tenant user management
        Schema::create('global_users', function (Blueprint $table) {
            $table->uuid('global_user_id')->primary();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->json('preferences')->nullable(); // User preferences across all tenants
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['email']);
            $table->index(['first_name', 'last_name']);
            $table->index(['created_at']);
        });

        // Create user_tenant_memberships table for managing user access across tenants
        Schema::create('user_tenant_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('global_user_id');
            $table->string('tenant_id'); // References tenants.id
            $table->enum('role', ['super_admin', 'admin', 'instructor', 'student', 'guest'])->default('student');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->timestamp('joined_at');
            $table->timestamp('last_active_at')->nullable();
            $table->json('tenant_specific_data')->nullable(); // Role-specific data per tenant
            $table->json('permissions')->nullable(); // Custom permissions per tenant
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('global_user_id')->references('global_user_id')->on('global_users')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Unique constraint - one membership per user per tenant
            $table->unique(['global_user_id', 'tenant_id']);
            
            // Indexes
            $table->index(['tenant_id', 'role']);
            $table->index(['status']);
            $table->index(['joined_at']);
            $table->index(['last_active_at']);
        });

        // Create global_courses table for courses that can be shared across institutions
        Schema::create('global_courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('global_course_code')->unique(); // e.g., 'MATH-101', 'ENG-201'
            $table->string('title');
            $table->text('description');
            $table->integer('credit_hours')->default(3);
            $table->enum('level', ['undergraduate', 'graduate', 'certificate', 'continuing_education']);
            $table->string('subject_area'); // e.g., 'Mathematics', 'English', 'Computer Science'
            $table->json('prerequisites')->nullable(); // Array of prerequisite course codes
            $table->json('learning_outcomes')->nullable(); // Array of learning outcomes
            $table->json('competencies')->nullable(); // Skills/competencies gained
            $table->enum('delivery_method', ['in_person', 'online', 'hybrid', 'self_paced'])->default('in_person');
            $table->integer('typical_duration_weeks')->nullable();
            $table->decimal('typical_workload_hours_per_week', 4, 1)->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->json('tags')->nullable(); // Searchable tags
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable(); // Reference to global_user_id
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['subject_area']);
            $table->index(['level']);
            $table->index(['delivery_method']);
            $table->index(['difficulty_level']);
            $table->index(['is_active']);
            $table->index(['created_at']);
            
            // Full-text search index for title and description
            DB::statement('CREATE INDEX global_courses_search_idx ON global_courses USING gin(to_tsvector(\'english\', title || \' \' || description))');
        });

        // Create tenant_course_offerings table to link global courses to specific tenant implementations
        Schema::create('tenant_course_offerings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->uuid('global_course_id')->nullable(); // NULL if this is a tenant-specific course
            $table->string('local_course_code'); // Tenant's internal course code
            $table->string('local_title')->nullable(); // Override global title if needed
            $table->text('local_description')->nullable(); // Additional local description
            $table->integer('local_credit_hours')->nullable(); // Override global credit hours
            $table->uuid('primary_instructor_id')->nullable(); // Reference to global_user_id
            $table->string('semester');
            $table->integer('year');
            $table->integer('max_enrollment')->nullable();
            $table->integer('current_enrollment')->default(0);
            $table->decimal('tuition_cost', 10, 2)->nullable();
            $table->json('schedule')->nullable(); // Class schedule information
            $table->string('location')->nullable(); // Physical or virtual location
            $table->json('custom_fields')->nullable(); // Tenant-specific additional fields
            $table->enum('status', ['draft', 'published', 'enrollment_open', 'enrollment_closed', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->date('enrollment_start_date')->nullable();
            $table->date('enrollment_end_date')->nullable();
            $table->date('course_start_date')->nullable();
            $table->date('course_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('global_course_id')->references('id')->on('global_courses')->onDelete('set null');
            
            // Unique constraint - one offering per tenant per course per semester/year
            $table->unique(['tenant_id', 'local_course_code', 'semester', 'year']);
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['global_course_id']);
            $table->index(['semester', 'year']);
            $table->index(['enrollment_start_date', 'enrollment_end_date']);
            $table->index(['course_start_date', 'course_end_date']);
        });

        // Create super_admin_analytics table for cross-tenant analytics
        Schema::create('super_admin_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('metric_name'); // e.g., 'total_enrollments', 'revenue', 'completion_rate'
            $table->string('metric_type'); // e.g., 'count', 'sum', 'average', 'percentage'
            $table->json('metric_value'); // Flexible storage for different value types
            $table->string('tenant_id')->nullable(); // NULL for global metrics
            $table->string('time_period'); // e.g., 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'
            $table->date('period_start');
            $table->date('period_end');
            $table->json('dimensions')->nullable(); // Additional grouping dimensions
            $table->json('filters')->nullable(); // Filters applied to generate this metric
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            // Foreign key
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Indexes
            $table->index(['metric_name', 'time_period']);
            $table->index(['tenant_id', 'metric_name']);
            $table->index(['period_start', 'period_end']);
            $table->index(['calculated_at']);
            
            // Unique constraint to prevent duplicate metrics
            $table->unique(['metric_name', 'tenant_id', 'time_period', 'period_start', 'period_end']);
        });

        // Create data_sync_logs table for tracking cross-tenant data synchronization
        Schema::create('data_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sync_type'); // e.g., 'user_sync', 'course_sync', 'analytics_sync'
            $table->string('source_tenant_id')->nullable();
            $table->string('target_tenant_id')->nullable();
            $table->uuid('source_record_id')->nullable();
            $table->uuid('target_record_id')->nullable();
            $table->enum('operation', ['create', 'update', 'delete', 'sync']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'skipped']);
            $table->json('sync_data')->nullable(); // Data being synchronized
            $table->json('conflicts')->nullable(); // Any conflicts encountered
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['sync_type', 'status']);
            $table->index(['source_tenant_id']);
            $table->index(['target_tenant_id']);
            $table->index(['started_at']);
            $table->index(['status', 'retry_count']);
        });

        // Create audit_trail table for tracking changes across the global system
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('global_user_id')->nullable(); // Who made the change
            $table->string('tenant_id')->nullable(); // Which tenant context
            $table->string('table_name'); // Which table was affected
            $table->uuid('record_id'); // Which record was affected
            $table->enum('operation', ['create', 'update', 'delete', 'restore']);
            $table->json('old_values')->nullable(); // Previous values
            $table->json('new_values')->nullable(); // New values
            $table->json('changed_fields')->nullable(); // List of fields that changed
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('global_user_id')->references('global_user_id')->on('global_users')->onDelete('set null');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Indexes
            $table->index(['table_name', 'record_id']);
            $table->index(['global_user_id']);
            $table->index(['tenant_id']);
            $table->index(['operation']);
            $table->index(['created_at']);
        });

        // Add some initial global courses
        $this->seedGlobalCourses();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trail');
        Schema::dropIfExists('data_sync_logs');
        Schema::dropIfExists('super_admin_analytics');
        Schema::dropIfExists('tenant_course_offerings');
        Schema::dropIfExists('global_courses');
        Schema::dropIfExists('user_tenant_memberships');
        Schema::dropIfExists('global_users');
    }

    /**
     * Seed some initial global courses
     */
    private function seedGlobalCourses(): void
    {
        $globalCourses = [
            [
                'id' => DB::raw('gen_random_uuid()'),
                'global_course_code' => 'MATH-101',
                'title' => 'College Algebra',
                'description' => 'Fundamental algebraic concepts including linear equations, quadratic equations, polynomials, and functions.',
                'credit_hours' => 3,
                'level' => 'undergraduate',
                'subject_area' => 'Mathematics',
                'learning_outcomes' => json_encode([
                    'Solve linear and quadratic equations',
                    'Graph functions and analyze their properties',
                    'Perform operations with polynomials',
                    'Apply algebraic concepts to real-world problems'
                ]),
                'delivery_method' => 'in_person',
                'typical_duration_weeks' => 16,
                'typical_workload_hours_per_week' => 6.0,
                'difficulty_level' => 'beginner',
                'tags' => json_encode(['algebra', 'mathematics', 'foundational']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'global_course_code' => 'ENG-101',
                'title' => 'English Composition I',
                'description' => 'Introduction to academic writing, critical thinking, and research skills.',
                'credit_hours' => 3,
                'level' => 'undergraduate',
                'subject_area' => 'English',
                'learning_outcomes' => json_encode([
                    'Write clear and coherent essays',
                    'Develop critical thinking skills',
                    'Conduct basic research and cite sources',
                    'Analyze and interpret texts'
                ]),
                'delivery_method' => 'hybrid',
                'typical_duration_weeks' => 16,
                'typical_workload_hours_per_week' => 5.0,
                'difficulty_level' => 'beginner',
                'tags' => json_encode(['writing', 'composition', 'critical thinking']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'global_course_code' => 'CS-101',
                'title' => 'Introduction to Computer Science',
                'description' => 'Fundamental concepts of computer science including programming, algorithms, and data structures.',
                'credit_hours' => 4,
                'level' => 'undergraduate',
                'subject_area' => 'Computer Science',
                'prerequisites' => json_encode(['MATH-101']),
                'learning_outcomes' => json_encode([
                    'Understand basic programming concepts',
                    'Implement simple algorithms',
                    'Work with basic data structures',
                    'Apply problem-solving techniques'
                ]),
                'delivery_method' => 'in_person',
                'typical_duration_weeks' => 16,
                'typical_workload_hours_per_week' => 8.0,
                'difficulty_level' => 'intermediate',
                'tags' => json_encode(['programming', 'algorithms', 'computer science']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'global_course_code' => 'BIO-101',
                'title' => 'General Biology I',
                'description' => 'Introduction to biological principles including cell structure, genetics, and evolution.',
                'credit_hours' => 4,
                'level' => 'undergraduate',
                'subject_area' => 'Biology',
                'learning_outcomes' => json_encode([
                    'Understand cell structure and function',
                    'Explain basic genetic principles',
                    'Describe evolutionary processes',
                    'Apply scientific method to biological questions'
                ]),
                'delivery_method' => 'in_person',
                'typical_duration_weeks' => 16,
                'typical_workload_hours_per_week' => 7.0,
                'difficulty_level' => 'intermediate',
                'tags' => json_encode(['biology', 'science', 'laboratory']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'global_course_code' => 'HIST-101',
                'title' => 'World History I',
                'description' => 'Survey of world civilizations from ancient times to 1500 CE.',
                'credit_hours' => 3,
                'level' => 'undergraduate',
                'subject_area' => 'History',
                'learning_outcomes' => json_encode([
                    'Analyze historical events and their causes',
                    'Compare different civilizations and cultures',
                    'Evaluate primary and secondary sources',
                    'Understand historical chronology and context'
                ]),
                'delivery_method' => 'online',
                'typical_duration_weeks' => 16,
                'typical_workload_hours_per_week' => 5.0,
                'difficulty_level' => 'beginner',
                'tags' => json_encode(['history', 'civilization', 'culture']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('global_courses')->insert($globalCourses);
    }
};