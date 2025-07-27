# Graduate Tracking System - Development Server Starter
# PowerShell script to start development servers in the correct order

Write-Host "========================================" -ForegroundColor Green
Write-Host "   Graduate Tracking System - Dev Setup" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Function to check if a port is in use
function Test-Port {
    param([int]$Port)
    try {
        $connection = New-Object System.Net.Sockets.TcpClient
        $connection.Connect("127.0.0.1", $Port)
        $connection.Close()
        return $true
    }
    catch {
        return $false
    }
}

# Function to wait for a service to be ready
function Wait-ForService {
    param([int]$Port, [string]$ServiceName, [int]$TimeoutSeconds = 60)
    
    Write-Host "Waiting for $ServiceName to be ready on port $Port..." -ForegroundColor Yellow
    $timeout = (Get-Date).AddSeconds($TimeoutSeconds)
    
    do {
        if (Test-Port -Port $Port) {
            Write-Host "✅ $ServiceName is ready!" -ForegroundColor Green
            return $true
        }
        Start-Sleep -Seconds 2
        Write-Host "." -NoNewline -ForegroundColor Yellow
    } while ((Get-Date) -lt $timeout)
    
    Write-Host ""
    Write-Host "❌ Timeout waiting for $ServiceName" -ForegroundColor Red
    return $false
}

# Check if PHP is available
Write-Host "[1/5] Checking PHP availability..." -ForegroundColor Cyan
$phpPath = "D:\DevCenter\xampp\php-8.3.23\php.exe"
if (-not (Test-Path $phpPath)) {
    Write-Host "❌ PHP not found at $phpPath" -ForegroundColor Red
    Write-Host "Please check your PHP installation path" -ForegroundColor Red
    exit 1
}
Write-Host "✅ PHP found at $phpPath" -ForegroundColor Green

# Check if Node.js is available
Write-Host "[2/5] Checking Node.js availability..." -ForegroundColor Cyan
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js version: $nodeVersion" -ForegroundColor Green
}
catch {
    Write-Host "❌ Node.js not found. Please install Node.js" -ForegroundColor Red
    exit 1
}

# Kill any existing processes on our ports
Write-Host "[3/5] Cleaning up existing processes..." -ForegroundColor Cyan
$processes = Get-NetTCPConnection -LocalPort 5173, 8080 -ErrorAction SilentlyContinue
if ($processes) {
    Write-Host "Stopping existing processes on ports 5173 and 8080..." -ForegroundColor Yellow
    Stop-Process -Name "node" -Force -ErrorAction SilentlyContinue
    Stop-Process -Name "php" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
}

# Start Vite development server
Write-Host "[4/5] Starting Vite development server..." -ForegroundColor Cyan
try {
    $viteProcess = Start-Process -FilePath "cmd" -ArgumentList "/c", "npm run dev" -PassThru -WindowStyle Normal
    Write-Host "Vite process started with PID: $($viteProcess.Id)" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to start Vite: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Wait for Vite to be ready
if (-not (Wait-ForService -Port 5173 -ServiceName "Vite Dev Server" -TimeoutSeconds 30)) {
    Write-Host "Failed to start Vite server. Exiting..." -ForegroundColor Red
    exit 1
}

# Start Laravel server
Write-Host "[5/5] Starting Laravel server..." -ForegroundColor Cyan
try {
    $laravelProcess = Start-Process -FilePath $phpPath -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=8080" -PassThru -WindowStyle Normal
    Write-Host "Laravel process started with PID: $($laravelProcess.Id)" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to start Laravel: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Wait for Laravel to be ready
if (-not (Wait-ForService -Port 8080 -ServiceName "Laravel Server" -TimeoutSeconds 30)) {
    Write-Host "Failed to start Laravel server. Checking for issues..." -ForegroundColor Red
    
    # Try to diagnose the issue
    Write-Host "Running diagnostics..." -ForegroundColor Yellow
    & $phpPath "scripts/debugging/diagnose_blank_screen.php"
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "   DEVELOPMENT SERVERS RUNNING" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 Laravel Application: " -NoNewline -ForegroundColor White
Write-Host "http://127.0.0.1:8080" -ForegroundColor Cyan
Write-Host "   - Super Admin Login: admin@system.com / password" -ForegroundColor Gray
Write-Host "   - Institution Admin: admin@tech-institute.edu / password" -ForegroundColor Gray
Write-Host "   - Institution Admin: admin@business-college.edu / password" -ForegroundColor Gray
Write-Host ""
Write-Host "⚡ Vite Dev Server: " -NoNewline -ForegroundColor White
Write-Host "http://localhost:5173" -ForegroundColor Cyan
Write-Host "   (for assets only - don't access directly)" -ForegroundColor Gray
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "   QUICK ACCESS LINKS" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "🔗 Super Admin Dashboard: " -NoNewline -ForegroundColor White
Write-Host "http://127.0.0.1:8080/super-admin/dashboard" -ForegroundColor Cyan
Write-Host "🔗 Login Page: " -NoNewline -ForegroundColor White
Write-Host "http://127.0.0.1:8080/login" -ForegroundColor Cyan
Write-Host "🔗 Register Page: " -NoNewline -ForegroundColor White
Write-Host "http://127.0.0.1:8080/register" -ForegroundColor Cyan
Write-Host "🔗 Testing Suite: " -NoNewline -ForegroundColor White
Write-Host "http://127.0.0.1:8080/testing" -ForegroundColor Cyan
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Open the application in browser
Write-Host "Opening Laravel application in browser..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
Start-Process "http://127.0.0.1:8080"

Write-Host ""
Write-Host "Press Ctrl+C to stop all servers..." -ForegroundColor Yellow
Write-Host "Or close this window to terminate both processes." -ForegroundColor Yellow

# Wait for user to stop the servers
try {
    # Keep the script running and monitor the processes
    while ($true) {
        # Check if processes are still running
        if ($viteProcess.HasExited) {
            Write-Host "⚠️  Vite process has stopped unexpectedly" -ForegroundColor Yellow
        }
        if ($laravelProcess.HasExited) {
            Write-Host "⚠️  Laravel process has stopped unexpectedly" -ForegroundColor Yellow
        }
        
        Start-Sleep -Seconds 5
    }
}
catch {
    Write-Host ""
    Write-Host "Stopping servers..." -ForegroundColor Yellow
}
finally {
    # Cleanup processes
    Write-Host "Cleaning up processes..." -ForegroundColor Yellow
    
    if (-not $viteProcess.HasExited) {
        Stop-Process -Id $viteProcess.Id -Force -ErrorAction SilentlyContinue
    }
    if (-not $laravelProcess.HasExited) {
        Stop-Process -Id $laravelProcess.Id -Force -ErrorAction SilentlyContinue
    }
    
    # Kill any remaining node/php processes
    Stop-Process -Name "node" -Force -ErrorAction SilentlyContinue
    Stop-Process -Name "php" -Force -ErrorAction SilentlyContinue
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "   All servers stopped. Goodbye!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
}