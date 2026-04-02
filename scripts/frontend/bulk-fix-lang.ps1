# Bulk fix for Vue script setup lang attributes

$files = @(
    "resources\js\Pages\SuperAdmin\Performance.vue",
    "resources\js\Pages\SuperAdmin\Content.vue",
    "resources\js\Pages\SuperAdmin\Activity.vue",
    "resources\js\Pages\Search\Components\CourseListItem.vue",
    "resources\js\Pages\Search\Components\CourseCard.vue",
    "resources\js\Pages\SuperAdmin\Reports.vue",
    "resources\js\Pages\Search\Components\ViewToggle.vue",
    "resources\js\Pages\Search\Components\SortDropdown.vue",
    "resources\js\Pages\Admin\Integrations\Show.vue",
    "resources\js\Pages\SuperAdmin\Settings.vue",
    "resources\js\Pages\RecruitingIntelligence.vue",
    "resources\js\Pages\CommunityEngagementHub.vue",
    "resources\js\Pages\settings\Appearance.vue",
    "resources\js\Pages\Search\Components\SaveSearchModal.vue",
    "resources\js\Pages\Search\Components\JobListItem.vue",
    "resources\js\Pages\Search\Components\GraduateCard.vue",
    "resources\js\Pages\Admin\EmailMarketing.vue",
    "resources\js\Pages\Help.vue",
    "resources\js\Pages\EmployerRelationsManagement.vue",
    "resources\js\Pages\CareerServicesHub.vue",
    "resources\js\Pages\Welcome.vue",
    "resources\js\Pages\Offline.vue",
    "resources\js\Pages\Search\Components\GraduateListItem.vue",
    "resources\js\Pages\Admin\Integrations\Index.vue",
    "resources\js\Pages\SuperAdmin\Analytics.vue"
)

$fixed = 0
foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw
        if ($content -match '<script setup>' -and $content -notmatch '<script setup lang="ts">') {
            $content = $content -replace '<script setup>', '<script setup lang="ts">'
            Set-Content -Path $file -Value $content -Encoding UTF8
            Write-Host "Fixed: $file" -ForegroundColor Green
            $fixed++
        }
    }
}
Write-Host "`nTotal files fixed: $fixed" -ForegroundColor Cyan