# Frontend Issue Fixer Script
# This script automatically fixes common frontend issues identified in the codebase

Write-Host "=== Frontend Issue Fixer ===" -ForegroundColor Green

# Fix 1: Add missing lang="ts" attributes to script setup blocks
Write-Host "1. Fixing missing lang='ts' attributes..." -ForegroundColor Yellow

$vueFiles = Get-ChildItem -Path "resources\js\Pages" -Recurse -Include "*.vue" | Where-Object {
    (Get-Content $_.FullName -First 10) -match "<script setup>"
}

foreach ($file in $vueFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match '<script setup>(?!\s+lang="ts")') {
        $content = $content -replace '<script setup>', '<script setup lang="ts">'
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        Write-Host "  Fixed: $($file.Name)" -ForegroundColor Green
    }
}

# Fix 2: Remove unused props variables
Write-Host "2. Identifying unused props..." -ForegroundColor Yellow

# This would require more sophisticated analysis - for now, we'll note the files
$filesWithUnusedProps = @(
    "resources\js\Pages\SuperAdmin\Dashboard.vue",
    "resources\js\Pages\SuperAdmin\Database.vue", 
    "resources\js\Pages\SuperAdmin\EmployerVerification.vue",
    "resources\js\Pages\SuperAdmin\Notifications.vue",
    "resources\js\Pages\SuperAdmin\Performance.vue",
    "resources\js\Pages\SuperAdmin\Settings.vue",
    "resources\js\Pages\SuperAdmin\SystemHealth.vue",
    "resources\js\Pages\Test\MobileComponents.vue"
)

foreach ($file in $filesWithUnusedProps) {
    if (Test-Path $file) {
        Write-Host "  Needs props cleanup: $file" -ForegroundColor Cyan
    }
}

Write-Host "=== Frontend Issue Fixer Complete ===" -ForegroundColor Green
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Run 'pnpm run lint' to see remaining issues" -ForegroundColor White
Write-Host "2. Run 'pnpm run typecheck' to check TypeScript errors" -ForegroundColor White
Write-Host "3. Run tests to verify functionality" -ForegroundColor White