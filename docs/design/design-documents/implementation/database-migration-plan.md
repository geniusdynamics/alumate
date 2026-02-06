# Database Migration Plan

## Overview
This document outlines the database migrations needed to implement the template creation system and brand management features.

## Migration Order

### Phase 1: Core Template System
1. `create_templates_table` - Template model with JSON structure and performance metrics
2. `create_landing_pages_table` - LandingPage model with tenant isolation
3. `create_brand_guidelines_table` - BrandGuidelines model for tenant-specific guidelines
4. `create_brand_colors_table` - BrandColor model for color palettes
5. `create_brand_fonts_table` - BrandFont model for font configurations
6. `create_brand_logos_table` - BrandLogo model for logo assets
7. `create_brand_templates_table` - BrandTemplate model for predefined configurations
8. `create_brand_template_colors_table` - Pivot table for BrandTemplate ↔ BrandColor relationship

### Phase 2: Template Components and Instances
9. `add_template_relationships_to_components` - Add template_id to components table
10. `create_component_instances_table` - ComponentInstance model for template usage
11. `create_template_categories_table` - Template categorization system
12. `create_template_tags_table` - Template tagging system
13. `create_template_tag_pivot_table` - Many-to-many relationship between templates and tags

### Phase 3: Analytics and Tracking
14. `create_template_analytics_table` - Template usage and performance tracking
15. `create_landing_page_analytics_table` - Landing page performance tracking
16. `create_template_ratings_table` - Template rating and feedback system
17. `create_ab_template_tests_table` - A/B testing for templates
18. `create_ab_template_test_assignments_table` - User assignments to template tests
19. `create_ab_template_test_conversions_table` - Conversion tracking for template tests

### Phase 4: Advanced Features
20. `create_template_versions_table` - Version control for templates
21. `create_template_favorites_table` - User favorite templates tracking
22. `create_template_downloads_table` - Template download/export tracking
23. `create_template_imports_table` - Template import tracking
24. `create_template_shares_table` - Template sharing functionality

## Detailed Migration Specifications

### 1. create_templates_table
```php
Schema::create('templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('category');
    $table->string('audience_type');
    $table->string('campaign_type');
    $table->json('structure')->nullable(); // Template structure JSON
    $table->json('default_config')->nullable(); // Default configuration
    $table->json('performance_metrics')->nullable(); // Performance tracking data
    $table->string('preview_image')->nullable();
    $table->string('preview_url')->nullable();
    $table->integer('version')->default(1);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_premium')->default(false);
    $table->unsignedInteger('usage_count')->default(0);
    $table->timestamp('last_used_at')->nullable();
    $table->json('tags')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();
    
    // Indexes
    $table->index(['tenant_id', 'category']);
    $table->index(['tenant_id', 'audience_type']);
    $table->index(['tenant_id', 'campaign_type']);
    $table->index(['is_active', 'created_at']);
    $table->index('usage_count');
});
```

### 2. create_landing_pages_table
```php
Schema::create('landing_pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('template_id')->constrained()->onDelete('cascade');
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->json('config')->nullable(); // Page-specific configuration
    $table->json('brand_config')->nullable(); // Brand-specific overrides
    $table->string('audience_type');
    $table->string('campaign_type');
    $table->string('category');
    $table->string('status')->default('draft'); // draft, reviewing, published, archived, suspended
    $table->timestamp('published_at')->nullable();
    $table->string('draft_hash')->nullable(); // For tracking unpublished changes
    $table->integer('version')->default(1);
    $table->unsignedInteger('usage_count')->default(0);
    $table->unsignedInteger('conversion_count')->default(0);
    $table->string('preview_url')->nullable();
    $table->string('public_url')->nullable();
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->json('seo_keywords')->nullable();
    $table->string('social_image')->nullable();
    $table->string('tracking_id')->nullable();
    $table->string('favicon_url')->nullable();
    $table->text('custom_css')->nullable();
    $table->text('custom_js')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();
    
    // Indexes
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'category']);
    $table->index(['tenant_id', 'audience_type']);
    $table->index(['tenant_id', 'campaign_type']);
    $table->index('published_at');
    $table->index('usage_count');
});
```

### 3. create_brand_guidelines_table
```php
Schema::create('brand_guidelines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->boolean('enforce_color_palette')->default(true);
    $table->boolean('require_contrast_check')->default(true);
    $table->decimal('min_contrast_ratio', 3, 1)->default(4.5);
    $table->boolean('enforce_font_families')->default(true);
    $table->boolean('enforce_typography_scale')->default(true);
    $table->unsignedSmallInteger('max_heading_size')->default(48);
    $table->unsignedSmallInteger('max_body_size')->default(18);
    $table->boolean('enforce_logo_placement')->default(true);
    $table->unsignedSmallInteger('min_logo_size')->default(32);
    $table->decimal('logo_clear_space', 3, 1)->default(1.5);
    $table->timestamps();
    
    // Unique constraint - only one guidelines record per tenant
    $table->unique('tenant_id');
});
```

