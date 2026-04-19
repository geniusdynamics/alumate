# Fix ALL Vue files with missing lang="ts"

Get-ChildItem -Path "resources\js" -Recurse -Include "*.vue" -ErrorAction SilentlyContinue | ForEach-Object {
    $f = $_.FullName
    $c = Get-Content $f -Raw -ErrorAction SilentlyContinue
    if ($c -and $c -match '<script setup>' -and $c -notmatch 'lang="ts"') {
        $c = $c -replace '<script setup>', '<script setup lang="ts">'
        $c | Set-Content -Path $f -Encoding UTF8
        Write-Host "Fixed: $f" -ForegroundColor Green
    }
}
Write-Host "Done!" -ForegroundColor Cyan