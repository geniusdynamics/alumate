# ABOUTME: Enhanced PowerShell script for starting Vite and Laravel development servers
# ABOUTME: Includes health monitoring, auto-restart, memory tracking, and interactive commands

# Configuration
$VitePort = 5100
$LaravelPort = 8080
$HealthCheckInterval = 30
$LogFile = "dev-server.log"

# Executable paths
$NodePath = "node"
$PhpPath = "php"
$NpmPath = "npm"

# Color scheme
$Colors = @{
    Success = "Green"
    Error = "Red"
    Warning = "Yellow"
    Info = "Cyan"
    Highlight = "Magenta"
    Muted = "Gray"
}

# Function to test if a port is in use
function Test-PortInUse {
    param([int]$Port)
    try {
        $connection = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue
        return $connection -ne $null
    }
    catch {
        return $false
    }
}

# Function to stop processes on a specific port
function Stop-ProcessOnPort {
    param([int]$Port)
    try {
        $connections = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue
        foreach ($conn in $connections) {
            $process = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
            if ($process) {
                Write-Host "Stopping process $($process.Name) (PID: $($process.Id)) on port $Port" -ForegroundColor $Colors.Warning
                Stop-Process -Id $process.Id -Force
            }
        }
    }
    catch {
        Write-Host "Error stopping processes on port $Port`: $($_.Exception.Message)" -ForegroundColor $Colors.Error
    }
}

# Function to test server health
function Test-ServerHealth {
    param(
        [int]$Port,
        [string]$ServerName,
        [switch]$Silent
    )
    try {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:$Port" -TimeoutSec 5 -ErrorAction SilentlyContinue
        if (-not $Silent) {
            Write-Host "✅ $ServerName server is healthy (Status: $($response.StatusCode))" -ForegroundColor $Colors.Success
        }
        return $true
    }
    catch {
        if (-not $Silent) {
            Write-Host "❌ $ServerName server is not responding" -ForegroundColor $Colors.Error
        }
        return $false
    }
}

# Function to get memory usage summary
function Show-MemoryUsage {
    try {
        $nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
        $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
        
        $totalMemory = 0
        if ($nodeProcesses) {
            $totalMemory += ($nodeProcesses | Measure-Object WorkingSet -Sum).Sum
        }
        if ($phpProcesses) {
            $totalMemory += ($phpProcesses | Measure-Object WorkingSet -Sum).Sum
        }
        
        $memoryMB = [math]::Round($totalMemory / 1MB, 1)
        return "$memoryMB MB"
    }
    catch {
        return "N/A"
    }
}

# Function to show detailed memory usage
function Show-DetailedMemoryUsage {
    Write-Host "`n💾 DETAILED MEMORY USAGE:" -ForegroundColor $Colors.Info
    
    $nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
    if ($nodeProcesses) {
        Write-Host "🟢 Node.js processes:" -ForegroundColor $Colors.Success
        $nodeProcesses | ForEach-Object {
            $memoryMB = [math]::Round($_.WorkingSet / 1MB, 1)
            Write-Host "  PID $($_.Id): ${memoryMB}MB" -ForegroundColor $Colors.Highlight
        }
    } else {
        Write-Host "🔴 No Node.js processes found" -ForegroundColor $Colors.Error
    }
    
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "🟢 PHP processes:" -ForegroundColor $Colors.Success
        $phpProcesses | ForEach-Object {
            $memoryMB = [math]::Round($_.WorkingSet / 1MB, 1)
            Write-Host "  PID $($_.Id): ${memoryMB}MB" -ForegroundColor $Colors.Highlight
        }
    } else {
        Write-Host "🔴 No PHP processes found" -ForegroundColor $Colors.Error
    }
    
    Write-Host ""
}

# Function to restart Vite server
function Restart-ViteServer {
    Write-Host "🔄 Restarting Vite server..." -ForegroundColor $Colors.Warning
    Stop-ProcessOnPort -Port $VitePort
    Start-Sleep -Seconds 2
    Start-Process -FilePath "cmd.exe" -ArgumentList @("/k", "title Vite Dev Server - Alumni Platform `& npm run dev") -WindowStyle Normal
    "$(Get-Date): Vite server restarted" | Out-File -FilePath $LogFile -Append
    Write-Host "✓ Vite server restart initiated" -ForegroundColor $Colors.Success
}

# Function to restart Laravel server
function Restart-LaravelServer {
    Write-Host "🔄 Restarting Laravel server..." -ForegroundColor $Colors.Warning
    Stop-ProcessOnPort -Port $LaravelPort
    Start-Sleep -Seconds 2
    Start-Process -FilePath "cmd.exe" -ArgumentList @("/k", "title Laravel Server - Alumni Platform `& php artisan serve --host=127.0.0.1 --port=$LaravelPort") -WindowStyle Normal
    "$(Get-Date): Laravel server restarted" | Out-File -FilePath $LogFile -Append
    Write-Host "✓ Laravel server restart initiated" -ForegroundColor $Colors.Success
}