### 4. create_brand_colors_table
```php
Schema::create('brand_colors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('value', 7); // Hex color code
    $table->string('type'); // primary, secondary, accent, neutral, etc.
    $table->json('usage_guidelines')->nullable();
    $table->unsignedInteger('usage_count')->default(0);
    $table->json('contrast_ratios')->nullable();
    $table->json('accessibility')->nullable();
    $table->timestamps();
    
    // Indexes
    $table->index(['tenant_id', 'type']);
    $table->index('usage_count');
});
```

### 5. create_brand_fonts_table
```php
Schema::create('brand_fonts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('family');
    $table->json('weights'); // Array of available weights
    $table->json('styles'); // Array of available styles
    $table->boolean('is_primary')->default(false);
    $table->string('type'); // system, google, custom
    $table->string('source'); // url for custom fonts
    $table->string('url')->nullable();
    $table->json('fallbacks'); // Array of fallback fonts
    $table->unsignedInteger('usage_count')->default(0);
    $table->string('loading_strategy')->default('swap'); // swap, block, optional
    $table->timestamps();
    
    // Indexes
    $table->index(['tenant_id', 'is_primary']);
    $table->index(['tenant_id', 'type']);
    $table->index('usage_count');
});
```

### 6. create_brand_logos_table
```php
Schema::create('brand_logos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('type'); // primary, secondary, favicon, etc.
    $table->string('url');
    $table->string('alt')->nullable();
    $table->unsignedInteger('size')->nullable();
    $table->string('mime_type')->nullable();
    $table->boolean('is_primary')->default(false);
    $table->boolean('optimized')->default(false);
    $table->string('cdn_url')->nullable();
    $table->json('variants')->nullable(); // Different sizes/formats
    $table->json('usage_guidelines')->nullable(); // Size requirements, clear space, etc.
    $table->timestamps();
    
    // Indexes
    $table->index(['tenant_id', 'is_primary']);
    $table->index(['tenant_id', 'type']);
});
```

### 7. create_brand_templates_table
```php
Schema::create('brand_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('primary_font')->nullable();
    $table->string('secondary_font')->nullable();
    $table->string('logo_variant')->nullable();
    $table->json('tags')->nullable();
    $table->boolean('is_default')->default(false);
    $table->unsignedInteger('usage_count')->default(0);
    $table->timestamps();
    
    // Indexes
    $table->index(['tenant_id', 'is_default']);
    $table->index('usage_count');
});
```

### 8. create_brand_template_colors_table
```php
Schema::create('brand_template_colors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('brand_template_id')->constrained()->onDelete('cascade');
    $table->foreignId('brand_color_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    
    // Prevent duplicate associations
    $table->unique(['brand_template_id', 'brand_color_id']);
});
```

## Indexing Strategy

### Primary Keys
All tables will use auto-incrementing big integers as primary keys.

### Foreign Keys
All foreign key relationships will have proper constraints with cascade/delete behaviors where appropriate.

### Performance Indexes
1. **Tenant ID Indexes**: All tenant-scoped tables will have indexes on tenant_id for fast tenant isolation
2. **Category Indexes**: Template categorization fields will be indexed for fast filtering
3. **Status Indexes**: Status fields will be indexed for quick state queries
4. **Timestamp Indexes**: Created_at and updated_at fields will be indexed for sorting
5. **Usage Count Indexes**: Usage tracking fields will be indexed for popularity queries
6. **Search Indexes**: Name and description fields will be indexed for full-text search capabilities

## Tenant Isolation

All tables except `brand_guidelines` (which has a unique constraint per tenant) will implement tenant isolation through:
- Foreign key relationships to the `tenants` table
- Indexes on tenant_id for performance
- Cascade delete behavior to clean up tenant data

## Migration Execution Order

Migrations should be executed in the order specified above to ensure proper foreign key relationships are maintained. Each migration will be timestamped appropriately to maintain Laravel's migration order.

## Rollback Strategy

Each migration will include a proper `down()` method to reverse the changes, allowing for safe rollbacks during development and emergency situations.

## Testing Strategy

1. **Unit Tests**: Each migration will be tested individually to ensure proper table creation
2. **Integration Tests**: Foreign key relationships will be verified through integration tests
3. **Performance Tests**: Index effectiveness will be validated with large dataset tests
4. **Tenant Isolation Tests**: Multi-tenant scenarios will be tested to ensure proper data separation

## Monitoring and Maintenance

1. **Migration Health Checks**: Automated checks will verify migration success
2. **Performance Monitoring**: Query performance will be monitored post-migration
3. **Data Integrity Checks**: Regular integrity checks will ensure referential integrity
4. **Backup Procedures**: Automated backups will be implemented before major migration runs