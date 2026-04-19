# API Layer Plan

## Overview
This document outlines the API layer implementation for the template creation system and brand management features.

## API Endpoints Structure

### Base URL Pattern
```
/api/v1/tenants/{tenant_id}/templates
/api/v1/tenants/{tenant_id}/landing-pages
/api/v1/tenants/{tenant_id}/brand
```

### Authentication
All endpoints will require:
- JWT Bearer Token authentication
- Tenant context validation
- Role-based access control
- Rate limiting per tenant/IP

## Template API Endpoints

### Template Management
```
GET    /api/v1/tenants/{tenant_id}/templates
POST   /api/v1/tenants/{tenant_id}/templates
GET    /api/v1/tenants/{tenant_id}/templates/{template_id}
PUT    /api/v1/tenants/{tenant_id}/templates/{template_id}
DELETE /api/v1/tenants/{tenant_id}/templates/{template_id}
POST   /api/v1/tenants/{tenant_id}/templates/{template_id}/duplicate
POST   /api/v1/tenants/{tenant_id}/templates/{template_id}/version
GET    /api/v1/tenants/{tenant_id}/templates/{template_id}/preview
GET    /api/v1/tenants/{tenant_id}/templates/categories
GET    /api/v1/tenants/{tenant_id}/templates/audience-types
GET    /api/v1/tenants/{tenant_id}/templates/campaign-types
```

### Template Search and Filtering
```
GET /api/v1/tenants/{tenant_id}/templates?search={query}&category={category}&audience_type={type}&campaign_type={type}&status={status}&sort={field}&direction={asc|desc}&per_page={count}
```

### Template Analytics
```
GET /api/v1/tenants/{tenant_id}/templates/{template_id}/analytics
GET /api/v1/tenants/{tenant_id}/templates/{template_id}/performance
GET /api/v1/tenants/{tenant_id}/templates/recommendations
```

## Landing Page API Endpoints

### Landing Page Management
```
GET    /api/v1/tenants/{tenant_id}/landing-pages
POST   /api/v1/tenants/{tenant_id}/landing-pages
GET    /api/v1/tenants/{tenant_id}/landing-pages/{page_id}
PUT    /api/v1/tenants/{tenant_id}/landing-pages/{page_id}
DELETE /api/v1/tenants/{tenant_id}/landing-pages/{page_id}
POST   /api/v1/tenants/{tenant_id}/landing-pages/{page_id}/publish
POST   /api/v1/tenants/{tenant_id}/landing-pages/{page_id}/unpublish
POST   /api/v1/tenants/{tenant_id}/landing-pages/{page_id}/archive
POST   /api/v1/tenants/{tenant_id}/landing-pages/{page_id}/suspend
GET    /api/v1/tenants/{tenant_id}/landing-pages/{page_id}/preview
GET    /api/v1/tenants/{tenant_id}/landing-pages/{page_id}/analytics
```

### Landing Page Status Management
```
POST /api/v1/tenants/{tenant_id}/landing-pages/{page_id}/status/{action}
```
Where action can be: publish, unpublish, archive, suspend

## Brand Management API Endpoints

### Brand Logo Management
```
GET    /api/v1/tenants/{tenant_id}/brand/logos
POST   /api/v1/tenants/{tenant_id}/brand/logos
GET    /api/v1/tenants/{tenant_id}/brand/logos/{logo_id}
PUT    /api/v1/tenants/{tenant_id}/brand/logos/{logo_id}
DELETE /api/v1/tenants/{tenant_id}/brand/logos/{logo_id}
POST   /api/v1/tenants/{tenant_id}/brand/logos/{logo_id}/optimize
POST   /api/v1/tenants/{tenant_id}/brand/logos/{logo_id}/set-primary
```

### Brand Color Management
```
GET    /api/v1/tenants/{tenant_id}/brand/colors
POST   /api/v1/tenants/{tenant_id}/brand/colors
GET    /api/v1/tenants/{tenant_id}/brand/colors/{color_id}
PUT    /api/v1/tenants/{tenant_id}/brand/colors/{color_id}
DELETE /api/v1/tenants/{tenant_id}/brand/colors/{color_id}
```

### Brand Font Management
```
GET    /api/v1/tenants/{tenant_id}/brand/fonts
POST   /api/v1/tenants/{tenant_id}/brand/fonts
GET    /api/v1/tenants/{tenant_id}/brand/fonts/{font_id}
PUT    /api/v1/tenants/{tenant_id}/brand/fonts/{font_id}
DELETE /api/v1/tenants/{tenant_id}/brand/fonts/{font_id}
POST   /api/v1/tenants/{tenant_id}/brand/fonts/{font_id}/set-primary
```

