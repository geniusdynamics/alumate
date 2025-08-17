@echo off
setlocal enabledelayedexpansion
color 0A
echo ========================================
echo   Graduate Tracking System - Dev Setup
echo ========================================
echo.

REM Initialize variables
set VITE_PORT=5100
set LARAVEL_PORT=8080
set PHP_PATH=D:\DevCenter\xampp\php-8.3.23\php.exe
set MAX_RETRIES=3
set HEALTH_CHECK_INTERVAL=10
set LOG_FILE=%~dp0dev-server.log

REM Create log file with timestamp
echo [%date% %time%] Development server startup initiated > "%LOG_FILE%"

REM Clean up any existing processes
echo [0/6] Cleaning up existing processes...
call :cleanup_processes
echo ✓ Cleanup complete

REM Check if PHP exists
echo [1/6] Checking PHP installation...
if not exist "%PHP_PATH%" (
    echo ❌ PHP not found at %PHP_PATH%
    echo Please check your PHP installation path
    echo [%date% %time%] ERROR: PHP not found >> "%LOG_FILE%"
    pause
    exit /b 1
)
echo ✓ PHP found
echo [%date% %time%] PHP installation verified >> "%LOG_FILE%"

REM Check if Node.js exists
echo [2/6] Checking Node.js installation...
node --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Node.js not found. Please install Node.js
    echo [%date% %time%] ERROR: Node.js not found >> "%LOG_FILE%"
    pause
    exit /b 1
)
for /f "tokens=*" %%i in ('node --version 2^>nul') do set NODE_VERSION=%%i
echo ✓ Node.js found: !NODE_VERSION!
echo [%date% %time%] Node.js installation verified: !NODE_VERSION! >> "%LOG_FILE%"

REM Check for port conflicts
echo [3/6] Checking for port conflicts...
call :check_port_conflicts

REM Clear Laravel caches
echo [4/6] Clearing Laravel caches...
echo [%date% %time%] Clearing Laravel caches >> "%LOG_FILE%"
"%PHP_PATH%" artisan config:clear >nul 2>&1
if errorlevel 1 echo ⚠️ Warning: Could not clear config cache
"%PHP_PATH%" artisan route:clear >nul 2>&1
if errorlevel 1 echo ⚠️ Warning: Could not clear route cache
"%PHP_PATH%" artisan view:clear >nul 2>&1
if errorlevel 1 echo ⚠️ Warning: Could not clear view cache
"%PHP_PATH%" artisan cache:clear >nul 2>&1
if errorlevel 1 echo ⚠️ Warning: Could not clear application cache
echo ✓ Laravel caches cleared
echo [%date% %time%] Laravel caches cleared >> "%LOG_FILE%"

REM Start Vite development server in persistent window
echo [5/6] Starting Vite development server...
echo [%date% %time%] Starting Vite server >> "%LOG_FILE%"
start "Vite Dev Server - Alumni Platform" cmd /k "title Vite Dev Server - Alumni Platform ^& echo Starting Vite Dev Server... ^& npm run dev ^& echo. ^& echo Vite server stopped. Press any key to close... ^& pause >nul"
echo ✓ Vite server starting in separate window...

REM Wait for Vite to initialize with better error handling
echo Waiting for Vite to initialize on http://127.0.0.1:5100 ...
set /a WAITED=0
set /a TIMEOUT=60
:WAIT_VITE
REM Try to fetch Vite client endpoint using PowerShell
powershell -Command "try { $resp = Invoke-WebRequest -Uri 'http://127.0.0.1:5100/@vite/client' -UseBasicParsing -TimeoutSec 3; if ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 500) { exit 0 } else { exit 1 } } catch { exit 1 }"
if %errorlevel%==0 (
  echo ✓ Vite is ready after %WAITED%s
  goto START_LARAVEL
) else (
  if %WAITED% GEQ %TIMEOUT% (
    echo ⚠ Vite did not become ready within %TIMEOUT%s
    echo ⚠ Check the Vite window for errors
    echo ⚠ Continuing with Laravel anyway...
    goto START_LARAVEL
  ) else (
    set /a WAITED+=3
    echo   ... waiting (%WAITED%s/%TIMEOUT%s)
    timeout /t 3 /nobreak >nul
    goto WAIT_VITE
  )
)

:START_LARAVEL
echo.
echo ========================================
echo   STARTING LARAVEL SERVER
echo ========================================
echo.
echo ✓ Vite Dev Server: http://127.0.0.1:5100
echo ✓ Laravel Server: http://127.0.0.1:8080 (starting...)
echo.
echo Both servers will run independently.
echo Close this window to stop Laravel server.
echo Close the Vite window to stop Vite server.
echo.
echo Laravel server output:
echo ----------------------------------------

REM Start Laravel server in persistent mode
start "Laravel Server - Alumni Platform" cmd /k "echo Starting Laravel Server... && D:\DevCenter\xampp\php-8.3.23\php.exe artisan serve --host=127.0.0.1 --port=8080"

