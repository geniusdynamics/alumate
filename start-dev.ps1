# Enhanced Development Server Startup Script for Laravel + Vite
# This script starts both Laravel and Vite development servers with monitoring

# Set error handling
$ErrorActionPreference = "Continue"

# Configuration Variables
$VitePort = 5100
$LaravelPort = 8080
$MaxRetries = 3
$HealthCheckInterval = 30
$LogFile = "dev-server.log"
$PhpPath = "D:\DevCenter\xampp\php-8.3.23\php.exe"

# Colors for output
$Green = "Green"
$Red = "Red"
$Yellow = "Yellow"
$Cyan = "Cyan"
$Blue = "Blue"
$Magenta = "Magenta"

# Initialize log file
"$(Get-Date): Enhanced development server startup initiated" | Out-File -FilePath $LogFile -Append

Write-Host "========================================" -ForegroundColor Green
Write-Host "  ENHANCED LARAVEL + VITE DEVELOPMENT SERVERS" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Function to cleanup existing processes
function Stop-ExistingServers {
    Write-Host "🧹 Cleaning up existing processes..." -ForegroundColor Yellow
    
    try {
        # Stop PHP processes (Laravel)
        $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
        if ($phpProcesses) {
            $phpProcesses | Stop-Process -Force -ErrorAction SilentlyContinue
            Write-Host "  ✓ Stopped PHP processes" -ForegroundColor Cyan
        }
        
        # Stop Node processes (Vite)
        $nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
        if ($nodeProcesses) {
            $nodeProcesses | Stop-Process -Force -ErrorAction SilentlyContinue
            Write-Host "  ✓ Stopped Node processes" -ForegroundColor Cyan
        }
        
        "$(Get-Date): Cleaned up existing processes" | Out-File -FilePath $LogFile -Append
        Start-Sleep -Seconds 2
        Write-Host "✅ Cleanup completed" -ForegroundColor Green
    }
    catch {
        Write-Host "⚠️ Warning during cleanup: $($_.Exception.Message)" -ForegroundColor Yellow
        "$(Get-Date): WARNING during cleanup: $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
    }
}

# Function to check port conflicts
function Test-PortConflicts {
    Write-Host "🔍 Checking for port conflicts..." -ForegroundColor Cyan
    
    $viteConflict = Get-NetTCPConnection -LocalPort $VitePort -ErrorAction SilentlyContinue
    if ($viteConflict) {
        Write-Host "  ⚠️ Warning: Port $VitePort is already in use" -ForegroundColor Yellow
        "$(Get-Date): WARNING: Port $VitePort conflict detected" | Out-File -FilePath $LogFile -Append
    }
    
    $laravelConflict = Get-NetTCPConnection -LocalPort $LaravelPort -ErrorAction SilentlyContinue
    if ($laravelConflict) {
        Write-Host "  ⚠️ Warning: Port $LaravelPort is already in use" -ForegroundColor Yellow
        "$(Get-Date): WARNING: Port $LaravelPort conflict detected" | Out-File -FilePath $LogFile -Append
    }
    
    Write-Host "✅ Port conflict check completed" -ForegroundColor Green
}

# Function to check if PHP is available
function Test-PhpInstallation {
    Write-Host "🔍 Checking PHP installation..." -ForegroundColor Cyan
    
    try {
        # Try custom PHP path first
        if (Test-Path $PhpPath) {
            $phpVersion = & $PhpPath -v 2>$null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "✅ PHP is available at: $PhpPath" -ForegroundColor Green
                "$(Get-Date): PHP found at $PhpPath" | Out-File -FilePath $LogFile -Append
                return $true
            }
        }
        
        # Fallback to system PATH
        $phpVersion = & php -v 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ PHP is available in PATH" -ForegroundColor Green
            "$(Get-Date): PHP found in system PATH" | Out-File -FilePath $LogFile -Append
            return $true
        }
    }
    catch {
        Write-Host "❌ PHP not found: $($_.Exception.Message)" -ForegroundColor Red
        "$(Get-Date): ERROR: PHP not found - $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
        Write-Host "Please ensure PHP is installed and added to your PATH" -ForegroundColor Yellow
        return $false
    }
    
    Write-Host "❌ PHP not found" -ForegroundColor Red
    "$(Get-Date): ERROR: PHP not found" | Out-File -FilePath $LogFile -Append
    return $false
}

# Execute startup sequence
Write-Host "[1/6] Cleaning up existing processes..." -ForegroundColor Yellow
Stop-ExistingServers
Write-Host ""

Write-Host "[2/6] Checking for port conflicts..." -ForegroundColor Yellow
Test-PortConflicts
Write-Host ""

