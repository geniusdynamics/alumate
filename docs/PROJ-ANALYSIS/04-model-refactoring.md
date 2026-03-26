# Model Refactoring Guide

## Executive Summary

This document provides detailed guidance on refactoring the Alumate platform's model layer, addressing god objects, unclear responsibilities, and complex inheritance patterns.

### Current Issues

- **257 models** with inconsistent patterns
- **God objects**: User.php (967 lines), Job.php (573 lines)
- **Complex boot() methods** with service dependencies
- **Mixed concerns**: Authentication + Profile + Tenancy in single models
- **TODO comments** indicating incomplete features

### Target State

- **~150 models** (40% reduction through consolidation)
- **Maximum 300 lines** per model
- **Clear responsibilities** per model
- **No service dependencies** in models
- **Complete features** (no TODOs)

---

## Refactoring Principles

### Principle 1: Single Responsibility

**Bad:**
```php
class User extends Authenticatable
{
    // Authentication
    // Profile management  
    // Graduation data
    // Mentoring
    // Preferences
    // 967 lines of everything...
}
```

**Good:**
```php
class User extends Authenticatable
{
    // Authentication only
    // ~200 lines
}

class UserProfile extends Model
{
    // Profile data (name, bio, avatar)
    // ~150 lines
}

class UserGraduationInfo extends Model
{
    // Graduation year, degree, institution
    // ~100 lines
}

class UserPreferences extends Model
{
    // Settings, notifications, appearance
    // ~100 lines
}
```

### Principle 2: No Service Dependencies

**Bad:**
```php
class Graduate extends Model
{
    protected static function boot()
    {
        static::addGlobalScope('tenant_context', function (Builder $builder) {
            $tenantService = app(TenantContextService::class);
            // Service dependency in model!
        });
    }
}
```

**Good:**
```php
class Graduate extends Model
{
    // Pure model - no service dependencies
    // Tenancy handled by schema, not global scopes
    
    protected static function boot()
    {
        parent::boot();
        // Only model-specific logic
    }
}
```

### Principle 3: Explicit Relationships

**Bad:**
```php
public function tenant()
{
    return $this->belongsTo(Tenant::class)
                ->where('id', app(TenantContextService::class)->getCurrentTenantId());
}
```

**Good:**
```php
public function institution()
{
    return $this->belongsTo(Institution::class);
}
// Clear, explicit, testable
```

---

## Detailed Refactoring Plans

### Model #1: User Decomposition

**Current State:** 967 lines handling authentication, profile, graduation, mentoring, preferences

**Target Structure:**

```
app/Models/
├── User.php (200 lines) - Authentication & authorization only
├── UserProfile.php (150 lines) - Personal information
├── UserGraduationInfo.php (100 lines) - Academic data
├── UserPreferences.php (100 lines) - Settings & preferences
├── UserMentorProfile.php (120 lines) - Mentorship data
└── UserEmploymentHistory.php (150 lines) - Work experience
```

**Step-by-Step:**

#### Step 1: Extract UserProfile

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProfile extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'bio',
        'avatar_path',
        'phone',
        'location',
        'website',
        'linkedin_url',
        'twitter_handle',
    ];
    
    protected $casts = [
        'user_id' => 'integer',
    ];
    
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
    
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

**Migration:**
```php
Schema::create('user_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('first_name');
    $table->string('last_name');
    $table->text('bio')->nullable();
    $table->string('avatar_path')->nullable();
    $table->string('phone')->nullable();
    $table->string('location')->nullable();
    $table->string('website')->nullable();
    $table->string('linkedin_url')->nullable();
    $table->string('twitter_handle')->nullable();
    $table->timestamps();
    
    $table->index('user_id');
});
```

#### Step 2: Migrate User Model

Move fields from User to UserProfile:

```php
// Data migration
$userProfiles = DB::table('users')
    ->select('id', 'first_name', 'last_name', 'phone', 'location', 'bio')
    ->get();

foreach ($userProfiles as $userData) {
    DB::table('user_profiles')->insert([
        'user_id' => $userData->id,
        'first_name' => $userData->first_name,
        'last_name' => $userData->last_name,
        'phone' => $userData->phone,
        'location' => $userData->location,
        'bio' => $userData->bio,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Remove old columns from users table (after validation period)
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn(['first_name', 'last_name', 'phone', 'location', 'bio']);
});
```

*(Continue with similar extractions for other concerns)*

---

### Model #2: Graduate Simplification

**Current State:** Uses TenantContextService in boot(), complex global scopes

