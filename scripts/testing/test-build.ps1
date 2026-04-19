Write-Host "Testing Vite build..." -ForegroundColor Yellow

try {
    Write-Host "Running npm run build..." -ForegroundColor Cyan
    npm run build
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Build successful!" -ForegroundColor Green
    } else {
        Write-Host "❌ Build failed with exit code: $LASTEXITCODE" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Build error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nChecking build output..." -ForegroundColor Yellow
if (Test-Path "public/build") {
    Write-Host "✅ Build directory exists" -ForegroundColor Green
    $files = Get-ChildItem "public/build" -Recurse | Measure-Object
    Write-Host "Files in build directory: $($files.Count)" -ForegroundColor Cyan
} else {
    Write-Host "❌ Build directory not found" -ForegroundColor Red
}