### Brand Template Management
```
GET    /api/v1/tenants/{tenant_id}/brand/templates
POST   /api/v1/tenants/{tenant_id}/brand/templates
GET    /api/v1/tenants/{tenant_id}/brand/templates/{template_id}
PUT    /api/v1/tenants/{tenant_id}/brand/templates/{template_id}
DELETE /api/v1/tenants/{tenant_id}/brand/templates/{template_id}
POST   /api/v1/tenants/{tenant_id}/brand/templates/{template_id}/duplicate
POST   /api/v1/tenants/{tenant_id}/brand/templates/{template_id}/apply
```

### Brand Guidelines Management
```
GET /api/v1/tenants/{tenant_id}/brand/guidelines
PUT /api/v1/tenants/{tenant_id}/brand/guidelines
```

### Brand Consistency Checking
```
POST /api/v1/tenants/{tenant_id}/brand/consistency-check
POST /api/v1/tenants/{tenant_id}/brand/auto-fix-issue/{issue_id}
```

## A/B Testing API Endpoints

### Test Management
```
GET    /api/v1/tenants/{tenant_id}/ab-tests
POST   /api/v1/tenants/{tenant_id}/ab-tests
GET    /api/v1/tenants/{tenant_id}/ab-tests/{test_id}
PUT    /api/v1/tenants/{tenant_id}/ab-tests/{test_id}
DELETE /api/v1/tenants/{tenant_id}/ab-tests/{test_id}
POST   /api/v1/tenants/{tenant_id}/ab-tests/{test_id}/start
POST   /api/v1/tenants/{tenant_id}/ab-tests/{test_id}/stop
GET    /api/v1/tenants/{tenant_id}/ab-tests/{test_id}/results
```

### Test Assignment and Conversion
```
POST /api/v1/ab-tests/{test_id}/assign
POST /api/v1/ab-tests/{test_id}/convert
```

## Analytics API Endpoints

### Template Analytics
```
GET /api/v1/tenants/{tenant_id}/analytics/templates/usage
GET /api/v1/tenants/{tenant_id}/analytics/templates/conversions
GET /api/v1/tenants/{tenant_id}/analytics/templates/performance
GET /api/v1/tenants/{tenant_id}/analytics/templates/trends
```

### Landing Page Analytics
```
GET /api/v1/tenants/{tenant_id}/analytics/landing-pages/{page_id}/traffic
GET /api/v1/tenants/{tenant_id}/analytics/landing-pages/{page_id}/conversions
GET /api/v1/tenants/{tenant_id}/analytics/landing-pages/{page_id}/performance
```

### Brand Analytics
```
GET /api/v1/tenants/{tenant_id}/analytics/brand/consistency
GET /api/v1/tenants/{tenant_id}/analytics/brand/usage
GET /api/v1/tenants/{tenant_id}/analytics/brand/trends
```

## Controller Structure

### 1. TemplateController
**Location**: `app/Http/Controllers/Api/TemplateController.php`

#### Responsibilities:
- Template CRUD operations
- Template search and filtering
- Template preview generation
- Template duplication and versioning

#### Methods:
```php
class TemplateController extends Controller
{
    public function index(Request $request, string $tenantId)
    public function store(Request $request, string $tenantId)
    public function show(string $tenantId, string $templateId)
    public function update(Request $request, string $tenantId, string $templateId)
    public function destroy(string $tenantId, string $templateId)
    public function duplicate(Request $request, string $tenantId, string $templateId)
    public function createVersion(Request $request, string $tenantId, string $templateId)
    public function preview(string $tenantId, string $templateId)
    public function getCategories(string $tenantId)
    public function getAudienceTypes(string $tenantId)
    public function getCampaignTypes(string $tenantId)
    public function getAnalytics(string $tenantId, string $templateId)
    public function getPerformanceMetrics(string $tenantId, string $templateId)
    public function getRecommendations(string $tenantId)
}
```

### 2. LandingPageController
**Location**: `app/Http/Controllers/Api/LandingPageController.php`

#### Responsibilities:
- Landing page CRUD operations
- Page publishing workflow
- Page analytics and tracking
- Page preview generation

