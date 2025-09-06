@echo off
color 0A
echo ========================================
echo   Graduate Tracking System - Dev Setup
echo ========================================
echo.

REM Clean up any existing processes
echo [0/4] Cleaning up existing processes...
taskkill /f /im php.exe 2>nul >nul
taskkill /f /im node.exe 2>nul >nul
timeout /t 2 /nobreak >nul
echo ✓ Cleanup complete

REM Check if PHP exists
echo [1/4] Checking PHP installation...
if not exist "D:\DevCenter\xampp\php-8.3.23\php.exe" (
    echo ❌ PHP not found at D:\DevCenter\xampp\php-8.3.23\php.exe
    echo Please check your PHP installation path
    pause
    exit /b 1
)
echo ✓ PHP found

REM Check if Node.js exists
echo [2/4] Checking Node.js installation...
node --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Node.js not found. Please install Node.js
    pause
    exit /b 1
)
echo ✓ Node.js found

REM Clear Laravel caches
echo [3/4] Clearing Laravel caches...
D:\DevCenter\xampp\php-8.3.23\php.exe artisan config:clear >nul 2>&1
D:\DevCenter\xampp\php-8.3.23\php.exe artisan route:clear >nul 2>&1
D:\DevCenter\xampp\php-8.3.23\php.exe artisan view:clear >nul 2>&1
D:\DevCenter\xampp\php-8.3.23\php.exe artisan cache:clear >nul 2>&1
echo ✓ Caches cleared

REM Start Vite development server in persistent window
echo [4/4] Starting Vite development server...
start "Vite Dev Server - Alumni Platform" cmd /k "echo Starting Vite Dev Server... && npm run dev"
echo ✓ Vite server starting in separate window...

REM Wait for Vite to initialize with better error handling
echo Waiting for Vite to initialize on http://127.0.0.1:5173 ...
set /a WAITED=0
set /a TIMEOUT=60
:WAIT_VITE
REM Try to fetch Vite client endpoint using PowerShell
powershell -Command "try { $resp = Invoke-WebRequest -Uri 'http://127.0.0.1:5173/@vite/client' -UseBasicParsing -TimeoutSec 3; if ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 500) { exit 0 } else { exit 1 } } catch { exit 1 }"
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
echo ✓ Vite Dev Server: http://127.0.0.1:5173
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
echo ✅ Vite Dev Server: http://127.0.0.1:5173
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
echo [%time%] Monitoring servers... (Press Ctrl+C to stop)
timeout /t 30 /nobreak >nul
goto MONITOR_LOOP