**Target State:** Pure model, schema-based tenancy

**Refactoring Steps:**

1. Remove TenantContextService dependency
2. Remove global scope for tenancy
3. Add proper relationships
4. Extract business logic to service layer

```php
// AFTER refactoring
class Graduate extends Model
{
    protected $fillable = [
        'student_id',
        'name',
        'email',
        'graduation_year',
        'course_id',
        'employment_status',
        // ... other fields
    ];
    
    // Remove boot() method entirely or keep minimal
    protected static function boot()
    {
        parent::boot();
        
        // Model lifecycle events only
        static::creating(function ($graduate) {
            $graduate->generateStudentId();
        });
    }
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function profile()
    {
        return $this->hasOne(GraduateProfile::class);
    }
    
    // Business logic moves to service
    // NO tenant filtering here!
}
```

---

### Model #3: Job Model Cleanup

**Current State:** 573 lines, mixed concerns

**Issues Found:**
- TODO comments
- Incomplete features
- Complex accessors

**Target Structure:**

```
app/Models/
├── Job.php (200 lines) - Core job data
├── JobPosting.php (150 lines) - Posting details
├── JobRequirement.php (100 lines) - Requirements
└── JobStatistics.php (120 lines) - Analytics
```

---

## Handling Incomplete Features

### Issue: TODO Comments in Models

**Example from BrandGuidelines.php:**
```php
// TODO: Create BrandGuidelineReview model for tracking approval history
// public function reviewHistory(): HasMany
// {
//     return $this->hasMany(BrandGuidelineReview::class);
// }
```

**Resolution Process:**

1. **Audit all TODOs:**
```bash
grep -r "TODO" app/Models/ --include="*.php" -n
# Output: 15-20 instances
```

2. **Categorize:**
- Must implement: Core functionality
- Nice to have: Enhancement
- Obsolete: Remove comment

3. **Implement or Remove:**
For each TODO:
- Create task in project board
- Implement if critical
- Remove comment if obsolete

---

## Testing Strategy

### Unit Tests for Refactored Models

```php
<?php

// tests/Unit/Models/UserProfileTest.php

use App\Models\User;
use App\Models\UserProfile;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create(['user_id' => $user->id]);
    
    expect($profile->user->id)->toBe($user->id);
});

it('calculates full name correctly', function () {
    $profile = UserProfile::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    expect($profile->full_name)->toBe('John Doe');
});

it('handles nullable fields', function () {
    $profile = UserProfile::factory()->create([
        'bio' => null,
        'phone' => null,
        'location' => null,
    ]);
    
    expect($profile->bio)->toBeNull();
});
```

### Integration Tests

```php
<?php

// tests/Integration/Models/UserRelationshipTest.php

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserGraduationInfo;

it('loads all related data efficiently', function () {
    $user = User::factory()->create();
    UserProfile::factory()->create(['user_id' => $user->id]);
    UserGraduationInfo::factory()->create(['user_id' => $user->id]);
    
    // Eager loading should work
    $loadedUser = User::with(['profile', 'graduationInfo'])
        ->find($user->id);
    
    expect($loadedUser->relationLoaded('profile'))->toBeTrue();
    expect($loadedUser->relationLoaded('graduationInfo'))->toBeTrue();
});
```

---

## Migration Strategy

### Phase 1: Parallel Run (Week 1-2)

Keep both old and new models working:

```php
// Old User model still has all fields
// New UserProfile model created
// Both work simultaneously
```

### Phase 2: Data Migration (Week 3)

Move data to new tables:

```bash
php artisan migrate:create create_user_profiles_table
php artisan migrate
php artisan db:seed UserProfileSeeder
```

### Phase 3: Code Migration (Week 4)

Update code to use new models:

```php
// BEFORE
$user->first_name = 'John';
$user->save();

// AFTER
$user->profile->first_name = 'John';
$user->profile->save();
```

### Phase 4: Cleanup (Week 5)

Remove old columns, delete deprecated code:

```bash
php artisan make:migration remove_old_fields_from_users_table
```

---

## Success Criteria

✅ All models under 300 lines  
✅ Zero service dependencies in models  
✅ No TODO comments in production code  
✅ 100% test coverage for model relationships  
✅ Clear naming indicating responsibility  
✅ No global scopes for tenancy  

---

## Next Steps

Continue reading:
- [Database Optimization Plan](./05-database-optimization.md)
- [Frontend Improvements](./06-frontend-improvements.md)
- [Implementation Roadmap](./08-implementation-roadmap.md)