#### Methods:
```php
class LandingPageController extends Controller
{
    public function index(Request $request, string $tenantId)
    public function store(Request $request, string $tenantId)
    public function show(string $tenantId, string $pageId)
    public function update(Request $request, string $tenantId, string $pageId)
    public function destroy(string $tenantId, string $pageId)
    public function publish(string $tenantId, string $pageId)
    public function unpublish(string $tenantId, string $pageId)
    public function archive(string $tenantId, string $pageId)
    public function suspend(string $tenantId, string $pageId)
    public function preview(string $tenantId, string $pageId)
    public function getAnalytics(string $tenantId, string $pageId)
    public function getStatusCounts(string $tenantId)
}
```

### 3. BrandCustomizerController
**Location**: `app/Http/Controllers/Api/BrandCustomizerController.php`

#### Responsibilities:
- Brand asset management (logos, colors, fonts)
- Brand template management
- Brand guidelines enforcement
- Brand consistency checking

#### Methods:
```php
class BrandCustomizerController extends Controller
{
    // Logo management
    public function getLogos(string $tenantId)
    public function storeLogo(Request $request, string $tenantId)
    public function showLogo(string $tenantId, string $logoId)
    public function updateLogo(Request $request, string $tenantId, string $logoId)
    public function deleteLogo(string $tenantId, string $logoId)
    public function optimizeLogo(string $tenantId, string $logoId)
    public function setPrimaryLogo(string $tenantId, string $logoId)
    
    // Color management
    public function getColors(string $tenantId)
    public function storeColor(Request $request, string $tenantId)
    public function showColor(string $tenantId, string $colorId)
    public function updateColor(Request $request, string $tenantId, string $colorId)
    public function deleteColor(string $tenantId, string $colorId)
    
    // Font management
    public function getFonts(string $tenantId)
    public function storeFont(Request $request, string $tenantId)
    public function showFont(string $tenantId, string $fontId)
    public function updateFont(Request $request, string $tenantId, string $fontId)
    public function deleteFont(string $tenantId, string $fontId)
    public function setPrimaryFont(string $tenantId, string $fontId)
    
    // Template management
    public function getTemplates(string $tenantId)
    public function storeTemplate(Request $request, string $tenantId)
    public function showTemplate(string $tenantId, string $templateId)
    public function updateTemplate(Request $request, string $tenantId, string $templateId)
    public function deleteTemplate(string $tenantId, string $templateId)
    public function duplicateTemplate(string $tenantId, string $templateId)
    public function applyTemplate(string $tenantId, string $templateId)
    
    // Guidelines management
    public function getGuidelines(string $tenantId)
    public function updateGuidelines(Request $request, string $tenantId)
    
    // Consistency checking
    public function runConsistencyCheck(Request $request, string $tenantId)
    public function autoFixIssue(string $tenantId, string $issueId)
}
```

### 4. ABTestController
**Location**: `app/Http/Controllers/Api/ABTestController.php`

#### Responsibilities:
- A/B test management
- Test assignment and conversion tracking
- Test result analysis

#### Methods:
```php
class ABTestController extends Controller
{
    public function index(string $tenantId)
    public function store(Request $request, string $tenantId)
    public function show(string $tenantId, string $testId)
    public function update(Request $request, string $tenantId, string $testId)
    public function destroy(string $tenantId, string $testId)
    public function start(string $tenantId, string $testId)
    public function stop(string $tenantId, string $testId)
    public function getResults(string $tenantId, string $testId)
    public function assign(string $testId)
    public function convert(string $testId)
}
```

### 5. AnalyticsController
**Location**: `app/Http/Controllers/Api/AnalyticsController.php`

#### Responsibilities:
- Template analytics and reporting
- Landing page analytics and tracking
- Brand analytics and trends

#### Methods:
```php
class AnalyticsController extends Controller
{
    // Template analytics
    public function getTemplateUsage(string $tenantId)
    public function getTemplateConversions(string $tenantId)
    public function getTemplatePerformance(string $tenantId)
    public function getTemplateTrends(string $tenantId)
    
    // Landing page analytics
    public function getLandingPageTraffic(string $tenantId, string $pageId)
    public function getLandingPageConversions(string $tenantId, string $pageId)
    public function getLandingPagePerformance(string $tenantId, string $pageId)
    
    // Brand analytics
    public function getBrandConsistency(string $tenantId)
    public function getBrandUsage(string $tenantId)
    public function getBrandTrends(string $tenantId)
}
```

## Request Validation

### Template Requests
```php
class TemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by middleware
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:landing,homepage,form,email,social',
            'audience_type' => 'required|string|in:individual,institution,employer,general',
            'campaign_type' => 'required|string|in:onboarding,event_promotion,donation,networking,career_services,recruiting,leadership,marketing',
            'structure' => 'nullable|array',
            'default_config' => 'nullable|array',
            'tags' => 'nullable|array',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
        ];
    }
}
```

