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

REM Start Vite development server first
echo [4/4] Starting Vite development server...
start "Vite Dev Server" /min cmd /c "npm run dev"
echo ✓ Vite server starting...

REM Wait for Vite to initialize
echo Waiting for Vite to initialize...
timeout /t 10 /nobreak >nul

REM Start Laravel server with error checking
echo Starting Laravel server...
echo ========================================
echo   LARAVEL SERVER OUTPUT
echo ========================================
echo.
echo Starting PHP Artisan Serve...
echo If you see errors below, they will help debug the issue:
echo.

REM Start Laravel in foreground so we can see errors
D:\DevCenter\xampp\php-8.3.23\php.exe artisan serve --host=127.0.0.1 --port=8080

REM If we reach here, Laravel has stopped
echo.
echo ========================================
echo   LARAVEL SERVER STOPPED
echo ========================================
echo.
echo The Laravel server has stopped. Check the output above for errors.
echo.
echo Common issues:
echo - Port 8080 already in use
echo - Database connection problems
echo - Missing .env file
echo - Composer dependencies not installed
echo.

echo Cleaning up Vite server...
taskkill /f /im node.exe 2>nul >nul

echo.
echo Press any key to exit...
pause >nul