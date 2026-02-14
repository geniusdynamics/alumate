# Fix remaining Vue files

$targetFiles = @(
    "resources\js\Pages\SuperAdmin\Users.vue",
    "resources\js\Pages\SuperAdmin\SystemHealth.vue",
    "resources\js\Pages\InstitutionAdmin\StaffManagement.vue",
    "resources\js\Pages\InstitutionAdmin\Reports.vue",
    "resources\js\Pages\InstitutionAdmin\ImportExportCenter.vue",
    "resources\js\Pages\InstitutionAdmin\Analytics.vue",
    "resources\js\Pages\Jobs\Dashboard.vue",
    "resources\js\Pages\Events\Discovery.vue",
    "resources\js\Pages\Career\MentorshipHub.vue",
    "resources\js\Pages\InstitutionAdmin\Dashboard.vue",
    "resources\js\Pages\Training\VideoTutorial.vue",
    "resources\js\Pages\Training\Index.vue",
    "resources\js\Pages\Training\FAQs.vue",
    "resources\js\Pages\Social\Timeline.vue",
    "resources\js\Pages\Messages\Index.vue",
    "resources\js\Pages\Forums\Index.vue",
    "resources\js\Pages\Events\Index.vue",
    "resources\js\Pages\Developer\ApiDocumentation.vue",
    "resources\js\Pages\VideoCall\Index.vue",
    "resources\js\Pages\Training\Guide.vue",
    "resources\js\Pages\Scholarships\Index.vue",
    "resources\js\Pages\Stories\Index.vue",
    "resources\js\Pages\Messages\Show.vue",
    "resources\js\Pages\Messages\Create.vue",
    "resources\js\Pages\Career\Timeline.vue"
)

$total = 0
foreach ($f in $targetFiles) {
    if (Test-Path $f) {
        $c = Get-Content $f -Raw
        if ($c -match '<script setup>' -and $c -notmatch 'lang="ts"') {
            $c = $c -replace '<script setup>', '<script setup lang="ts">'
            Set-Content -Path $f -Value $c -Encoding UTF8
            Write-Host "Fixed: $f" -ForegroundColor Green
            $total++
        }
    }
}
Write-Host "`nTotal: $total files fixed"