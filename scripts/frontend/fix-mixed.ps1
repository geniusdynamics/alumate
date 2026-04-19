
# Fix mixed script tags

Get-ChildItem -Path "resources\js\Components" -Recurse -Include "*.vue" -ErrorAction SilentlyContinue | ForEach-Object {
    $f = $_.FullName
    $c = Get-Content $f -Raw -ErrorAction SilentlyContinue
    if ($c -match '<script setup lang="ts">' -and $c -match '<script>' -and $c -notmatch '<script lang="ts">') {
        $c = $c -replace '<script>', '<script lang="ts">'
        $c | Set-Content -Path $f -Encoding UTF8
        Write-Host "Fixed: $f" -ForegroundColor Green
    }
}
Write-Host "Done!" -ForegroundColor Cyan
