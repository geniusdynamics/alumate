# Frontend Fix Script - PowerShell Version

Write-Host "=== Frontend Issue Fixer ===" -ForegroundColor Green

# Get all Vue files in Pages directory
$vueFiles = Get-ChildItem -Path "resources\js\Pages" -Recurse -Include "*.vue"

Write-Host "Found $($vueFiles.Count) Vue files to check..." -ForegroundColor Yellow

$fixedCount = 0
$issueCount = 0

foreach ($file in $vueFiles) {
    try {
        $content = Get-Content $file.FullName -Raw
        
        # Check for missing lang="ts" attribute
        if ($content -match '<script setup>(?!\s+lang="ts")') {
            $content = $content -replace '<script setup>', '<script setup lang="ts">'
            Set-Content -Path $file.FullName -Value $content -Encoding UTF8
            Write-Host "✓ Fixed lang attribute in: $($file.Name)" -ForegroundColor Green
            $fixedCount++
        }
        
        # Count issues (files with <script setup> but no lang="ts")
        if ($content -match '<script setup>(?!\s+lang="ts")') {
            $issueCount++
        }
        
    } catch {
        Write-Host "✗ Error processing $($file.Name): $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`n=== Summary ===" -ForegroundColor Cyan
Write-Host "Files checked: $($vueFiles.Count)" -ForegroundColor White
Write-Host "Files fixed: $fixedCount" -ForegroundColor White
Write-Host "Remaining issues: $issueCount" -ForegroundColor White

if ($issueCount -eq 0) {
    Write-Host "🎉 All lang attributes fixed!" -ForegroundColor Green
} else {
    Write-Host "⚠️  $issueCount files still need attention" -ForegroundColor Yellow
}

Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Run 'pnpm run lint' to see remaining issues" -ForegroundColor White
Write-Host "2. Run 'pnpm run typecheck' to verify TypeScript" -ForegroundColor White
Write-Host "3. Address unused variable warnings" -ForegroundColor White