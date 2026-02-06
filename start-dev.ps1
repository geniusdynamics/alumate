#!/usr/bin/env pwsh
# ABOUTME: Development startup script for Laravel + Vite project
# ABOUTME: Automatically detects project directory and starts both frontend and backend servers

param(
    [switch]$SkipChecks,
    [int]$VitePort = 5173,
    [int]$LaravelPort = 8080
)

# Color output functions
function Write-Success { param($Message) Write-Host $Message -ForegroundColor Green }
function Write-Info { param($Message) Write-Host $Message -ForegroundColor Cyan }
function Write-Warning { param($Message) Write-Host $Message -ForegroundColor Yellow }
function Write-Error { param($Message) Write-Host $Message -ForegroundColor Red }

# Get the directory where this script is located
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

# PHP executable path
$phpPath = "D:\DevCenter\xampp\php-8.3.23\php.exe"

# Function to test if a port is available
function Test-Port {
    param([int]$Port)
    try {
        $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Any, $Port)
        $listener.Start()
        $listener.Stop()
        return $true
    }
    catch {
        return $false
    }
}

# Function to kill processes on specific ports
function Stop-ProcessOnPort {
    param([int]$Port)
    try {
        $processes = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess
        foreach ($pid in $processes) {
            if ($pid -and $pid -ne 0) {
                Stop-Process -Id $pid -Force -ErrorAction SilentlyContinue
                Write-Info "Stopped process $pid on port $Port"
            }
        }
    }
    catch {
        Write-Warning "Could not stop processes on port $Port"
    }
}

# Function to check if a command exists
function Test-Command {
    param([string]$Command)
    try {
        Get-Command $Command -ErrorAction Stop | Out-Null
        return $true
    }
    catch {
        return $false
    }
}

try {
    Write-Info "=== Laravel + Vite Development Server Startup ==="
    Write-Info "Project Directory: $ScriptDir"
    Write-Info "PHP Path: $phpPath"
    
    # Verify we're in a Laravel project
    if (-not (Test-Path "artisan")) {
        Write-Error "Laravel artisan file not found. Please run this script from your Laravel project root."
        exit 1
    }
    
    if (-not $SkipChecks) {
        # Check PHP
        if (-not (Test-Path $phpPath)) {
            Write-Error "PHP not found at: $phpPath"
            Write-Error "Please update the script with the correct PHP path."
            exit 1
        }
        
        # Check Node.js
        if (-not (Test-Command "node")) {
            Write-Error "Node.js not found. Please install Node.js."
            exit 1
        }
        
        # Check npm
        if (-not (Test-Command "npm")) {
            Write-Error "npm not found. Please install npm."
            exit 1
        }
        
        Write-Success "All dependencies found!"
    }
    
    # Handle port conflicts
    if (-not (Test-Port $VitePort)) {
        Write-Warning "Port $VitePort is in use. Attempting to free it..."
        Stop-ProcessOnPort $VitePort
        Start-Sleep -Seconds 2
    }
    
    if (-not (Test-Port $LaravelPort)) {
        Write-Warning "Port $LaravelPort is in use. Attempting to free it..."
        Stop-ProcessOnPort $LaravelPort
        Start-Sleep -Seconds 2
    }
    
    # Install/update dependencies
    Write-Info "Installing/updating dependencies..."
    if (Test-Path "package.json") {
        npm install
        if ($LASTEXITCODE -ne 0) {
            Write-Error "npm install failed"
            exit 1
        }
    }
    
    if (Test-Path "composer.json") {
        if (Test-Command "composer") {
            composer install --no-dev --optimize-autoloader 2>$null
            if ($LASTEXITCODE -ne 0) {
                Write-Warning "Composer install had issues, continuing..."
            }
        }
    }
    
    # Clear Laravel caches
    Write-Info "Clearing Laravel caches..."
    & $phpPath artisan config:clear 2>$null
    & $phpPath artisan route:clear 2>$null
    & $phpPath artisan view:clear 2>$null
    & $phpPath artisan cache:clear 2>$null
    
    Write-Success "Caches cleared!"
    
    # Start Vite development server
    Write-Info "Starting Vite development server on port $VitePort..."
    $viteJob = Start-Job -ScriptBlock {
        param($ScriptDir, $VitePort)
        Set-Location $ScriptDir
        npm run dev -- --port $VitePort --host 0.0.0.0
    } -ArgumentList $ScriptDir, $VitePort
    
    Start-Sleep -Seconds 3
    
    # Start Laravel development server
    Write-Info "Starting Laravel development server on port $LaravelPort..."
    $laravelJob = Start-Job -ScriptBlock {
        param($ScriptDir, $phpPath, $LaravelPort)
        Set-Location $ScriptDir
        & $phpPath artisan serve --host=127.0.0.1 --port=$LaravelPort
    } -ArgumentList $ScriptDir, $phpPath, $LaravelPort
    
    Start-Sleep -Seconds 3
    
    # Check if servers started successfully
    $viteRunning = $viteJob.State -eq "Running"
    $laravelRunning = $laravelJob.State -eq "Running"
    
    if ($viteRunning -and $laravelRunning) {
        Write-Success "=== Development servers started successfully! ==="
        Write-Success "Frontend (Vite): http://localhost:$VitePort"
        Write-Success "Backend (Laravel): http://127.0.0.1:$LaravelPort"
        Write-Info "Press Ctrl+C to stop both servers"
        
        # Monitor jobs and wait for user interruption
        try {
            while ($true) {
                if ($viteJob.State -ne "Running" -or $laravelJob.State -ne "Running") {
                    Write-Warning "One or more servers stopped unexpectedly"
                    break
                }
                Start-Sleep -Seconds 5
            }
        }
        catch {
            Write-Info "Shutting down servers..."
        }
    } else {
        Write-Error "Failed to start one or more servers"
        if (-not $viteRunning) {
            Write-Error "Vite server failed to start"
            Receive-Job $viteJob
        }
        if (-not $laravelRunning) {
            Write-Error "Laravel server failed to start"
            Receive-Job $laravelJob
        }
    }
}
finally {
    # Cleanup: Stop all background jobs
    Write-Info "Cleaning up background processes..."
    
    if ($viteJob) {
        Stop-Job $viteJob -ErrorAction SilentlyContinue
        Remove-Job $viteJob -ErrorAction SilentlyContinue
        Write-Info "Vite server stopped"
    }
    
    if ($laravelJob) {
        Stop-Job $laravelJob -ErrorAction SilentlyContinue
        Remove-Job $laravelJob -ErrorAction SilentlyContinue
        Write-Info "Laravel server stopped"
    }
    
    # Additional cleanup for any remaining processes
    Stop-ProcessOnPort $VitePort
    Stop-ProcessOnPort $LaravelPort
    
    Write-Success "Cleanup completed!"
}