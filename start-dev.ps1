# ABOUTME: Fixed PowerShell script for starting Vite and Laravel development servers
# ABOUTME: Includes proper error handling, execution policy check, and correct PHP paths

# Check execution policy first
$currentPolicy = Get-ExecutionPolicy -Scope CurrentUser
if ($currentPolicy -eq \"Restricted\") {
    Write-Host \"❌ PowerShell execution policy is Restricted\" -ForegroundColor Red
    Write-Host \"\"
    Write-Host \"To fix this, run as Administrator:\" -ForegroundColor Yellow
    Write-Host \"Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser\" -ForegroundColor Cyan
    Write-Host \"\"
    Write-Host \"Alternatively, use start-dev.bat which doesn't have this restriction.\" -ForegroundColor Green
    Write-Host \"\"
    Read-Host \"Press Enter to exit\"
    exit 1
}

# Configuration
$VitePort = 5100
$LaravelPort = 8080
$LogFile = \"dev-server.log\"

# Correct executable paths for this project
$NodePath = \"node\"
$PhpPath = \"D:\\DevCenter\\xampp\\php-8.3.23\\php.exe\"
$PnpmPath = \"pnpm\"

# Color scheme
$Colors = @{
    Success = \"Green\"
    Error = \"Red\"
    Warning = \"Yellow\"
    Info = \"Cyan\"
    Highlight = \"Magenta\"
    Muted = \"Gray\"
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
                Write-Host \"Stopping process $($process.Name) (PID: $($process.Id)) on port $Port\" -ForegroundColor $Colors.Warning
                Stop-Process -Id $process.Id -Force
            }
        }
    }
    catch {
        Write-Host \"Error stopping processes on port $Port : $($_.Exception.Message)\" -ForegroundColor $Colors.Error
    }
}

# Function to test Vite server health
function Test-ViteHealth {
    try {
        $response = Invoke-WebRequest -Uri \"http://127.0.0.1:$VitePort/@vite/client\" -UseBasicParsing -TimeoutSec 3 -ErrorAction SilentlyContinue
        return $response.StatusCode -ge 200 -and $response.StatusCode -lt 500
    }
    catch {
        return $false
    }
}

# Main script starts here
Write-Host \"========================================\" -ForegroundColor $Colors.Success
Write-Host \"   Graduate Tracking System - Dev Setup\" -ForegroundColor $Colors.Success
Write-Host \"========================================\" -ForegroundColor $Colors.Success
Write-Host \"\"

# Cleanup existing processes
Write-Host \"[0/5] Cleaning up existing processes...\" -ForegroundColor $Colors.Warning
try {
    taskkill /F /IM php.exe 2>$null | Out-Null
    taskkill /F /IM node.exe 2>$null | Out-Null
    Start-Sleep -Seconds 2
    Write-Host \"✓ Cleanup complete\" -ForegroundColor $Colors.Success
}
catch {
    Write-Host \"✓ No existing processes to clean up\" -ForegroundColor $Colors.Success
}

# Check PHP installation
Write-Host \"[1/5] Checking PHP installation...\" -ForegroundColor $Colors.Warning
if (-not (Test-Path $PhpPath)) {
    Write-Host \"❌ PHP not found at $PhpPath\" -ForegroundColor $Colors.Error
    Write-Host \"Please check your PHP installation path\" -ForegroundColor $Colors.Error
    Read-Host \"Press Enter to exit\"
    exit 1
}
Write-Host \"✓ PHP found\" -ForegroundColor $Colors.Success

# Check Node.js installation
Write-Host \"[2/5] Checking Node.js installation...\" -ForegroundColor $Colors.Warning
try {
    $nodeVersion = & $NodePath --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host \"✓ Node.js found: $nodeVersion\" -ForegroundColor $Colors.Success
    } else {
        throw \"Node.js not found\"
    }
}
catch {
    Write-Host \"❌ Node.js not found. Please install Node.js\" -ForegroundColor $Colors.Error
    Read-Host \"Press Enter to exit\"
    exit 1
}

# Check pnpm installation
Write-Host \"[3/5] Checking pnpm installation...\" -ForegroundColor $Colors.Warning
try {
    $pnpmVersion = & $PnpmPath --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host \"✓ pnpm found: $pnpmVersion\" -ForegroundColor $Colors.Success
    } else {
        throw \"pnpm not found\"
    }
}
catch {
    Write-Host \"❌ pnpm not found. Please install pnpm\" -ForegroundColor $Colors.Error
    Read-Host \"Press Enter to exit\"
    exit 1
}

# Clear Laravel caches
Write-Host \"[4/5] Clearing Laravel caches...\" -ForegroundColor $Colors.Warning
try {
    & $PhpPath artisan config:clear 2>$null | Out-Null
    & $PhpPath artisan route:clear 2>$null | Out-Null
    & $PhpPath artisan view:clear 2>$null | Out-Null
    & $PhpPath artisan cache:clear 2>$null | Out-Null
    Write-Host \"✓ Laravel caches cleared\" -ForegroundColor $Colors.Success
}
catch {
    Write-Host \"⚠️ Warning: Could not clear some caches\" -ForegroundColor $Colors.Warning
}