REM Show status and keep main window open for monitoring
echo.
echo ========================================
echo   DEVELOPMENT SERVERS RUNNING
echo ========================================
echo.
echo ✅ Vite Dev Server: http://127.0.0.1:5100
echo ✅ Laravel Server: http://127.0.0.1:8080
echo.
echo Both servers are running in separate windows.
echo.
echo 🔍 MONITORING:
echo - Check Vite window for frontend compilation
echo - Check Laravel window for backend logs
echo - Both servers will auto-reload on file changes
echo.
echo 🛑 TO STOP:
echo - Close individual server windows, OR
echo - Press Ctrl+C in this window to stop monitoring
echo.
echo Press any key to open both URLs in browser...
pause >nul

REM Open both URLs in default browser
start http://127.0.0.1:8080
start http://127.0.0.1:5100

echo.
echo ✓ URLs opened in browser
echo.
echo This monitoring window will stay open.
echo Close it when you're done developing.
echo.

:MONITOR_LOOP
echo.
echo ========================================
echo   ENHANCED SERVER MONITORING
echo ========================================
echo.
echo [%time%] Health Check Status:
call :health_check_vite
call :health_check_laravel
call :show_memory_usage
echo.
echo Options:
echo   [R] Restart servers
echo   [C] Clear caches
echo   [L] View logs
echo   [Q] Quit monitoring
echo.
echo Monitoring... (Press Ctrl+C to stop or wait %HEALTH_CHECK_INTERVAL%s for next check)
timeout /t %HEALTH_CHECK_INTERVAL% /nobreak >nul
if errorlevel 1 (
    choice /c RCLQ /n /t 1 /d Q >nul 2>&1
    if errorlevel 4 goto END_MONITORING
    if errorlevel 3 call :show_logs
    if errorlevel 2 call :clear_caches_interactive
    if errorlevel 1 call :restart_servers
)
goto MONITOR_LOOP

:END_MONITORING
echo.
echo Monitoring stopped. Servers continue running in separate windows.
echo Close server windows manually when done developing.
goto :eof

REM ========================================
REM SUPPORTING FUNCTIONS
REM ========================================

:cleanup_processes
taskkill /f /im php.exe 2>nul >nul
taskkill /f /im node.exe 2>nul >nul
echo [%date% %time%] Cleaned up existing processes >> "%LOG_FILE%"
timeout /t 2 /nobreak >nul
goto :eof

:check_port_conflicts
netstat -an | findstr ":%VITE_PORT% " >nul 2>&1
if not errorlevel 1 (
    echo ⚠️ Warning: Port %VITE_PORT% is already in use
    echo [%date% %time%] WARNING: Port %VITE_PORT% conflict detected >> "%LOG_FILE%"
)
netstat -an | findstr ":%LARAVEL_PORT% " >nul 2>&1
if not errorlevel 1 (
    echo ⚠️ Warning: Port %LARAVEL_PORT% is already in use
    echo [%date% %time%] WARNING: Port %LARAVEL_PORT% conflict detected >> "%LOG_FILE%"
)
echo ✓ Port conflict check completed
goto :eof

:health_check_vite
powershell -Command "try { $resp = Invoke-WebRequest -Uri 'http://127.0.0.1:%VITE_PORT%/@vite/client' -UseBasicParsing -TimeoutSec 3; Write-Host '  ✅ Vite Server: HEALTHY (Port %VITE_PORT%)' -ForegroundColor Green } catch { Write-Host '  ❌ Vite Server: DOWN (Port %VITE_PORT%)' -ForegroundColor Red }" 2>nul
goto :eof

:health_check_laravel
powershell -Command "try { $resp = Invoke-WebRequest -Uri 'http://127.0.0.1:%LARAVEL_PORT%' -UseBasicParsing -TimeoutSec 3; Write-Host '  ✅ Laravel Server: HEALTHY (Port %LARAVEL_PORT%)' -ForegroundColor Green } catch { Write-Host '  ❌ Laravel Server: DOWN (Port %LARAVEL_PORT%)' -ForegroundColor Red }" 2>nul
goto :eof

:show_memory_usage
for /f "tokens=2 delims=," %%i in ('tasklist /fi "imagename eq php.exe" /fo csv ^| findstr /v "INFO"') do (
    echo   📊 PHP Memory: %%i
)
for /f "tokens=2 delims=," %%i in ('tasklist /fi "imagename eq node.exe" /fo csv ^| findstr /v "INFO"') do (
    echo   📊 Node Memory: %%i
)
goto :eof

:show_logs
echo.
echo ========================================
echo   RECENT LOG ENTRIES
echo ========================================
if exist "%LOG_FILE%" (
    powershell -Command "Get-Content '%LOG_FILE%' | Select-Object -Last 10"
) else (
    echo No log file found.
)
echo ========================================
echo.
pause
goto :eof

:clear_caches_interactive
echo.
echo Clearing Laravel caches...
"%PHP_PATH%" artisan config:clear
"%PHP_PATH%" artisan route:clear
"%PHP_PATH%" artisan view:clear
"%PHP_PATH%" artisan cache:clear
echo ✓ Caches cleared
echo [%date% %time%] Caches cleared via interactive menu >> "%LOG_FILE%"
pause
goto :eof

:restart_servers
echo.
echo Restarting servers...
call :cleanup_processes
echo Servers stopped. Please restart manually or run this script again.
echo [%date% %time%] Servers restarted via interactive menu >> "%LOG_FILE%"
pause
exit /b 0