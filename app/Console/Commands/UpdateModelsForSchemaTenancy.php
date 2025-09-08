<?php
// ABOUTME: Artisan command to update Eloquent models for schema-based tenancy
// ABOUTME: Removes tenant_id columns and updates global scopes to use TenantContextService

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdateModelsForSchemaTenancy extends Command
{
    protected $signature = 'tenancy:update-models
                          {--dry-run : Show what would be changed without making changes}
                          {--model= : Update specific model only}';

    protected $description = 'Update Eloquent models to use schema-based tenancy instead of tenant_id columns';

    protected array $modelsToUpdate = [
        'LandingPageAnalytics',
        'EmailPreference', 
        'TenantCourseOffering',
        'TemplatePerformanceReport',
        'SecurityLog',
        'ComponentTheme',
        'BrandTemplate',
        'TemplateCrmSyncLog',
        'NotificationPreference',
        'AnalyticsEvent',
        'EmailAutomationRule',
        'PublishedSite',
        'NotificationTemplate',
        'CrmSyncLog',
        'BrandFont',
        'SuperAdminAnalytics',
        'Template',
        'BrandConfig',
        'TemplateCrmIntegration',
        'NotificationLog',
        'EmailSequence',
        'TemplatePerformanceDashboard',
        'DataSyncLog',
        'Graduate',
        'BrandGuidelines',
        'BrandLogo',
        'EmailCampaign',
        'TemplateVariant',
        'LandingPage',
        'EmailAnalytics',
        'BrandColor',
        'TemplateAnalyticsEvent',
        'BehaviorEvent',
        'AuditTrail'
    ];

    public function handle(): int
    {
        $this->info('Starting model updates for schema-based tenancy...');
        
        $modelsToProcess = $this->option('model') 
            ? [$this->option('model')]
            : $this->modelsToUpdate;

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($modelsToProcess as $modelName) {
            $modelPath = app_path("Models/{$modelName}.php");
            
            if (!File::exists($modelPath)) {
                $this->warn("Model {$modelName} not found at {$modelPath}");
                $skippedCount++;
                continue;
            }

            if ($this->updateModel($modelPath, $modelName)) {
                $updatedCount++;
                $this->info("✓ Updated {$modelName}");
            } else {
                $skippedCount++;
                $this->warn("⚠ Skipped {$modelName} (already updated or no changes needed)");
            }
        }

        $this->info("\nCompleted: {$updatedCount} updated, {$skippedCount} skipped");
        
        if ($this->option('dry-run')) {
            $this->warn('This was a dry run. No files were actually modified.');
        }

        return Command::SUCCESS;
    }

    protected function updateModel(string $filePath, string $modelName): bool
    {
        $content = File::get($filePath);
        $originalContent = $content;

        // Skip if already updated (contains TenantContextService)
        if (Str::contains($content, 'TenantContextService')) {
            return false;
        }

        // Skip if doesn't contain tenant_id
        if (!Str::contains($content, 'tenant_id')) {
            return false;
        }

        // Add ABOUTME comments
        $content = $this->addAboutMeComments($content, $modelName);
        
        // Add TenantContextService import
        $content = $this->addTenantContextImport($content);
        
        // Remove tenant_id from fillable
        $content = $this->removeTenantIdFromFillable($content);
        
        // Update boot method
        $content = $this->updateBootMethod($content);
        
        // Update tenant relationship
        $content = $this->updateTenantRelationship($content);
        
        // Update forTenant scope
        $content = $this->updateForTenantScope($content);
        
        // Remove tenant_id from validation rules
        $content = $this->removeTenantIdFromValidation($content);

        if ($this->option('dry-run')) {
            if ($content !== $originalContent) {
                $this->line("\nChanges for {$modelName}:");
                $this->line(str_repeat('-', 50));
                // Show a summary of changes
                $this->line('- Added TenantContextService import');
                $this->line('- Removed tenant_id from fillable');
                $this->line('- Updated boot method for schema-based tenancy');
                $this->line('- Updated tenant relationship method');
            }
            return true;
        }

        if ($content !== $originalContent) {
            File::put($filePath, $content);
            return true;
        }

        return false;
    }

    protected function addAboutMeComments(string $content, string $modelName): string
    {
        // Add ABOUTME comments after opening PHP tag
        if (!Str::contains($content, '// ABOUTME:')) {
            $content = str_replace(
                "<?php\n\nnamespace",
                "<?php\n// ABOUTME: {$modelName} model for schema-based multi-tenancy without tenant_id column\n// ABOUTME: Manages {$this->getModelDescription($modelName)} with automatic tenant context resolution\n\nnamespace",
                $content
            );
        }
        
        return $content;
    }

    protected function addTenantContextImport(string $content): string
    {
        // Add TenantContextService import
        if (!Str::contains($content, 'use App\\Services\\TenantContextService;')) {
            $content = str_replace(
                "namespace App\\Models;\n\n",
                "namespace App\\Models;\n\nuse App\\Services\\TenantContextService;\n",
                $content
            );
        }
        
        return $content;
    }

    protected function removeTenantIdFromFillable(string $content): string
    {
        // Remove 'tenant_id' from fillable array
        $patterns = [
            "/\s*'tenant_id',\n/",
            "/\s*'tenant_id'\n/",
            "/'tenant_id',\s*/",
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        return $content;
    }

    protected function updateBootMethod(string $content): string
    {
        // Replace existing boot method or add new one
        $bootMethodPattern = '/protected static function boot\(\): void\s*\{[^}]*\}/s';
        
        $newBootMethod = "protected static function boot(): void\n    {\n        parent::boot();\n\n        // Apply tenant context for schema-based tenancy\n        static::addGlobalScope('tenant_context', function (\$builder) {\n            app(TenantContextService::class)->applyTenantContext(\$builder);\n        });\n    }";
        
        if (preg_match($bootMethodPattern, $content)) {
            $content = preg_replace($bootMethodPattern, $newBootMethod, $content);
        } else {
            // Add boot method after class declaration
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+\w+\s*\{[^\n]*\n)/s',
                "$1\n    /**\n     * Boot the model\n     */\n    {$newBootMethod}\n\n",
                $content
            );
        }
        
        return $content;
    }

    protected function updateTenantRelationship(string $content): string
    {
        // Replace tenant() relationship method
        $tenantMethodPattern = '/public function tenant\(\): BelongsTo\s*\{[^}]*\}/s';
        
        $newTenantMethod = "/**\n     * Get the current tenant context\n     * Note: In schema-based tenancy, tenant relationship is contextual\n     */\n    public function getCurrentTenant()\n    {\n        return app(TenantContextService::class)->getCurrentTenant();\n    }";
        
        if (preg_match($tenantMethodPattern, $content)) {
            $content = preg_replace($tenantMethodPattern, $newTenantMethod, $content);
        }
        
        return $content;
    }

    protected function updateForTenantScope(string $content): string
    {
        // Update forTenant scope method
        $forTenantPattern = '/public function scopeForTenant\([^}]*\}/s';
        
        $newForTenantMethod = "/**\n     * Scope query to specific tenant (for schema-based tenancy)\n     * Note: This is primarily for administrative purposes\n     */\n    public function scopeForTenant(\$query, string \$tenantId)\n    {\n        // In schema-based tenancy, this would switch schema context\n        return app(TenantContextService::class)->scopeToTenant(\$query, \$tenantId);\n    }";
        
        if (preg_match($forTenantPattern, $content)) {
            $content = preg_replace($forTenantPattern, $newForTenantMethod, $content);
        }
        
        return $content;
    }

    protected function removeTenantIdFromValidation(string $content): string
    {
        // Remove tenant_id validation rules
        $patterns = [
            "/'tenant_id'\s*=>\s*'[^']*',?\s*/",
            "/\s*'tenant_id'\s*=>\s*'[^']*'\n/",
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        return $content;
    }

    protected function getModelDescription(string $modelName): string
    {
        $descriptions = [
            'LandingPageAnalytics' => 'landing page analytics data',
            'EmailPreference' => 'email preferences',
            'TenantCourseOffering' => 'course offerings',
            'TemplatePerformanceReport' => 'template performance reports',
            'SecurityLog' => 'security audit logs',
            'ComponentTheme' => 'component themes',
            'BrandTemplate' => 'brand templates',
            'TemplateCrmSyncLog' => 'template CRM sync logs',
            'NotificationPreference' => 'notification preferences',
            'AnalyticsEvent' => 'analytics events',
            'EmailAutomationRule' => 'email automation rules',
            'PublishedSite' => 'published sites',
            'NotificationTemplate' => 'notification templates',
            'CrmSyncLog' => 'CRM sync logs',
            'BrandFont' => 'brand fonts',
            'SuperAdminAnalytics' => 'super admin analytics',
            'Template' => 'templates',
            'BrandConfig' => 'brand configurations',
            'TemplateCrmIntegration' => 'template CRM integrations',
            'NotificationLog' => 'notification logs',
            'EmailSequence' => 'email sequences',
            'TemplatePerformanceDashboard' => 'template performance dashboards',
            'DataSyncLog' => 'data sync logs',
            'Graduate' => 'graduate records',
            'BrandGuidelines' => 'brand guidelines',
            'BrandLogo' => 'brand logos',
            'EmailCampaign' => 'email campaigns',
            'TemplateVariant' => 'template variants',
            'LandingPage' => 'landing pages',
            'EmailAnalytics' => 'email analytics',
            'BrandColor' => 'brand colors',
            'TemplateAnalyticsEvent' => 'template analytics events',
            'BehaviorEvent' => 'behavior events',
            'AuditTrail' => 'audit trail records'
        ];
        
        return $descriptions[$modelName] ?? strtolower($modelName) . ' records';
    }
}