Write-Host "[3/6] Checking PHP installation..." -ForegroundColor Yellow
if (-not (Test-PhpInstallation)) {
    Write-Host "❌ Cannot proceed without PHP" -ForegroundColor Red
    "$(Get-Date): FATAL: Cannot proceed without PHP" | Out-File -FilePath $LogFile -Append
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host ""

# Function to check if Node.js is available
function Test-NodeInstallation {
    Write-Host "🔍 Checking Node.js installation..." -ForegroundColor Cyan
    
    try {
        $nodeVersion = & node --version 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Node.js is available: $nodeVersion" -ForegroundColor Green
            "$(Get-Date): Node.js found - $nodeVersion" | Out-File -FilePath $LogFile -Append
            return $true
        }
    }
    catch {
        Write-Host "❌ Node.js not found: $($_.Exception.Message)" -ForegroundColor Red
        "$(Get-Date): ERROR: Node.js not found - $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
        Write-Host "Please ensure Node.js is installed and added to your PATH" -ForegroundColor Yellow
        return $false
    }
    
    Write-Host "❌ Node.js not found" -ForegroundColor Red
    "$(Get-Date): ERROR: Node.js not found" | Out-File -FilePath $LogFile -Append
    return $false
}

Write-Host "[4/6] Checking Node.js installation..." -ForegroundColor Yellow
if (-not (Test-NodeInstallation)) {
    Write-Host "❌ Cannot proceed without Node.js" -ForegroundColor Red
    "$(Get-Date): FATAL: Cannot proceed without Node.js" | Out-File -FilePath $LogFile -Append
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host ""

# Clear Laravel caches
Write-Host "[5/6] Clearing Laravel caches..." -ForegroundColor Yellow
try {
    $phpExe = if (Test-Path $PhpPath) { $PhpPath } else { "php" }
    
    Write-Host "  🧹 Clearing config cache..." -ForegroundColor Cyan
    & $phpExe artisan config:clear 2>$null
    
    Write-Host "  🧹 Clearing route cache..." -ForegroundColor Cyan
    & $phpExe artisan route:clear 2>$null
    
    Write-Host "  🧹 Clearing view cache..." -ForegroundColor Cyan
    & $phpExe artisan view:clear 2>$null
    
    Write-Host "  🧹 Clearing application cache..." -ForegroundColor Cyan
    & $phpExe artisan cache:clear 2>$null
    
    Write-Host "✅ Laravel caches cleared" -ForegroundColor Green
    "$(Get-Date): Laravel caches cleared successfully" | Out-File -FilePath $LogFile -Append
} catch {
    Write-Host "⚠️ Warning: Could not clear some caches: $($_.Exception.Message)" -ForegroundColor Yellow
    "$(Get-Date): WARNING: Cache clearing failed - $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
}
Write-Host ""

# Start development servers
Write-Host "[6/6] Starting development servers..." -ForegroundColor Yellow

# Start Vite server in a new window
Write-Host "🚀 Starting Vite server on port $VitePort..." -ForegroundColor Cyan
try {
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; Write-Host 'Vite Development Server' -ForegroundColor Green; npm run dev" -WindowStyle Normal
    "$(Get-Date): Vite server started on port $VitePort" | Out-File -FilePath $LogFile -Append
    Write-Host "✅ Vite server window opened" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to start Vite server: $($_.Exception.Message)" -ForegroundColor Red
    "$(Get-Date): ERROR: Failed to start Vite server - $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
}

# Wait for Vite to initialize
Write-Host "⏳ Waiting for Vite to initialize..." -ForegroundColor Cyan
Start-Sleep -Seconds 3

# Start Laravel server in a new window
Write-Host "🚀 Starting Laravel server on port $LaravelPort..." -ForegroundColor Cyan
try {
    $phpExe = if (Test-Path $PhpPath) { $PhpPath } else { "php" }
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; Write-Host 'Laravel Development Server' -ForegroundColor Green; & '$phpExe' artisan serve --host=127.0.0.1 --port=$LaravelPort" -WindowStyle Normal
    "$(Get-Date): Laravel server started on port $LaravelPort" | Out-File -FilePath $LogFile -Append
    Write-Host "✅ Laravel server window opened" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to start Laravel server: $($_.Exception.Message)" -ForegroundColor Red
    "$(Get-Date): ERROR: Failed to start Laravel server - $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
}

# Wait for Laravel to start
Write-Host "⏳ Waiting for Laravel to start..." -ForegroundColor Cyan
Start-Sleep -Seconds 5

# Function to test server health
function Test-ServerHealth {
    param(
        [int]$Port,
        [string]$ServerName,
        [switch]$Silent
    )
    
    try {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:$Port" -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
        if (-not $Silent) {
            Write-Host "✅ $ServerName server is healthy on port $Port" -ForegroundColor Green
        }
        "$(Get-Date): $ServerName server health check passed" | Out-File -FilePath $LogFile -Append
        return $true
    }
    catch {
        if (-not $Silent) {
            Write-Host "❌ $ServerName server health check failed on port $Port" -ForegroundColor Red
        }
        "$(Get-Date): $ServerName server health check failed - $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
        return $false
    }
}

# Function to show memory usage
function Show-MemoryUsage {
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    $nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
    
    $phpMemory = ($phpProcesses | Measure-Object WorkingSet -Sum).Sum / 1MB
    $nodeMemory = ($nodeProcesses | Measure-Object WorkingSet -Sum).Sum / 1MB
    
    return "PHP: $([math]::Round($phpMemory, 1))MB | Node: $([math]::Round($nodeMemory, 1))MB"
}

# Function to show detailed memory usage
function Show-DetailedMemoryUsage {
    Write-Host "`n📊 DETAILED MEMORY USAGE:" -ForegroundColor Cyan
    
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "  PHP Processes:" -ForegroundColor Yellow
        $phpProcesses | ForEach-Object {
            $memoryMB = [math]::Round($_.WorkingSet / 1MB, 1)
            Write-Host "    PID $($_.Id): $memoryMB MB" -ForegroundColor White
        }
    }
    
    $nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
    if ($nodeProcesses) {
        Write-Host "  Node.js Processes:" -ForegroundColor Yellow
        $nodeProcesses | ForEach-Object {
            $memoryMB = [math]::Round($_.WorkingSet / 1MB, 1)
            Write-Host "    PID $($_.Id): $memoryMB MB" -ForegroundColor White
        }
    }
    Write-Host ""
}

