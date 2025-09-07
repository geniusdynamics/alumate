# Tenant Models Audit Report

## Overview

This document provides a comprehensive audit of all models in the Alumate codebase that currently use the hybrid tenancy approach with `tenant_id` columns. These models need to be migrated to the pure schema-based approach.

## Models Requiring Migration

### High Priority - Core Business Models

These models contain critical business data and should be migrated first:

#### 1. ComponentTheme
- **File**: `app/Models/ComponentTheme.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: High - affects UI theming across the platform
- **Dependencies**: Templates, Landing Pages

#### 2. LandingPage
- **File**: `app/Models/LandingPage.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: High - core marketing functionality
- **Dependencies**: Templates, Analytics, ComponentThemes

#### 3. Template
- **File**: `app/Models/Template.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Very High - central to the platform
- **Dependencies**: TemplateVariants, Components, BrandConfigs

#### 4. Component
- **File**: `app/Models/Component.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: High - building blocks for templates
- **Dependencies**: Templates, ComponentThemes

#### 5. Graduate
- **File**: `app/Models/Graduate.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship
- **Migration Impact**: Critical - core user data
- **Dependencies**: Already in tenant schema, needs cleanup

### Medium Priority - Brand & Configuration Models

#### 6. BrandConfig
- **File**: `app/Models/BrandConfig.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - affects branding
- **Dependencies**: BrandColors, BrandFonts, BrandLogos

#### 7. BrandColor
- **File**: `app/Models/BrandColor.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - visual branding
- **Dependencies**: BrandConfig, Templates

#### 8. BrandFont
- **File**: `app/Models/BrandFont.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - typography branding
- **Dependencies**: BrandConfig, Templates

#### 9. BrandLogo
- **File**: `app/Models/BrandLogo.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - logo management
- **Dependencies**: BrandConfig

#### 10. BrandTemplate
- **File**: `app/Models/BrandTemplate.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - branded templates
- **Dependencies**: Templates, BrandConfig

#### 11. BrandGuidelines
- **File**: `app/Models/BrandGuidelines.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - brand documentation
- **Dependencies**: BrandConfig

### Medium Priority - Analytics & Performance Models

#### 12. AnalyticsEvent
- **File**: `app/Models/AnalyticsEvent.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship
- **Migration Impact**: Medium - tracking and insights
- **Dependencies**: Templates, LandingPages

#### 13. TemplateAnalyticsEvent
- **File**: `app/Models/TemplateAnalyticsEvent.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - template performance
- **Dependencies**: Templates, AnalyticsEvent

#### 14. TemplatePerformanceDashboard
- **File**: `app/Models/TemplatePerformanceDashboard.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - performance monitoring
- **Dependencies**: Templates, Analytics

#### 15. TemplatePerformanceReport
- **File**: `app/Models/TemplatePerformanceReport.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - reporting
- **Dependencies**: Templates, Analytics

#### 16. LandingPageAnalytics
- **File**: `app/Models/LandingPageAnalytics.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship
- **Migration Impact**: Medium - page performance
- **Dependencies**: LandingPages, AnalyticsEvent

### Medium Priority - Template & Variant Models

#### 17. TemplateVariant
- **File**: `app/Models/TemplateVariant.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - A/B testing
- **Dependencies**: Templates

#### 18. PublishedSite
- **File**: `app/Models/PublishedSite.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Medium - site publishing
- **Dependencies**: Templates, LandingPages

### Low Priority - Email & Communication Models

#### 19. EmailSequence
- **File**: `app/Models/EmailSequence.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - email automation
- **Dependencies**: EmailTemplates

#### 20. EmailTemplate
- **File**: `app/Models/EmailTemplate.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship
- **Migration Impact**: Low - email content
- **Dependencies**: EmailSequence

#### 21. EmailCampaign
- **File**: `app/Models/EmailCampaign.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship
- **Migration Impact**: Low - email marketing
- **Dependencies**: EmailTemplates, EmailSequence

#### 22. EmailAnalytics
- **File**: `app/Models/EmailAnalytics.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - email performance
- **Dependencies**: EmailCampaigns

#### 23. EmailAutomationRule
- **File**: `app/Models/EmailAutomationRule.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship
- **Migration Impact**: Low - automation logic
- **Dependencies**: EmailSequence

#### 24. EmailPreference
- **File**: `app/Models/EmailPreference.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship
- **Migration Impact**: Low - user preferences
- **Dependencies**: Users

### Low Priority - Integration & Sync Models

#### 25. TemplateCrmIntegration
- **File**: `app/Models/TemplateCrmIntegration.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - CRM integration
- **Dependencies**: Templates

#### 26. TemplateCrmSyncLog
- **File**: `app/Models/TemplateCrmSyncLog.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - sync logging
- **Dependencies**: TemplateCrmIntegration

#### 27. CrmSyncLog
- **File**: `app/Models/CrmSyncLog.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - general sync logging
- **Dependencies**: Various integrations

### Low Priority - Notification & Logging Models

#### 28. NotificationTemplate
- **File**: `app/Models/NotificationTemplate.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - system notifications
- **Dependencies**: None