# Check for port conflicts
Write-Host \"[5/5] Checking for port conflicts...\" -ForegroundColor $Colors.Warning
if (Test-PortInUse -Port $VitePort) {
    Write-Host \"⚠️ Port $VitePort is in use. Attempting to free it...\" -ForegroundColor $Colors.Warning
    Stop-ProcessOnPort -Port $VitePort
    Start-Sleep -Seconds 2
}

if (Test-PortInUse -Port $LaravelPort) {
    Write-Host \"⚠️ Port $LaravelPort is in use. Attempting to free it...\" -ForegroundColor $Colors.Warning
    Stop-ProcessOnPort -Port $LaravelPort
    Start-Sleep -Seconds 2
}
Write-Host \"✓ Port check complete\" -ForegroundColor $Colors.Success

Write-Host \"\"
Write-Host \"========================================\" -ForegroundColor $Colors.Info
Write-Host \"   STARTING DEVELOPMENT SERVERS\" -ForegroundColor $Colors.Info
Write-Host \"========================================\" -ForegroundColor $Colors.Info
Write-Host \"\"

# Start Vite development server
Write-Host \"Starting Vite development server...\" -ForegroundColor $Colors.Info
Start-Process -FilePath \"cmd.exe\" -ArgumentList \"/k\", \"title Vite Dev Server - Alumni Platform & echo Starting Vite Dev Server... & pnpm run dev\" -WindowStyle Normal
Write-Host \"✓ Vite server starting in separate window...\" -ForegroundColor $Colors.Success

# Wait for Vite to initialize
Write-Host \"Waiting for Vite to initialize on http://127.0.0.1:$VitePort ...\" -ForegroundColor $Colors.Info
$waited = 0
$timeout = 60
$viteReady = $false

do {
    if (Test-ViteHealth) {
        Write-Host \"✓ Vite is ready after $waited seconds\" -ForegroundColor $Colors.Success
        $viteReady = $true
        break
    }
    
    if ($waited -ge $timeout) {
        Write-Host \"⚠ Vite did not become ready within $timeout seconds\" -ForegroundColor $Colors.Warning
        Write-Host \"⚠ Check the Vite window for errors\" -ForegroundColor $Colors.Warning
        Write-Host \"⚠ Continuing with Laravel anyway...\" -ForegroundColor $Colors.Warning
        break
    }

    $waited += 3
    Write-Host \"   ... waiting ($waited / $timeout seconds)\" -ForegroundColor $Colors.Muted
    Start-Sleep -Seconds 3
} while ($true)

# Start Laravel server
Write-Host \"\"
Write-Host \"Starting Laravel development server...\" -ForegroundColor $Colors.Info
Start-Process -FilePath \"cmd.exe\" -ArgumentList \"/k\", \"title Laravel Server - Alumni Platform & echo Starting Laravel Server... & $PhpPath artisan serve --host=127.0.0.1 --port=$LaravelPort\" -WindowStyle Normal
Write-Host \"✓ Laravel server starting in separate window...\" -ForegroundColor $Colors.Success

# Show final status
Write-Host \"\"
Write-Host \"========================================\" -ForegroundColor $Colors.Success
Write-Host \"   DEVELOPMENT SERVERS RUNNING\" -ForegroundColor $Colors.Success
Write-Host \"========================================\" -ForegroundColor $Colors.Success
Write-Host \"\"
Write-Host \"✅ Vite Dev Server: http://127.0.0.1:$VitePort\" -ForegroundColor $Colors.Success
Write-Host \"✅ Laravel Server: http://127.0.0.1:$LaravelPort\" -ForegroundColor $Colors.Success
Write-Host \"\"
Write-Host \"Both servers are running in separate windows.\" -ForegroundColor $Colors.Info
Write-Host \"\"
Write-Host \"🔍 MONITORING:\" -ForegroundColor $Colors.Highlight
Write-Host \"- Check Vite window for frontend compilation\" -ForegroundColor $Colors.Info
Write-Host \"- Check Laravel window for backend logs\" -ForegroundColor $Colors.Info
Write-Host \"- Both servers will auto-reload on file changes\" -ForegroundColor $Colors.Info
Write-Host \"\"
Write-Host \"🛑 TO STOP:\" -ForegroundColor $Colors.Highlight
Write-Host \"- Close individual server windows, OR\" -ForegroundColor $Colors.Info
Write-Host \"- Press Ctrl+C in this window to stop monitoring\" -ForegroundColor $Colors.Info
Write-Host \"\"

# Ask user if they want to open URLs
$openUrls = Read-Host \"Open both URLs in browser? (Y/n)\"
if ($openUrls -ne \"n\" -and $openUrls -ne \"N\") {
    Write-Host \"Opening URLs in browser...\" -ForegroundColor $Colors.Info
    Start-Process \"http://127.0.0.1:$LaravelPort\"
    Start-Sleep -Seconds 1
    Start-Process \"http://127.0.0.1:$VitePort\"
    Write-Host \"✓ URLs opened in browser\" -ForegroundColor $Colors.Success
}

Write-Host \"\"
Write-Host \"This monitoring window will stay open.\" -ForegroundColor $Colors.Info
Write-Host \"Close it when you're done developing.\" -ForegroundColor $Colors.Info
Write-Host \"\"

# Keep the script running for monitoring
do {
    $timestamp = Get-Date -Format \"HH:mm:ss\"
    Write-Host \"[$timestamp] Monitoring servers... (Press Ctrl+C to stop)\" -ForegroundColor $Colors.Muted
    Start-Sleep -Seconds 30
} while ($true)