# Graduate Tracking System - Development Server Starter
# PowerShell script for proper server startup sequence

Write-Host "========================================" -ForegroundColor Green
Write-Host "  🎓 Graduate Tracking System - Ready!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Kill any existing processes first
Write-Host "[0/5] Cleaning up existing processes..." -ForegroundColor Yellow
try {
    Stop-Job -Name 'ViteServer' -ErrorAction SilentlyContinue
    Remove-Job -Name 'ViteServer' -ErrorAction SilentlyContinue
    Stop-Job -Name 'LaravelServer' -ErrorAction SilentlyContinue
    Remove-Job -Name 'LaravelServer' -ErrorAction SilentlyContinue
    taskkill /F /IM php.exe 2>$null | Out-Null
    taskkill /F /IM node.exe 2>$null | Out-Null
    Write-Host "✓ Existing processes cleaned up" -ForegroundColor Green
} catch {
    Write-Host "✓ No existing processes to clean up" -ForegroundColor Green
}

# Check if PHP is available
Write-Host "[1/5] Checking PHP installation..." -ForegroundColor Yellow
try {
    $phpVersion = & "D:\DevCenter\xampp\php-8.3.23\php.exe" --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ PHP is available" -ForegroundColor Green
    } else {
        throw "PHP not found"
    }
} catch {
    Write-Host "❌ PHP not found at D:\DevCenter\xampp\php-8.3.23\php.exe" -ForegroundColor Red
    exit 1
}

# Check if Node.js is available
Write-Host "[2/5] Checking Node.js installation..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Node.js is available: $nodeVersion" -ForegroundColor Green
    } else {
        throw "Node.js not found"
    }
} catch {
    Write-Host "❌ Node.js not found. Please install Node.js" -ForegroundColor Red
    exit 1
}

# Clear Laravel caches
Write-Host "[3/5] Clearing Laravel caches..." -ForegroundColor Yellow
try {
    & "D:\DevCenter\xampp\php-8.3.23\php.exe" artisan config:clear 2>$null
    & "D:\DevCenter\xampp\php-8.3.23\php.exe" artisan route:clear 2>$null
    & "D:\DevCenter\xampp\php-8.3.23\php.exe" artisan view:clear 2>$null
    & "D:\DevCenter\xampp\php-8.3.23\php.exe" artisan cache:clear 2>$null
    Write-Host "✓ Laravel caches cleared" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Warning: Could not clear some caches" -ForegroundColor Yellow
}

# Start Vite development server
Write-Host "[4/5] Starting Vite development server..." -ForegroundColor Yellow
$viteJob = Start-Job -ScriptBlock {
    Set-Location 'D:\DevCenter\abuilds\alumate'
    npm run dev
} -Name 'ViteServer'

Write-Host "✓ Vite server starting..." -ForegroundColor Green
Start-Sleep -Seconds 10

# Start Laravel server
Write-Host "[5/5] Starting Laravel server..." -ForegroundColor Yellow
$laravelJob = Start-Job -ScriptBlock {
    Set-Location 'D:\DevCenter\abuilds\alumate'
    & 'D:\DevCenter\xampp\php-8.3.23\php.exe' artisan serve --host=127.0.0.1 --port=8080
} -Name 'LaravelServer'

Write-Host "✓ Laravel server starting..." -ForegroundColor Green
Start-Sleep -Seconds 8

# Check server status
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "   🔍 CHECKING SERVER STATUS" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

$viteRunning = $false
$laravelRunning = $false

# Check Vite
try {
    $viteResponse = Invoke-WebRequest -Uri "http://localhost:5100" -TimeoutSec 5 -ErrorAction Stop
    $viteRunning = $true
    Write-Host "✅ Vite Dev Server: http://localhost:5100" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Vite Dev Server: Starting..." -ForegroundColor Yellow
}

# Check Laravel
$attempts = 0
while (-not $laravelRunning -and $attempts -lt 5) {
    try {
        $laravelResponse = Invoke-WebRequest -Uri "http://127.0.0.1:8080" -TimeoutSec 10 -ErrorAction Stop
        $laravelRunning = $true
        Write-Host "✅ Laravel Application: http://127.0.0.1:8080" -ForegroundColor Green
    } catch {
        $attempts++
        Write-Host "⏳ Attempt $attempts/5: Laravel starting..." -ForegroundColor Yellow
        Start-Sleep -Seconds 3
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "   🎉 SYSTEM READY!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

Write-Host "🔑 DEMO ACCOUNTS:" -ForegroundColor Cyan
Write-Host "  Super Admin:" -ForegroundColor Yellow
Write-Host "    📧 admin@system.com" -ForegroundColor White
Write-Host "    🔒 password" -ForegroundColor White
Write-Host ""
Write-Host "  Institution Admin:" -ForegroundColor Yellow
Write-Host "    📧 admin@tech-institute.edu" -ForegroundColor White
Write-Host "    🔒 password" -ForegroundColor White
Write-Host ""
Write-Host "  Graduate:" -ForegroundColor Yellow
Write-Host "    📧 john.smith@student.edu" -ForegroundColor White
Write-Host "    🔒 password" -ForegroundColor White
Write-Host ""

Write-Host "🌐 ACCESS LINKS:" -ForegroundColor Cyan
Write-Host "  • Main App: http://127.0.0.1:8080" -ForegroundColor White
Write-Host "  • Login: http://127.0.0.1:8080/login" -ForegroundColor White
Write-Host "  • Register: http://127.0.0.1:8080/register" -ForegroundColor White
Write-Host ""

if ($laravelRunning) {
    Write-Host "🚀 Opening application..." -ForegroundColor Yellow
    Start-Process "http://127.0.0.1:8080"
}

Write-Host "Press Ctrl+C to stop servers..." -ForegroundColor Yellow
Write-Host ""

# Monitor servers
try {
    while ($true) {
        $viteStatus = Get-Job -Name 'ViteServer' -ErrorAction SilentlyContinue
        $laravelStatus = Get-Job -Name 'LaravelServer' -ErrorAction SilentlyContinue
        
        if ($viteStatus.State -eq "Failed" -or $laravelStatus.State -eq "Failed") {
            Write-Host "⚠️  Server stopped. Exiting..." -ForegroundColor Red
            break
        }
        
        Start-Sleep -Seconds 10
    }
} catch {
    Write-Host "🛑 Stopping servers..." -ForegroundColor Yellow
} finally {
    Stop-Job -Name 'ViteServer' -ErrorAction SilentlyContinue
    Remove-Job -Name 'ViteServer' -ErrorAction SilentlyContinue
    Stop-Job -Name 'LaravelServer' -ErrorAction SilentlyContinue
    Remove-Job -Name 'LaravelServer' -ErrorAction SilentlyContinue
    taskkill /F /IM php.exe 2>$null | Out-Null
    taskkill /F /IM node.exe 2>$null | Out-Null
    Write-Host "✅ All servers stopped!" -ForegroundColor Green
}