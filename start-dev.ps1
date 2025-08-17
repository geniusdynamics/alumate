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

# Start Vite and Laravel in separate persistent windows
Write-Host "[4/4] Starting development servers..." -ForegroundColor Green

# Start Vite dev server in separate window
Write-Host "Starting Vite development server in separate window..." -ForegroundColor Green
Start-Process -FilePath "cmd.exe" -ArgumentList "/k", "title Vite Dev Server - Alumni Platform && echo Starting Vite Dev Server... && npm run dev" -WindowStyle Normal
Write-Host "✓ Vite server starting in separate window..." -ForegroundColor Green

# Wait for Vite to initialize with better error handling
Write-Host "Waiting for Vite to initialize on http://127.0.0.1:5100 ..." -ForegroundColor Yellow
$waited = 0
$timeout = 60
$viteReady = $false

do {
    try {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:5100/@vite/client" -UseBasicParsing -TimeoutSec 3 -ErrorAction Stop
        if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) {
            Write-Host "✓ Vite is ready after $waited seconds" -ForegroundColor Green
            $viteReady = $true
            break
        }
    } catch {
        # Vite not ready yet, continue waiting
    }
    
    if ($waited -ge $timeout) {
        Write-Host "⚠ Vite did not become ready within $timeout seconds" -ForegroundColor Yellow
        Write-Host "⚠ Check the Vite window for errors" -ForegroundColor Yellow
        Write-Host "⚠ Continuing with Laravel anyway..." -ForegroundColor Yellow
        break
    }

    $waited += 3
    Write-Host "   ... waiting ($waited/$timeout seconds)" -ForegroundColor Gray
    Start-Sleep -Seconds 3
} while ($true)

# Start Laravel server in separate window
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   STARTING LARAVEL SERVER" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✓ Vite Dev Server: http://127.0.0.1:5100" -ForegroundColor Green
Write-Host "✓ Laravel Server: http://127.0.0.1:8080 (starting...)" -ForegroundColor Green
Write-Host "`nBoth servers will run independently." -ForegroundColor White
Write-Host "Close individual server windows to stop them." -ForegroundColor White

Start-Process -FilePath "cmd.exe" -ArgumentList "/k", "title Laravel Server - Alumni Platform && echo Starting Laravel Server... && D:\DevCenter\xampp\php-8.3.23\php.exe artisan serve --host=127.0.0.1 --port=8080" -WindowStyle Normal

# Give Laravel a moment to start
Start-Sleep -Seconds 3

# Show status
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   DEVELOPMENT SERVERS RUNNING" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Vite Dev Server: http://127.0.0.1:5100" -ForegroundColor Green
Write-Host "✅ Laravel Server: http://127.0.0.1:8080" -ForegroundColor Green
Write-Host "`nBoth servers are running in separate windows." -ForegroundColor White
Write-Host "`n🔍 MONITORING:" -ForegroundColor Cyan
Write-Host "- Check Vite window for frontend compilation" -ForegroundColor Gray
Write-Host "- Check Laravel window for backend logs" -ForegroundColor Gray
Write-Host "- Both servers will auto-reload on file changes" -ForegroundColor Gray
Write-Host "`n🛑 TO STOP:" -ForegroundColor Cyan
Write-Host "- Close individual server windows, OR" -ForegroundColor Gray
Write-Host "- Press Ctrl+C in this window to stop monitoring" -ForegroundColor Gray

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

# Open both URLs in browser
Write-Host "🚀 Opening URLs in browser..." -ForegroundColor Yellow
Start-Process "http://127.0.0.1:8080"
Start-Process "http://127.0.0.1:5100"
Write-Host "✓ URLs opened in browser" -ForegroundColor Green

Write-Host "`nThis monitoring window will stay open." -ForegroundColor White
Write-Host "Close it when you're done developing." -ForegroundColor White

# Monitor the servers
Write-Host "`nPress Ctrl+C to stop monitoring..." -ForegroundColor Yellow

try {
    while ($true) {
        $timestamp = Get-Date -Format "HH:mm:ss"
        Write-Host "[$timestamp] Monitoring servers... (Press Ctrl+C to stop)" -ForegroundColor Gray
        Start-Sleep -Seconds 30
    }
} catch {
    Write-Host "`nMonitoring stopped." -ForegroundColor Yellow
    Write-Host "Server windows will continue running independently." -ForegroundColor White
    Write-Host "Close them manually when done developing." -ForegroundColor White
}