### Landing Page Requests
```php
class LandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'template_id' => 'required|exists:templates,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'config' => 'nullable|array',
            'brand_config' => 'nullable|array',
            'audience_type' => 'required|in:individual,institution,employer',
            'campaign_type' => 'required|in:onboarding,event_promotion,networking,career_services,recruiting,donation,leadership,marketing',
            'category' => 'required|in:individual,institution,employer',
            'status' => 'required|in:draft,reviewing,published,archived,suspended',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|array',
            'social_image' => 'nullable|string|url|max:255',
            'tracking_id' => 'nullable|string|max:255',
        ];
    }
}
```

### Brand Asset Requests
```php
class BrandAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            // Logo rules
            'logo.name' => 'required_with:logo|string|max:255',
            'logo.type' => 'required_with:logo|in:primary,secondary,favicon,social',
            'logo.file' => 'required_with:logo|file|mimes:png,jpg,jpeg,svg,webp|max:5120',
            
            // Color rules
            'color.name' => 'required_with:color|string|max:100',
            'color.value' => 'required_with:color|string|regex:/^#[0-9A-F]{6}$/i',
            'color.type' => 'required_with:color|in:primary,secondary,accent,neutral,warning,error,success,info',
            
            // Font rules
            'font.name' => 'required_with:font|string|max:100',
            'font.family' => 'required_with:font|string|max:100',
            'font.weights' => 'nullable|array',
            'font.styles' => 'nullable|array',
            'font.type' => 'required_with:font|in:system,google,custom',
        ];
    }
}
```

## API Response Structure

### Success Responses
```json
{
  "success": true,
  "data": {},
  "message": "Operation completed successfully",
  "meta": {
    "timestamp": "2023-01-01T00:00:00Z",
    "version": "1.0"
  }
}
```

### Paginated Responses
```json
{
  "success": true,
  "data": [],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "/api/v1/tenants/1/templates?page=1",
    "last": "/api/v1/tenants/1/templates?page=10",
    "prev": null,
    "next": "/api/v1/tenants/1/templates?page=2"
  }
}
```

### Error Responses
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": [
      {
        "field": "name",
        "message": "The name field is required."
      }
    ]
  },
  "meta": {
    "timestamp": "2023-01-01T00:00:00Z",
    "trace_id": "abc123"
  }
}
```

## Middleware and Security

### Authentication Middleware
- `auth:sanctum` for API token authentication
- `tenant.access` for tenant isolation
- `role:admin|editor` for role-based access

### Rate Limiting
```php
Route::middleware(['throttle:60,1'])->group(function () {
    // API routes with 60 requests per minute limit
});
```

### CORS Configuration
```php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://localhost:3000', 'https://*.yourdomain.com'],
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => false,
```

## API Documentation

### OpenAPI/Swagger Specification
All endpoints will be documented using OpenAPI 3.0 specification with:
- Request/response schemas
- Authentication requirements
- Error response codes
- Example requests/responses
- Rate limiting information

### Postman Collection
Complete Postman collection with:
- Pre-request scripts for authentication
- Example requests for all endpoints
- Environment variables for different deployments
- Test scripts for validation

## Testing Strategy

### Unit Tests
- Controller method testing
- Request validation testing
- Response formatting verification
- Error handling validation

### Integration Tests
- Full API endpoint testing
- Authentication flow validation
- Tenant isolation verification
- Database operation testing

### Performance Tests
- Load testing with concurrent users
- Response time benchmarking
- Memory usage monitoring
- Database query optimization

## Monitoring and Logging

### API Monitoring
- Request/response logging
- Performance metrics collection
- Error rate tracking
- Uptime monitoring

### Audit Logging
- User action tracking
- Data modification logging
- Security event recording
- Compliance reporting

## Versioning Strategy

### API Versioning
- URI versioning: `/api/v1/`
- Semantic versioning for breaking changes
- Deprecation notices for legacy endpoints
- Migration guides for version upgrades

### Backward Compatibility
- Maintaining deprecated endpoints for 6 months
- Clear deprecation warnings
- Migration path documentation
- Legacy endpoint sunset schedule

## Deployment Considerations

### Staging Environment
- Mirror production environment
- Automated deployment pipeline
- Smoke testing for API endpoints
- Performance benchmarking

### Production Environment
- Load balancer configuration
- SSL certificate management
- CDN integration for static assets
- Database connection pooling

### Disaster Recovery
- API gateway failover
- Backup endpoint availability
- Data replication strategies
- Recovery time objectives