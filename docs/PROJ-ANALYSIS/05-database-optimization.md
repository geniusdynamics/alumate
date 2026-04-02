# Database Schema Optimization Plan

## Executive Summary

This document outlines the strategy for optimizing the Alumate platform's database schema, addressing migration fragmentation, missing indexes, and query performance issues.

### Current Issues

- **275 migrations** with overlapping changes
- Fragmented schema evolution
- Missing composite indexes
- N+1 query problems
- Inconsistent naming conventions

### Target State

- Consolidated migrations (<100 files)
- Comprehensive indexing strategy
- Optimized query patterns
- Clear schema documentation

---

## Migration Cleanup Strategy

### Phase 1: Audit (Week 1)

**Step 1: Identify Duplicate Migrations**

```bash
#!/bin/bash
# scripts/database/audit-migrations.sh

echo "=== MIGRATION AUDIT ==="
echo ""

# Find duplicate table creations
echo "Duplicate Table Creations:"
grep -r "Schema::create" database/migrations/ --include="*.php" | \
    sed 's/.*Schema::create('\''//' | sed 's/'\''.*//' | \
    sort | uniq -c | sort -rn | \
    awk '$1 > 1 {print $2 " (" $1 " times)"}'

echo ""
echo "Orphaned Migrations (no effect):"
# Check for migrations that modify non-existent tables
php artisan migrate:status | grep "No"
```

**Expected Output:**
```
=== MIGRATION AUDIT ===

Duplicate Table Creations:
analytics_events (3 times)
consents (2 times)
homepage_content (2 times)

Orphaned Migrations:
✓ 2025_09_29_133000_create_learning_progress_table.php
```

### Phase 2: Consolidation (Week 2-3)

**Create Master Migration Files:**

```php
<?php

// database/migrations/tenant/2026_01_01_000000_consolidated_analytics_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidates:
     * - 2025_08_10_033110_create_analytics_events_table.php
     * - 2025_08_10_034256_create_analytics_conversions_table.php
     * - 2025_08_10_034743_create_analytics_errors_table.php
     * - 2025_08_15_071729_create_analytics_tracking_tables.php
     */
    public function up(): void
    {
        // Analytics Events Table
        if (!Schema::hasTable('analytics_events')) {
            Schema::create('analytics_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_id')->unique();
                $table->string('event_type');
                $table->jsonb('properties');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('occurred_at');
                $table->timestamps();
                
                // Composite indexes
                $table->index(['event_type', 'occurred_at']);
                $table->index(['user_id', 'occurred_at']);
            });
        }
        
        // Analytics Conversions Table
        if (!Schema::hasTable('analytics_conversions')) {
            Schema::create('analytics_conversions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('analytics_events')->cascadeOnDelete();
                $table->string('conversion_type');
                $table->decimal('value', 10, 2)->default(0);
                $table->timestamps();
                
                $table->index(['conversion_type', 'created_at']);
            });
        }
        
        // Additional tables...
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_conversions');
        Schema::dropIfExists('analytics_events');
    }
};
```

### Phase 3: Index Optimization (Week 4)

**Add Missing Indexes:**

```php
<?php

// database/migrations/tenant/2026_02_01_000000_add_missing_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Graduate model indexes
        if (!Schema::hasIndex('graduates', 'graduates_employment_status_idx')) {
            Schema::table('graduates', function (Blueprint $table) {
                $table->index('employment_status', 'graduates_employment_status_idx');
                $table->index(['graduation_year', 'course_id'], 'graduates_year_course_idx');
            });
        }
        
        // Job applications indexes
        if (!Schema::hasIndex('job_applications', 'job_applications_status_idx')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'job_applications_status_date_idx');
                $table->index(['graduate_id', 'status'], 'job_applications_graduate_status_idx');
            });
        }
        
        // Event registrations composite index
        if (!Schema::hasIndex('event_registrations', 'event_registrations_composite_idx')) {
            Schema::table('event_registrations', function (Blueprint $table) {
                $table->index(['event_id', 'user_id', 'status'], 'event_registrations_composite_idx');
            });
        }
        
        // Analyze index usage
        DB::statement('ANALYZE');
    }
    
    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropIndex('graduates_employment_status_idx');
            $table->dropIndex('graduates_year_course_idx');
        });
        
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex('job_applications_status_date_idx');
            $table->dropIndex('job_applications_graduate_status_idx');
        });
        
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropIndex('event_registrations_composite_idx');
        });
    }
};
```

---

## Query Performance Optimization

### N+1 Query Elimination

**Before:**
```php
// Controller code causing N+1
$graduates = Graduate::all();

foreach ($graduates as $graduate) {
    echo $graduate->course->name; // N+1 query!
    echo $graduate->user->email;  // Another N+1!
}
```

**After:**
```php
// Eager loading
$graduates = Graduate::with(['course', 'user'])->get();

foreach ($graduates as $graduate) {
    echo $graduate->course->name; // No additional query
    echo $graduate->user->email;  // No additional query
}
```

### Query Caching

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsQueryService
{
    public function getEmploymentRate(int $graduationYear): float
    {
        return Cache::remember(
            "analytics.employment_rate.{$graduationYear}",
            3600, // 1 hour
            function () use ($graduationYear) {
                $total = DB::table('graduates')
                    ->where('graduation_year', $graduationYear)
                    ->count();
                
                $employed = DB::table('graduates')
                    ->where('graduation_year', $graduationYear)
                    ->whereIn('employment_status', ['employed', 'self_employed'])
                    ->count();
                
                return $total > 0 ? ($employed / $total) * 100 : 0;
            }
        );
    }
}
```

---

## Success Criteria

✅ Migrations consolidated from 275 to <100 files  
✅ All critical queries have appropriate indexes  
✅ N+1 queries eliminated  
✅ Average query time <100ms  
✅ 95%+ cache hit rate for frequently accessed data  

---

## Next Steps

Continue reading:
- [Frontend Improvements](./06-frontend-improvements.md)
- [Testing Strategy](./07-testing-strategy.md)