# Function to restart both servers
function Restart-Servers {
    Write-Host "🔄 Restarting both servers..." -ForegroundColor $Colors.Warning
    Restart-ViteServer
    Start-Sleep -Seconds 1
    Restart-LaravelServer
    Write-Host "✓ Both servers restart initiated" -ForegroundColor $Colors.Success
}

# Function to clear Laravel caches
function Clear-LaravelCaches {
    Write-Host "🧹 Clearing Laravel caches..." -ForegroundColor $Colors.Info
    
    $commands = @(
        "config:clear",
        "cache:clear",
        "route:clear",
        "view:clear"
    )
    
    foreach ($cmd in $commands) {
        try {
            $result = & $PhpPath artisan $cmd 2>&1
            Write-Host "✓ $cmd completed" -ForegroundColor $Colors.Success
        }
        catch {
            Write-Host "⚠️ $cmd failed: $($_.Exception.Message)" -ForegroundColor $Colors.Warning
        }
    }
    
    "$(Get-Date): Laravel caches cleared" | Out-File -FilePath $LogFile -Append
    Write-Host "✓ Laravel caches cleared" -ForegroundColor $Colors.Success
}

# Function to show recent log entries
function Show-LogFile {
    Write-Host "`n📋 RECENT LOG ENTRIES:" -ForegroundColor $Colors.Info
    if (Test-Path $LogFile) {
        Get-Content $LogFile -Tail 10 | ForEach-Object {
            Write-Host "  $_" -ForegroundColor $Colors.Muted
        }
    } else {
        Write-Host "  No log file found" -ForegroundColor $Colors.Warning
    }
    Write-Host ""
}
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

# Main script execution starts here
Write-Host "`n========================================" -ForegroundColor $Colors.Info
Write-Host "   ALUMNI PLATFORM DEV ENVIRONMENT" -ForegroundColor $Colors.Info
Write-Host "========================================" -ForegroundColor $Colors.Info
Write-Host "🚀 Enhanced Development Server Manager" -ForegroundColor $Colors.Highlight
Write-Host "📅 $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor $Colors.Muted
Write-Host ""

# Check prerequisites
Write-Host "🔍 Checking prerequisites..." -ForegroundColor $Colors.Info

# Check if Node.js is available
try {
    $nodeVersion = & $NodePath --version 2>$null
    Write-Host "✅ Node.js: $nodeVersion" -ForegroundColor $Colors.Success
}
catch {
    Write-Host "❌ Node.js not found. Please install Node.js" -ForegroundColor $Colors.Error
    exit 1
}

# Check if PHP is available
try {
    $phpVersion = & $PhpPath --version 2>$null | Select-Object -First 1
    Write-Host "✅ PHP: $($phpVersion.Split(' ')[1])" -ForegroundColor $Colors.Success
}
catch {
    Write-Host "❌ PHP not found. Please install PHP" -ForegroundColor $Colors.Error
    exit 1
}

# Check if npm is available
try {
    $npmVersion = & $NpmPath --version 2>$null
    Write-Host "✅ NPM: $npmVersion" -ForegroundColor $Colors.Success
}
catch {
    Write-Host "❌ NPM not found. Please install NPM" -ForegroundColor $Colors.Error
    exit 1
}

# Check for port conflicts
Write-Host "`n🔍 Checking for port conflicts..." -ForegroundColor $Colors.Info

if (Test-PortInUse -Port $VitePort) {
    Write-Host "⚠️ Port $VitePort is in use. Attempting to free it..." -ForegroundColor $Colors.Warning
    Stop-ProcessOnPort -Port $VitePort
    Start-Sleep -Seconds 2
}

if (Test-PortInUse -Port $LaravelPort) {
    Write-Host "⚠️ Port $LaravelPort is in use. Attempting to free it..." -ForegroundColor $Colors.Warning
    Stop-ProcessOnPort -Port $LaravelPort
    Start-Sleep -Seconds 2
}

Write-Host "✅ Ports $VitePort and $LaravelPort are available" -ForegroundColor $Colors.Success

# Clear Laravel caches before starting
Write-Host "`n🧹 Clearing Laravel caches before startup..." -ForegroundColor $Colors.Info
Clear-LaravelCaches

# Start Vite server in separate window
Write-Host "`n========================================" -ForegroundColor $Colors.Info
Write-Host "   STARTING VITE DEV SERVER" -ForegroundColor $Colors.Info
Write-Host "========================================" -ForegroundColor $Colors.Info
Write-Host "🚀 Starting Vite development server..." -ForegroundColor $Colors.Highlight
Write-Host "📱 URL: http://127.0.0.1:$VitePort" -ForegroundColor $Colors.Success
Write-Host "📁 Watching: .\resources\js, .\resources\css" -ForegroundColor $Colors.Muted
Write-Host ""

