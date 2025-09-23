@echo off
REM ABOUTME: Development startup script for Laravel + Vite project (Windows Batch)
REM ABOUTME: Automatically detects project directory and starts both frontend and backend servers

setlocal enabledelayedexpansion

REM Default configuration
set VITE_PORT=5173
set LARAVEL_PORT=8080
set SKIP_CHECKS=false
set PHP_PATH=D:\DevCenter\xampp\php-8.3.23\php.exe

REM Parse command line arguments
:parse_args
if "%~1"=="" goto start_script
if /i "%~1"=="--skip-checks" (
    set SKIP_CHECKS=true
    shift
    goto parse_args
)
if /i "%~1"=="--vite-port" (
    set VITE_PORT=%~2
    shift
    shift
    goto parse_args
)
if /i "%~1"=="--laravel-port" (
    set LARAVEL_PORT=%~2
    shift
    shift
    goto parse_args
)
echo Unknown option: %~1
echo Usage: %0 [--skip-checks] [--vite-port PORT] [--laravel-port PORT]
exit /b 1

:start_script
REM Get the directory where this script is located
set SCRIPT_DIR=%~dp0
cd /d "%SCRIPT_DIR%"

echo [INFO] === Laravel + Vite Development Server Startup ===
echo [INFO] Project Directory: %SCRIPT_DIR%
echo [INFO] PHP Path: %PHP_PATH%

REM Verify we're in a Laravel project
if not exist "artisan" (
    echo [ERROR] Laravel artisan file not found. Please run this script from your Laravel project root.
    exit /b 1
)

if /i "%SKIP_CHECKS%"=="false" (
    REM Check PHP
    if not exist "%PHP_PATH%" (
        echo [ERROR] PHP not found at: %PHP_PATH%
        echo [ERROR] Please update the script with the correct PHP path.
        exit /b 1
    )
    
    REM Check Node.js
    node --version >nul 2>&1
    if errorlevel 1 (
        echo [ERROR] Node.js not found. Please install Node.js.
        exit /b 1
    )
    
    REM Check npm
    npm --version >nul 2>&1
    if errorlevel 1 (
        echo [ERROR] npm not found. Please install npm.
        exit /b 1
    )
    
    echo [SUCCESS] All dependencies found!
)

REM Function to kill processes on specific ports
echo [INFO] Checking for existing processes on ports...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%VITE_PORT% "') do (
    if not "%%a"=="" (
        echo [INFO] Stopping process %%a on port %VITE_PORT%
        taskkill /f /pid %%a >nul 2>&1
    )
)

for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%LARAVEL_PORT% "') do (
    if not "%%a"=="" (
        echo [INFO] Stopping process %%a on port %LARAVEL_PORT%
        taskkill /f /pid %%a >nul 2>&1
    )
)

REM Install/update dependencies
echo [INFO] Installing/updating dependencies...
if exist "package.json" (
    call npm install
    if errorlevel 1 (
        echo [ERROR] npm install failed
        exit /b 1
    )
)

if exist "composer.json" (
    composer --version >nul 2>&1
    if not errorlevel 1 (
        composer install --no-dev --optimize-autoloader >nul 2>&1
        if errorlevel 1 (
            echo [WARNING] Composer install had issues, continuing...
        )
    )
)

REM Clear Laravel caches
echo [INFO] Clearing Laravel caches...

"%PHP_PATH%" artisan config:clear >nul 2>&1
"%PHP_PATH%" artisan route:clear >nul 2>&1
"%PHP_PATH%" artisan view:clear >nul 2>&1
"%PHP_PATH%" artisan cache:clear >nul 2>&1

echo [SUCCESS] Caches cleared!

REM Create temporary batch files for background processes
echo @echo off > vite_server.bat
echo cd /d "%SCRIPT_DIR%" >> vite_server.bat
echo npm run dev -- --port %VITE_PORT% --host 0.0.0.0 >> vite_server.bat

echo @echo off > laravel_server.bat
echo cd /d "%SCRIPT_DIR%" >> laravel_server.bat
echo "%PHP_PATH%" artisan serve --host=127.0.0.1 --port=%LARAVEL_PORT% >> laravel_server.bat

REM Start Vite development server
echo [INFO] Starting Vite development server on port %VITE_PORT%...
start "Vite Server" /min cmd /c vite_server.bat
timeout /t 3 /nobreak >nul

REM Start Laravel development server
echo [INFO] Starting Laravel development server on port %LARAVEL_PORT%...
start "Laravel Server" /min cmd /c laravel_server.bat
timeout /t 3 /nobreak >nul

REM Check if servers are running
netstat -an | findstr ":%VITE_PORT% " >nul
set VITE_RUNNING=%errorlevel%

netstat -an | findstr ":%LARAVEL_PORT% " >nul
set LARAVEL_RUNNING=%errorlevel%

if %VITE_RUNNING%==0 if %LARAVEL_RUNNING%==0 (
    echo [SUCCESS] === Development servers started successfully! ===
    echo [SUCCESS] Frontend (Vite): http://localhost:%VITE_PORT%
    echo [SUCCESS] Backend (Laravel): http://127.0.0.1:%LARAVEL_PORT%
    echo [INFO] Press Ctrl+C to stop both servers
    echo [INFO] Close this window to stop the servers
    
    REM Keep the script running
    :monitor_loop
    timeout /t 5 /nobreak >nul
    
    REM Check if servers are still running
    netstat -an | findstr ":%VITE_PORT% " >nul
    if errorlevel 1 (
        echo [WARNING] Vite server stopped unexpectedly
        goto cleanup
    )
    
    netstat -an | findstr ":%LARAVEL_PORT% " >nul
    if errorlevel 1 (
        echo [WARNING] Laravel server stopped unexpectedly
        goto cleanup
    )
    
    goto monitor_loop
) else (
    echo [ERROR] Failed to start one or more servers
    if not %VITE_RUNNING%==0 (
        echo [ERROR] Vite server failed to start
    )
    if not %LARAVEL_RUNNING%==0 (
        echo [ERROR] Laravel server failed to start
    )
)

:cleanup
echo [INFO] Cleaning up background processes...

REM Kill processes on the ports
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%VITE_PORT% "') do (
    if not "%%a"=="" (
        echo [INFO] Stopping Vite process %%a
        taskkill /f /pid %%a >nul 2>&1
    )
)

for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%LARAVEL_PORT% "') do (
    if not "%%a"=="" (
        echo [INFO] Stopping Laravel process %%a
        taskkill /f /pid %%a >nul 2>&1
    )
)

REM Clean up temporary files
if exist "vite_server.bat" del "vite_server.bat"
if exist "laravel_server.bat" del "laravel_server.bat"

echo [SUCCESS] Cleanup completed!
pause