#### 29. NotificationLog
- **File**: `app/Models/NotificationLog.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope
- **Migration Impact**: Low - notification history
- **Dependencies**: NotificationTemplate

#### 30. NotificationPreference
- **File**: `app/Models/NotificationPreference.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - user preferences
- **Dependencies**: Users

#### 31. SecurityLog
- **File**: `app/Models/SecurityLog.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship, forTenant scope
- **Migration Impact**: Low - security auditing
- **Dependencies**: Users

#### 32. ActivityLog
- **File**: `app/Models/ActivityLog.php`
- **Current State**: Uses `tenant_id` in fillable
- **Migration Impact**: Low - activity tracking
- **Dependencies**: Users

#### 33. BehaviorEvent
- **File**: `app/Models/BehaviorEvent.php`
- **Current State**: Uses `tenant_id` in fillable, tenant relationship, forTenant scope
- **Migration Impact**: Low - user behavior tracking
- **Dependencies**: Users

#### 34. Testimonial
- **File**: `app/Models/Testimonial.php`
- **Current State**: Uses `tenant_id` in fillable, has global scope, tenant relationship
- **Migration Impact**: Low - testimonial management
- **Dependencies**: Users

## Models to Keep in Central Database

These models should remain in the central database as they handle cross-tenant functionality:

### 1. Tenant
- **File**: `app/Models/Tenant.php`
- **Reason**: Core tenancy model
- **Action**: No migration needed

### 2. Domain
- **File**: `app/Models/Domain.php`
- **Reason**: Tenant resolution
- **Action**: No migration needed

### 3. User
- **File**: `app/Models/User.php`
- **Reason**: Cross-tenant authentication
- **Action**: Keep central, may need relationship updates

### 4. Course
- **File**: `app/Models/Course.php`
- **Reason**: References institution_id (tenant_id)
- **Action**: Evaluate if should move to tenant schema

### 5. Integration Models
- **SsoConfiguration**: Cross-tenant SSO
- **IntegrationConfiguration**: Cross-tenant integrations

## Migration Strategy Summary

### Phase 1: Core Business Models (Week 1)
- ComponentTheme
- LandingPage
- Template
- Component
- Graduate (cleanup)

### Phase 2: Brand & Configuration (Week 2, Days 1-2)
- BrandConfig
- BrandColor
- BrandFont
- BrandLogo
- BrandTemplate
- BrandGuidelines

### Phase 3: Analytics & Performance (Week 2, Days 3-4)
- AnalyticsEvent
- TemplateAnalyticsEvent
- TemplatePerformanceDashboard
- TemplatePerformanceReport
- LandingPageAnalytics
- TemplateVariant
- PublishedSite

### Phase 4: Email & Communication (Week 2, Day 5)
- EmailSequence
- EmailTemplate
- EmailCampaign
- EmailAnalytics
- EmailAutomationRule
- EmailPreference

### Phase 5: Integration & Logging (Week 3, Day 1)
- TemplateCrmIntegration
- TemplateCrmSyncLog
- CrmSyncLog
- NotificationTemplate
- NotificationLog
- NotificationPreference
- SecurityLog
- ActivityLog
- BehaviorEvent
- Testimonial

## Common Patterns to Remove

### 1. Global Scopes
```php
// REMOVE THIS PATTERN
protected static function booted()
{
    static::addGlobalScope('tenant', function (Builder $builder) {
        $builder->where('tenant_id', tenant()->id);
    });
}
```

### 2. Tenant Relationships
```php
// REMOVE THIS PATTERN
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}
```

### 3. ForTenant Scopes
```php
// REMOVE THIS PATTERN
public function scopeForTenant($query, int $tenantId)
{
    return $query->where('tenant_id', $tenantId);
}
```

### 4. Fillable tenant_id
```php
// REMOVE FROM FILLABLE ARRAYS
protected $fillable = [
    // ... other fields
    'tenant_id', // REMOVE THIS
];
```

## Validation Rules to Update

Many models have validation rules that reference tenant_id:

```php
// REMOVE THESE VALIDATION RULES
'tenant_id' => 'required|exists:tenants,id',
```

## Total Migration Count

- **Models to migrate**: 34
- **High priority**: 5 models
- **Medium priority**: 18 models  
- **Low priority**: 11 models
- **Models to keep central**: 5 models

## Risk Assessment

### High Risk
- Template, Component, ComponentTheme: Core platform functionality
- Graduate: Critical user data (already in tenant schema)

### Medium Risk
- LandingPage: Marketing functionality
- Brand models: Visual identity
- Analytics models: Reporting and insights

### Low Risk
- Email models: Supplementary functionality
- Logging models: Audit trails
- Integration models: External connections

## Success Criteria

1. All 34 models successfully migrated
2. Zero `tenant_id` columns in tenant schemas
3. No global scopes for tenant filtering
4. All tests passing
5. Performance improvements validated
6. Data integrity maintained

This audit provides the foundation for our systematic migration to pure schema-based multi-tenancy.