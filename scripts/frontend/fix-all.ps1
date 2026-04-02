# Comprehensive Vue Lang Fix

$files = Get-ChildItem -Path "resources\js\Pages" -Recurse -Include "*.vue" -ErrorAction SilentlyContinue

$count = 0
foreach ($f in $files) {
    $c = Get-Content $f.FullName -Raw -ErrorAction SilentlyContinue
    if ($c -match '<script setup>(?!.*lang="ts")') {
        $c = $c -replace '<script setup>', '<script setup lang="ts">'
        $c | Set-Content -Path $f.FullName -Encoding UTF8
        $count++
        Write-Host "Fixed: $($f.Name)" -ForegroundColor Green
    }
}
Write-Host "`nTotal fixed: $count" -ForegroundColor Cyan