Start-Process -FilePath "cmd.exe" -ArgumentList @("/k", "title Vite Dev Server - Alumni Platform "&" npm run dev") -WindowStyle Normal

# Give Vite a moment to start
Start-Sleep -Seconds 3

# Start Laravel server in separate window
Write-Host "`n========================================" -ForegroundColor $Colors.Info
Write-Host "   STARTING LARAVEL SERVER" -ForegroundColor $Colors.Info
Write-Host "========================================" -ForegroundColor $Colors.Info
Write-Host "✓ Vite Dev Server: http://127.0.0.1:$VitePort" -ForegroundColor $Colors.Success
Write-Host "✓ Laravel Server: http://127.0.0.1:$LaravelPort (starting...)" -ForegroundColor $Colors.Success
Write-Host "`nBoth servers will run independently." -ForegroundColor $Colors.Highlight
Write-Host "Close individual server windows to stop them." -ForegroundColor $Colors.Highlight

Start-Process -FilePath "cmd.exe" -ArgumentList @("/k", "title Laravel Server - Alumni Platform `& php artisan serve --host=127.0.0.1 --port=$LaravelPort") -WindowStyle Normal

# Give Laravel a moment to start
Start-Sleep -Seconds 3

# Show status
Write-Host "`n========================================" -ForegroundColor $Colors.Info
Write-Host "   DEVELOPMENT SERVERS RUNNING" -ForegroundColor $Colors.Info
Write-Host "========================================" -ForegroundColor $Colors.Info
Write-Host "✅ Vite Dev Server: http://127.0.0.1:$VitePort" -ForegroundColor $Colors.Success
Write-Host "✅ Laravel Server: http://127.0.0.1:$LaravelPort" -ForegroundColor $Colors.Success
Write-Host "`nBoth servers are running in separate windows." -ForegroundColor $Colors.Highlight
Write-Host "`n🔍 MONITORING:" -ForegroundColor $Colors.Info
Write-Host "- Check Vite window for frontend compilation" -ForegroundColor $Colors.Muted
Write-Host "- Check Laravel window for backend logs" -ForegroundColor $Colors.Muted
Write-Host "- Both servers will auto-reload on file changes" -ForegroundColor $Colors.Muted
Write-Host "`n🛑 TO STOP:" -ForegroundColor $Colors.Info
Write-Host "- Close individual server windows, OR" -ForegroundColor $Colors.Muted
Write-Host "- Press Ctrl+C in this window to stop monitoring" -ForegroundColor $Colors.Muted

Write-Host ""
Write-Host "🔑 DEMO ACCOUNTS:" -ForegroundColor $Colors.Info
Write-Host "  Super Admin:" -ForegroundColor $Colors.Warning
Write-Host "    📧 admin@system.com" -ForegroundColor $Colors.Highlight
Write-Host "    🔒 password" -ForegroundColor $Colors.Highlight
Write-Host ""
Write-Host "  Institution Admin:" -ForegroundColor $Colors.Warning
Write-Host "    📧 admin@tech-institute.edu" -ForegroundColor $Colors.Highlight
Write-Host "    🔒 password" -ForegroundColor $Colors.Highlight
Write-Host ""
Write-Host "  Graduate:" -ForegroundColor $Colors.Warning
Write-Host "    📧 john.smith@student.edu" -ForegroundColor $Colors.Highlight
Write-Host "    🔒 password" -ForegroundColor $Colors.Highlight
Write-Host ""

Write-Host "🌐 ACCESS LINKS:" -ForegroundColor $Colors.Info
Write-Host "  • Main App: http://127.0.0.1:$LaravelPort" -ForegroundColor $Colors.Highlight
Write-Host "  • Login: http://127.0.0.1:$LaravelPort/login" -ForegroundColor $Colors.Highlight
Write-Host "  • Register: http://127.0.0.1:$LaravelPort/register" -ForegroundColor $Colors.Highlight
Write-Host ""

# Open both URLs in browser
Write-Host "🚀 Opening URLs in browser..." -ForegroundColor $Colors.Warning
Start-Process "http://127.0.0.1:$LaravelPort"
Start-Process "http://127.0.0.1:$VitePort"
Write-Host "✓ URLs opened in browser" -ForegroundColor $Colors.Success

# Initial server status check
Write-Host "🔍 Performing initial server health checks..." -ForegroundColor $Colors.Warning
Start-Sleep -Seconds 3

# Monitor the servers
Write-Host "`nPress Ctrl+C to stop monitoring..." -ForegroundColor Yellow

