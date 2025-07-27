@echo off
color 0A
echo ========================================
echo   Graduate Tracking System - Dev Setup
echo ========================================
echo.

REM Start Laravel server in background
echo [1/2] Starting Laravel server on port 8080...
start "Laravel Server" D:\DevCenter\xampp\php-8.3.23\php.exe artisan serve --port=8080

REM Wait a moment for Laravel to start
timeout /t 5 /nobreak >nul

REM Start npm dev server
echo [2/2] Starting Vite development server...
start "Vite Dev Server" npm run dev

REM Wait for servers to fully start
timeout /t 3 /nobreak >nul

echo.
echo ========================================
echo   DEVELOPMENT SERVERS RUNNING
echo ========================================
echo.
echo ^> Laravel Application: http://127.0.0.1:8080
echo   - Super Admin Login: admin@system.com / password
echo   - Institution Admin: admin@tech-institute.edu / password
echo   - Institution Admin: admin@business-college.edu / password
echo.
echo ^> Vite Dev Server: http://localhost:5173 (for assets only)
echo.
echo ========================================
echo   QUICK ACCESS LINKS
echo ========================================
echo.
echo ^> Super Admin Dashboard: http://127.0.0.1:8080/super-admin/dashboard
echo ^> Login Page: http://127.0.0.1:8080/login
echo ^> Register Page: http://127.0.0.1:8080/register
echo.
echo ========================================
echo.
echo Opening Laravel application in browser...
timeout /t 2 /nobreak >nul
start http://127.0.0.1:8080

echo.
echo Press any key to stop all servers...
pause >nul

echo.
echo Stopping servers...
taskkill /f /im php.exe 2>nul
taskkill /f /im node.exe 2>nul
echo.
echo ========================================
echo   All servers stopped. Goodbye!
echo ========================================