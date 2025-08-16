@echo off
color 0F
title Graduate Tracking System - Development Helper

:MAIN_MENU
cls
echo ========================================
echo   Graduate Tracking System
echo   Development Helper
echo ========================================
echo.
echo [1] Start Development Servers
echo [2] Check System Status
echo [3] Run Database Migrations
echo [4] Create Sample Data
echo [5] View Demo Accounts
echo [6] Setup PHP PATH
echo [7] Open Application URLs
echo [8] View Logs
echo [9] Exit
echo.
set /p choice="Select an option (1-9): "

if "%choice%"=="1" goto START_SERVERS
if "%choice%"=="2" goto CHECK_STATUS
if "%choice%"=="3" goto RUN_MIGRATIONS
if "%choice%"=="4" goto CREATE_DATA
if "%choice%"=="5" goto SHOW_ACCOUNTS
if "%choice%"=="6" goto SETUP_PATH
if "%choice%"=="7" goto OPEN_URLS
if "%choice%"=="8" goto VIEW_LOGS
if "%choice%"=="9" goto EXIT

echo Invalid choice. Please try again.
pause
goto MAIN_MENU

:START_SERVERS
cls
echo ========================================
echo   Starting Development Servers
echo ========================================
echo.
call ..\..\start-dev.bat
goto MAIN_MENU

:CHECK_STATUS
cls
echo ========================================
echo   System Status Check
echo ========================================
echo.

echo Checking PHP...
D:\DevCenter\xampp\php-8.3.23\php.exe --version
echo.

echo Checking Laravel...
cd ..\..
D:\DevCenter\xampp\php-8.3.23\php.exe artisan --version
cd scripts\development
echo.

echo Checking Node.js...
node --version
echo.

echo Checking NPM...
npm --version
echo.

echo Checking if servers are running...
netstat -an | findstr ":8080" >nul
if %errorlevel% equ 0 (
    echo ✓ Laravel server is running on port 8080
) else (
    echo ✗ Laravel server is not running
)

netstat -an | findstr ":5100" >nul
if %errorlevel% equ 0 (
    echo ✓ Vite server is running on port 5100
) else (
    echo ✗ Vite server is not running
)

echo.
pause
goto MAIN_MENU

:RUN_MIGRATIONS
cls
echo ========================================
echo   Running Database Migrations
echo ========================================
echo.

cd ..\..
echo Running central migrations...
D:\DevCenter\xampp\php-8.3.23\php.exe artisan migrate
echo.

echo Running tenant migrations...
D:\DevCenter\xampp\php-8.3.23\php.exe artisan tenants:migrate
echo.

cd scripts\development
echo Migrations completed!
pause
goto MAIN_MENU

:CREATE_DATA
cls
echo ========================================
echo   Creating Sample Data
echo ========================================
echo.

cd ..\..
echo Creating central sample data...
D:\DevCenter\xampp\php-8.3.23\php.exe scripts\data\create_sample_data.php
echo.

echo Creating tenant sample data...
D:\DevCenter\xampp\php-8.3.23\php.exe scripts\data\create_tenant_sample_data.php
echo.

cd scripts\development
echo Sample data created!
pause
goto MAIN_MENU

:SHOW_ACCOUNTS
cls
echo ========================================
echo   Demo Accounts
echo ========================================
echo.
echo SUPER ADMIN:
echo   Email: admin@system.com
echo   Password: password
echo   URL: http://127.0.0.1:8080/super-admin/dashboard
echo.
echo INSTITUTION ADMIN (Tech Institute):
echo   Email: admin@tech-institute.edu
echo   Password: password
echo   URL: http://127.0.0.1:8080/institution-admin/dashboard
echo.
echo INSTITUTION ADMIN (Business College):
echo   Email: admin@business-college.edu
echo   Password: password
echo   URL: http://127.0.0.1:8080/institution-admin/dashboard
echo.
echo GRADUATE:
echo   Email: john.smith@student.edu
echo   Password: password
echo   URL: http://127.0.0.1:8080/graduate/dashboard
echo.
echo EMPLOYER:
echo   Email: techcorp@company.com
echo   Password: password
echo   URL: http://127.0.0.1:8080/employer/dashboard
echo.
pause
goto MAIN_MENU

:SETUP_PATH
cls
echo ========================================
echo   Setup PHP PATH
echo ========================================
echo.
call setup-php-path.bat
goto MAIN_MENU

:OPEN_URLS
cls
echo ========================================
echo   Opening Application URLs
echo ========================================
echo.

echo Opening main application...
start http://127.0.0.1:8080

echo Opening login page...
start http://127.0.0.1:8080/login

echo Opening super admin dashboard...
start http://127.0.0.1:8080/super-admin/dashboard

echo URLs opened in browser!
pause
goto MAIN_MENU

:VIEW_LOGS
cls
echo ========================================
echo   Application Logs
echo ========================================
echo.

cd ..\..
if exist storage\logs\laravel.log (
    echo Latest log entries:
    echo.
    powershell "Get-Content storage\logs\laravel.log -Tail 20"
) else (
    echo No log file found.
)

cd scripts\development
echo.
pause
goto MAIN_MENU

:EXIT
echo.
echo Thank you for using the Development Helper!
echo.
exit /b 0