# Function to restart servers
function Restart-Servers {
    Write-Host "🔄 Restarting development servers..." -ForegroundColor Yellow
    "$(Get-Date): Restarting servers" | Out-File -FilePath $LogFile -Append
    
    Stop-ExistingServers
    Start-Sleep -Seconds 2
    
    # Restart Vite
    try {
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; Write-Host 'Vite Development Server (Restarted)' -ForegroundColor Green; npm run dev" -WindowStyle Normal
        Write-Host "✅ Vite server restarted" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to restart Vite server" -ForegroundColor Red
    }
    
    Start-Sleep -Seconds 2
    
    # Restart Laravel
    try {
        $phpExe = if (Test-Path $PhpPath) { $PhpPath } else { "php" }
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; Write-Host 'Laravel Development Server (Restarted)' -ForegroundColor Green; & '$phpExe' artisan serve --host=127.0.0.1 --port=$LaravelPort" -WindowStyle Normal
        Write-Host "✅ Laravel server restarted" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to restart Laravel server" -ForegroundColor Red
    }
}

# Function to clear Laravel caches
function Clear-LaravelCaches {
    try {
        $phpExe = if (Test-Path $PhpPath) { $PhpPath } else { "php" }
        
        Write-Host "  🧹 Clearing config cache..." -ForegroundColor Cyan
        & $phpExe artisan config:clear 2>$null
        
        Write-Host "  🧹 Clearing route cache..." -ForegroundColor Cyan
        & $phpExe artisan route:clear 2>$null
        
        Write-Host "  🧹 Clearing view cache..." -ForegroundColor Cyan
        & $phpExe artisan view:clear 2>$null
        
        Write-Host "  🧹 Clearing application cache..." -ForegroundColor Cyan
        & $phpExe artisan cache:clear 2>$null
        
        Write-Host "✅ Laravel caches cleared" -ForegroundColor Green
        "$(Get-Date): Laravel caches cleared" | Out-File -FilePath $LogFile -Append
    } catch {
        Write-Host "❌ Failed to clear caches: $($_.Exception.Message)" -ForegroundColor Red
        "$(Get-Date): Cache clearing failed - $($_.Exception.Message)" | Out-File -FilePath $LogFile -Append
    }
}

# Function to show log file
function Show-LogFile {
    Write-Host "`n📋 RECENT LOG ENTRIES:" -ForegroundColor Cyan
    if (Test-Path $LogFile) {
        Get-Content $LogFile -Tail 10 | ForEach-Object {
            Write-Host "  $_" -ForegroundColor Gray
        }
    } else {
        Write-Host "  No log file found" -ForegroundColor Yellow
    }
    Write-Host ""
}

# Start Laravel server in separate window
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   STARTING LARAVEL SERVER" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✓ Vite Dev Server: http://127.0.0.1:5100" -ForegroundColor Green
Write-Host "✓ Laravel Server: http://127.0.0.1:8080 (starting...)" -ForegroundColor Green
Write-Host "`nBoth servers will run independently." -ForegroundColor White
Write-Host "Close individual server windows to stop them." -ForegroundColor White

