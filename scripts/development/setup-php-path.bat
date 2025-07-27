@echo off
echo ========================================
echo   PHP PATH Setup Helper
echo ========================================
echo.

set PHP_PATH=D:\DevCenter\xampp\php-8.3.23

echo Current PHP path: %PHP_PATH%
echo.

echo Testing PHP installation...
"%PHP_PATH%\php.exe" --version
if %errorlevel% neq 0 (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please check the path and try again.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Adding PHP to System PATH
echo ========================================
echo.

echo This will add PHP to your system PATH permanently.
echo You may need to restart your command prompt after this.
echo.
set /p confirm="Continue? (y/n): "

if /i "%confirm%" neq "y" (
    echo Operation cancelled.
    pause
    exit /b 0
)

echo.
echo Adding %PHP_PATH% to system PATH...

REM Add to system PATH using setx
setx PATH "%PATH%;%PHP_PATH%" /M

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo   SUCCESS!
    echo ========================================
    echo.
    echo PHP has been added to your system PATH.
    echo.
    echo IMPORTANT: You need to:
    echo 1. Close this command prompt
    echo 2. Open a new command prompt
    echo 3. Test by typing: php --version
    echo.
    echo After that, you can use 'php' directly instead of the full path.
) else (
    echo.
    echo ERROR: Failed to add PHP to PATH.
    echo You may need to run this as Administrator.
    echo.
    echo Manual steps:
    echo 1. Open System Properties ^> Environment Variables
    echo 2. Edit the System PATH variable
    echo 3. Add: %PHP_PATH%
)

echo.
pause