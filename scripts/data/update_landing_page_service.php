<?php

/**
 * Script to update LandingPageService for schema-based tenancy
 * This script will make the necessary changes to remove tenant_id references
 */

require_once 'vendor/autoload.php';

echo "Starting LandingPageService update for schema-based tenancy...\n";

$filePath = 'app/Services/LandingPageService.php';

if (!file_exists($filePath)) {
    echo "Error: LandingPageService.php not found at {$filePath}\n";
    exit(1);
}

// Read the current file
$content = file_get_contents($filePath);

echo "Original file size: " . strlen($content) . " bytes\n";

// Add ABOUTME comments at the top
$aboutmeComments = "<?php\n\n// ABOUTME: This service handles landing page creation, management, and analytics for the multi-tenant application.\n// ABOUTME: Updated for schema-based tenancy architecture where tenant isolation is handled at the database schema level.\n\n";

// Replace the opening PHP tag and namespace
$content = preg_replace('/^<\?php\s*\n\nnamespace/', $aboutmeComments . 'namespace', $content);

// Add TenantContextService import
$importPattern = '/(use App\\Services\\LeadScoringService;)/m';
$replacement = '$1' . "\nuse App\\Services\\TenantContextService;";
$content = preg_replace($importPattern, $replacement, $content);

// Update constructor to inject TenantContextService
$constructorPattern = '/(private LeadScoringService \$leadScoringService;)/m';
$constructorReplacement = '$1' . "\n    private TenantContextService \$tenantContextService;";
$content = preg_replace($constructorPattern, $constructorReplacement, $content);

$constructorParamsPattern = '/(LeadScoringService \$leadScoringService)/m';
$constructorParamsReplacement = '$1,' . "\n        TenantContextService \$tenantContextService";
$content = preg_replace($constructorParamsPattern, $constructorParamsReplacement, $content);

$constructorAssignPattern = '/(\$this->leadScoringService = \$leadScoringService;)/m';
$constructorAssignReplacement = '$1' . "\n        \$this->tenantContextService = \$tenantContextService;";
$content = preg_replace($constructorAssignPattern, $constructorAssignReplacement, $content);

// Remove tenant_id assignments in createFromTemplate method
$tenantIdPattern1 = "/\s*'tenant_id'\s*=>\s*\$customizations\['tenant_id'\]\s*\?\?\s*\$template->tenant_id,?\s*\n/m";
$content = preg_replace($tenantIdPattern1, '', $content);

$tenantIdPattern2 = "/\s*'tenant_id'\s*=>\s*\$original->tenant_id,?\s*\n/m";
$content = preg_replace($tenantIdPattern2, '', $content);

// Remove tenant validation in applyBrandToLandingPage method
$tenantValidationPattern = "/\s*\/\/ Ensure both belong to the same tenant.*?\n\s*if \(\$landingPage->tenant->id !== \$brandConfig->tenant_id\) \{.*?\}\s*\n/s";
$content = preg_replace($tenantValidationPattern, "\n        // Note: Tenant isolation is now handled at schema level, so no cross-tenant validation needed\n\n", $content);

echo "Updated file size: " . strlen($content) . " bytes\n";

// Write the updated content back
if (file_put_contents($filePath, $content)) {
    echo "Successfully updated {$filePath}\n";
    echo "Changes made:\n";
    echo "- Added ABOUTME comments\n";
    echo "- Imported TenantContextService\n";
    echo "- Updated constructor to inject TenantContextService\n";
    echo "- Removed tenant_id assignments\n";
    echo "- Updated tenant validation logic\n";
} else {
    echo "Error: Failed to write updated content to {$filePath}\n";
    exit(1);
}

echo "LandingPageService update completed successfully!\n";