Write-Host ""
Write-Host "🎉 Development environment setup complete!" -ForegroundColor $Colors.Success
Write-Host "📱 Vite (Frontend): http://127.0.0.1:$VitePort" -ForegroundColor $Colors.Info
Write-Host "🚀 Laravel (Backend): http://127.0.0.1:$LaravelPort" -ForegroundColor $Colors.Info
Write-Host "📋 Log file: $LogFile" -ForegroundColor $Colors.Info
Write-Host ""
Write-Host "💡 Both servers are running in separate windows" -ForegroundColor $Colors.Warning
Write-Host "💡 This script will now monitor both servers" -ForegroundColor $Colors.Warning
Write-Host ""

# Open the application in default browser
if ($laravelHealthy) {
    Write-Host "🌐 Opening application in browser..." -ForegroundColor $Colors.Success
    Start-Process "http://127.0.0.1:$LaravelPort"
    "$(Get-Date): Application opened in browser" | Out-File -FilePath $LogFile -Append
}

# Start monitoring loop
Write-Host "🔄 Starting server monitoring..." -ForegroundColor $Colors.Success
Write-Host "Press 'q' to quit, 'r' to restart servers, 'c' to clear caches, 'g' to view logs, 'm' to check memory" -ForegroundColor $Colors.Warning
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
    
    Write-Host "`r[$(Get-Date -Format 'HH:mm:ss')] Vite: $viteStatus | Laravel: $laravelStatus | Memory: $(Show-MemoryUsage)" -NoNewline -ForegroundColor $Colors.Success
    
    # Check for user input
    if ([Console]::KeyAvailable) {
        $key = [Console]::ReadKey($true)
        Write-Host "" # New line after status
        
        switch ($key.KeyChar.ToString().ToLower()) {
            'q' {
                Write-Host "🛑 Shutting down monitoring..." -ForegroundColor $Colors.Warning
                "$(Get-Date): Monitoring stopped by user" | Out-File -FilePath $LogFile -Append
                Write-Host "💡 Servers are still running in separate windows" -ForegroundColor $Colors.Warning
                Write-Host "💡 Close those windows to stop the servers" -ForegroundColor $Colors.Warning
                exit 0
            }
            'r' {
                Write-Host "🔄 Restarting servers..." -ForegroundColor $Colors.Warning
                Restart-Servers
                $lastViteCheck = Get-Date
                $lastLaravelCheck = Get-Date
            }
            'v' {
                Write-Host "🔄 Restarting Vite server only..." -ForegroundColor $Colors.Warning
                Restart-ViteServer
                $lastViteCheck = Get-Date
            }
            'l' {
                Write-Host "🔄 Restarting Laravel server only..." -ForegroundColor $Colors.Warning
                Restart-LaravelServer
                $lastLaravelCheck = Get-Date
            }
            'c' {
                Write-Host "🧹 Clearing Laravel caches..." -ForegroundColor $Colors.Warning
                Clear-LaravelCaches
            }
            'g' {
                Show-LogFile
            }
            'm' {
                Show-DetailedMemoryUsage
            }
            'h' {
                Write-Host "`n📖 AVAILABLE COMMANDS:" -ForegroundColor $Colors.Info
                Write-Host "  q - Quit monitoring (servers keep running)" -ForegroundColor $Colors.Highlight
                Write-Host "  r - Restart both servers" -ForegroundColor $Colors.Highlight
                Write-Host "  v - Restart Vite server only" -ForegroundColor $Colors.Highlight
                Write-Host "  l - Restart Laravel server only" -ForegroundColor $Colors.Highlight
                Write-Host "  c - Clear Laravel caches" -ForegroundColor $Colors.Highlight
                Write-Host "  g - Show recent log entries" -ForegroundColor $Colors.Highlight
                Write-Host "  m - Show detailed memory usage" -ForegroundColor $Colors.Highlight
                Write-Host "  h - Show this help" -ForegroundColor $Colors.Highlight
                Write-Host ""
            }
        }
    }
    
    # Auto-restart if servers are down
    if (-not $viteHealthy -or -not $laravelHealthy) {
        Write-Host "`n⚠️ Server(s) detected as down. Attempting auto-restart..." -ForegroundColor $Colors.Warning
        
        if (-not $viteHealthy) {
            Write-Host "🔄 Auto-restarting Vite server..." -ForegroundColor $Colors.Warning
            Restart-ViteServer
            $lastViteCheck = Get-Date
        }
        
        if (-not $laravelHealthy) {
            Write-Host "🔄 Auto-restarting Laravel server..." -ForegroundColor $Colors.Warning
            Restart-LaravelServer
            $lastLaravelCheck = Get-Date
        }
        
        "$(Get-Date): Auto-restart triggered" | Out-File -FilePath $LogFile -Append
        Start-Sleep -Seconds 5
    }
    
    Start-Sleep -Seconds 1
}