Start-Process -FilePath "cmd.exe" -ArgumentList "/k", "title Laravel Server - Alumni Platform "&" echo Starting Laravel Server... "&" D:\DevCenter\xampp\php-8.3.23\php.exe artisan serve --host=127.0.0.1 --port=8080" -WindowStyle Normal

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

# Initial server status check
Write-Host "🔍 Performing initial server health checks..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

# Check if servers are responding
$viteHealthy = Test-ServerHealth -Port $VitePort -ServerName "Vite"
$laravelHealthy = Test-ServerHealth -Port $LaravelPort -ServerName "Laravel"

Write-Host ""
Write-Host "🎉 Development environment setup complete!" -ForegroundColor Green
Write-Host "📱 Vite (Frontend): http://127.0.0.1:$VitePort" -ForegroundColor Cyan
Write-Host "🚀 Laravel (Backend): http://127.0.0.1:$LaravelPort" -ForegroundColor Cyan
Write-Host "📋 Log file: $LogFile" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 Both servers are running in separate windows" -ForegroundColor Yellow
Write-Host "💡 This script will now monitor both servers" -ForegroundColor Yellow
Write-Host ""

# Open the application in default browser
if ($laravelHealthy) {
    Write-Host "🌐 Opening application in browser..." -ForegroundColor Green
    Start-Process "http://127.0.0.1:$LaravelPort"
    "$(Get-Date): Application opened in browser" | Out-File -FilePath $LogFile -Append
}

# Start monitoring loop
Write-Host "🔄 Starting server monitoring..." -ForegroundColor Green
Write-Host "Press 'q' to quit, 'r' to restart servers, 'c' to clear caches, 'l' to view logs, 'm' to check memory" -ForegroundColor Yellow
Write-Host ""

$lastViteCheck = Get-Date
$lastLaravelCheck = Get-Date
$checkInterval = $HealthCheckInterval

while ($true) {
    $currentTime = Get-Date
    
    # Perform health checks at intervals
    if (($currentTime - $lastViteCheck).TotalSeconds -ge $checkInterval) {
        $viteHealthy = Test-ServerHealth -Port $VitePort -ServerName "Vite" -Silent
        $lastViteCheck = $currentTime
    }
    
    if (($currentTime - $lastLaravelCheck).TotalSeconds -ge $checkInterval) {
        $laravelHealthy = Test-ServerHealth -Port $LaravelPort -ServerName "Laravel" -Silent
        $lastLaravelCheck = $currentTime
    }
    
    # Display status
    $viteStatus = if ($viteHealthy) { "✅ Running" } else { "❌ Down" }
    $laravelStatus = if ($laravelHealthy) { "✅ Running" } else { "❌ Down" }
    
    Write-Host "`r[$(Get-Date -Format 'HH:mm:ss')] Vite: $viteStatus | Laravel: $laravelStatus | Memory: $(Show-MemoryUsage)" -NoNewline -ForegroundColor Green
    
    # Check for user input
    if ([Console]::KeyAvailable) {
        $key = [Console]::ReadKey($true)
        Write-Host "" # New line after status
        
        switch ($key.KeyChar.ToString().ToLower()) {
            'q' {
                Write-Host "🛑 Shutting down monitoring..." -ForegroundColor Yellow
                "$(Get-Date): Monitoring stopped by user" | Out-File -FilePath $LogFile -Append
                Write-Host "💡 Servers are still running in separate windows" -ForegroundColor Yellow
                Write-Host "💡 Close those windows to stop the servers" -ForegroundColor Yellow
                exit 0
            }
            'r' {
                Write-Host "🔄 Restarting servers..." -ForegroundColor Yellow
                Restart-Servers
                $lastViteCheck = Get-Date
                $lastLaravelCheck = Get-Date
            }
            'c' {
                Write-Host "🧹 Clearing caches..." -ForegroundColor Yellow
                Clear-LaravelCaches
            }
            'l' {
                Show-LogFile
            }
            'm' {
                Show-DetailedMemoryUsage
            }
            default {
                Write-Host "Available commands: (q)uit, (r)estart, (c)lear caches, (l)ogs, (m)emory" -ForegroundColor Cyan
            }
        }
        Write-Host "Press 'q' to quit, 'r' to restart servers, 'c' to clear caches, 'l' to view logs, 'm' to check memory" -ForegroundColor Yellow
    }
    
    # Auto-restart if servers are down
    if (-not $viteHealthy -or -not $laravelHealthy) {
        Write-Host "`n⚠️ Server(s) detected as down. Attempting auto-restart..." -ForegroundColor Yellow
        Restart-Servers
        $lastViteCheck = Get-Date
        $lastLaravelCheck = Get-Date
    }
    
    Start-Sleep -Milliseconds 1000
}