@echo off
setlocal enabledelayedexpansion

REM Graduate Tracking System - Comprehensive Test Runner (Windows)
REM This script runs the complete automated testing suite

echo 🚀 Graduate Tracking System - Comprehensive Test Suite
echo ======================================================

REM Default options
set SUITE=all
set COVERAGE=false
set REPORT=false
set PARALLEL=false
set STOP_ON_FAILURE=false
set ENVIRONMENT=local

REM Parse command line arguments
:parse_args
if "%~1"=="" goto :args_parsed
if "%~1"=="--suite" (
    set SUITE=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--coverage" (
    set COVERAGE=true
    shift
    goto :parse_args
)
if "%~1"=="--report" (
    set REPORT=true
    shift
    goto :parse_args
)
if "%~1"=="--parallel" (
    set PARALLEL=true
    shift
    goto :parse_args
)
if "%~1"=="--stop-on-failure" (
    set STOP_ON_FAILURE=true
    shift
    goto :parse_args
)
if "%~1"=="--env" (
    set ENVIRONMENT=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--help" (
    echo Usage: %0 [OPTIONS]
    echo.
    echo Options:
    echo   --suite SUITE          Test suite to run (all, unit, integration, feature, e2e, performance, security)
    echo   --coverage             Generate coverage report
    echo   --report               Generate comprehensive test report
    echo   --parallel             Run tests in parallel
    echo   --stop-on-failure      Stop on first failure
    echo   --env ENVIRONMENT      Test environment (local, ci, staging)
    echo   --help                 Show this help message
    echo.
    echo Examples:
    echo   %0 --suite unit --coverage
    echo   %0 --suite all --report --parallel
    echo   %0 --suite security --stop-on-failure
    exit /b 0
)
echo Unknown option: %~1
exit /b 1

:args_parsed

echo Configuration:
echo   Suite: !SUITE!
echo   Environment: !ENVIRONMENT!
echo   Coverage: !COVERAGE!
echo   Report: !REPORT!
echo   Parallel: !PARALLEL!
echo   Stop on failure: !STOP_ON_FAILURE!
echo.

REM Check if required tools are available
echo Checking prerequisites...

where php >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP is not installed
    exit /b 1
)

where composer >nul 2>&1
if errorlevel 1 (
    echo ❌ Composer is not installed
    exit /b 1
)

if not exist "vendor\bin\phpunit.bat" (
    echo ❌ PHPUnit is not installed. Run: composer install
    exit /b 1
)

echo ✅ Prerequisites check passed
echo.

REM Set environment variables
set APP_ENV=testing
set DB_CONNECTION=mysql
set DB_HOST=127.0.0.1
set DB_PORT=3306
set DB_DATABASE=graduate_tracking_test
set DB_USERNAME=root
set DB_PASSWORD=

REM Prepare test environment
echo Preparing test environment...

REM Clear caches
php artisan cache:clear --env=testing >nul 2>&1
php artisan config:clear --env=testing >nul 2>&1
php artisan route:clear --env=testing >nul 2>&1
php artisan view:clear --env=testing >nul 2>&1

REM Create reports directory
if not exist "tests\reports" mkdir "tests\reports"

REM Run database migrations
php artisan migrate:fresh --env=testing --force >nul 2>&1

echo ✅ Test environment prepared
echo.

REM Build test command
set COMMAND=php artisan test:comprehensive

if not "!SUITE!"=="all" (
    set COMMAND=!COMMAND! --suite=!SUITE!
)

if "!COVERAGE!"=="true" (
    set COMMAND=!COMMAND! --coverage
)

if "!REPORT!"=="true" (
    set COMMAND=!COMMAND! --report
)

if "!PARALLEL!"=="true" (
    set COMMAND=!COMMAND! --parallel
)

if "!STOP_ON_FAILURE!"=="true" (
    set COMMAND=!COMMAND! --stop-on-failure
)

REM Run tests
echo Running tests...
echo Command: !COMMAND!
echo.

REM Record start time
for /f "tokens=1-4 delims=:.," %%a in ("%time%") do (
    set /a "start=(((%%a*60)+1%%b %% 100)*60+1%%c %% 100)*100+1%%d %% 100"
)

call !COMMAND!
set TEST_EXIT_CODE=!errorlevel!

REM Record end time
for /f "tokens=1-4 delims=:.," %%a in ("%time%") do (
    set /a "end=(((%%a*60)+1%%b %% 100)*60+1%%c %% 100)*100+1%%d %% 100"
)

set /a elapsed=end-start
set /a hours=elapsed/(60*60*100)
set /a mins=(elapsed-hours*60*60*100)/(60*100)
set /a secs=(elapsed-hours*60*60*100-mins*60*100)/100

if !TEST_EXIT_CODE! equ 0 (
    echo.
    echo ✅ Tests completed successfully in %hours%:%mins%:%secs%
    
    REM Show coverage summary if generated
    if "!COVERAGE!"=="true" (
        if exist "tests\reports\coverage.xml" (
            echo.
            echo Coverage Summary:
            echo   Coverage report generated at: tests\reports\coverage\index.html
        )
    )
    
    REM Show report location if generated
    if "!REPORT!"=="true" (
        if exist "tests\reports\latest_report.json" (
            echo.
            echo Test Report:
            echo   Report saved to: tests\reports\latest_report.json
            echo   HTML report: tests\reports\testdox.html
        )
    )
    
    set EXIT_CODE=0
) else (
    echo.
    echo ❌ Tests failed after %hours%:%mins%:%secs%
    
    REM Show failure summary
    if exist "tests\reports\junit.xml" (
        echo.
        echo Failure Summary:
        echo   Check tests\reports\junit.xml for detailed failure information
    )
    
    set EXIT_CODE=1
)

REM Cleanup
echo.
echo Cleaning up...

REM Clear test data
php artisan migrate:fresh --env=testing --force >nul 2>&1

echo ✅ Cleanup completed

REM Final summary
echo.
echo ======================================================
if !EXIT_CODE! equ 0 (
    echo 🎉 All tests passed successfully!
) else (
    echo 💥 Some tests failed. Check the output above.
)
echo ======================================================

exit /b !EXIT_CODE!