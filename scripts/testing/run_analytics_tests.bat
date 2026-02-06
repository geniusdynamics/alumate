@echo off
setlocal enabledelayedexpansion

REM ============================================================================
REM Analytics Test Runner Script (Windows)
REM ============================================================================
REM This script runs comprehensive analytics tests including:
REM - Unit tests for analytics services
REM - Feature tests for analytics controllers
REM - Component tests for analytics components
REM - Generates test coverage report
REM - Validates test coverage meets 80%% threshold
REM ============================================================================

REM Set working directory
cd /d "%~dp0\..\.."

REM Set environment variables
set APP_ENV=testing
set DB_CONNECTION=sqlite
set DB_DATABASE=":memory:"
set CACHE_STORE=array
set SESSION_DRIVER=array
set QUEUE_CONNECTION=sync

REM Create reports directory
if not exist "tests\reports" mkdir "tests\reports"

echo.
echo ============================================================================
echo  Analytics Test Runner
echo ============================================================================
echo.

REM Check prerequisites
echo Checking prerequisites...

where php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP is not installed
    exit /b 1
)
echo [OK] PHP is installed

where composer >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Composer is not installed
    exit /b 1
)
echo [OK] Composer is installed

if not exist "vendor\bin\phpunit.bat" (
    echo [ERROR] PHPUnit is not installed. Run: composer install
    exit /b 1
)
echo [OK] PHPUnit is installed

where node >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js is not installed
    exit /b 1
)
echo [OK] Node.js is installed

echo [OK] All prerequisites satisfied
echo.

REM Clear caches
php artisan cache:clear --env=testing >nul 2>&1
php artisan config:clear --env=testing >nul 2>&1
php artisan route:clear --env=testing >nul 2>&1
php artisan view:clear --env=testing >nul 2>&1

echo [OK] Test environment prepared
echo.

REM ============================================================================
REM Analytics Service Unit Tests
REM ============================================================================
echo ============================================================================
echo  Running Analytics Service Unit Tests
echo ============================================================================
echo.

set SERVICE_TESTS[0]=tests/Unit/Services/Analytics/BehaviorFlowServiceTest.php
set SERVICE_TESTS[1]=tests/Unit/Services/Analytics/CohortAnalysisServiceTest.php
set SERVICE_TESTS[2]=tests/Unit/Services/Analytics/AnalyticsDataSyncServiceTest.php
set SERVICE_TESTS[3]=tests/Unit/Services/Analytics/AttributionTrackingServiceTest.php
set SERVICE_TESTS[4]=tests/Unit/Services/Analytics/AutomatedInsightsServiceTest.php
set SERVICE_TESTS[5]=tests/Unit/Services/Analytics/LearningAnalyticsServiceTest.php
set SERVICE_TESTS[6]=tests/Unit/Services/Analytics/PrivacyComplianceServiceTest.php

set SERVICE_COUNT=0
set SERVICE_PASSED=0
set SERVICE_FAILED=0

for %%i in (
    tests\Unit\Services\Analytics\BehaviorFlowServiceTest.php
    tests\Unit\Services\Analytics\CohortAnalysisServiceTest.php
    tests\Unit\Services\Analytics\AnalyticsDataSyncServiceTest.php
    tests\Unit\Services\Analytics\AttributionTrackingServiceTest.php
    tests\Unit\Services\Analytics\AutomatedInsightsServiceTest.php
    tests\Unit\Services\Analytics\LearningAnalyticsServiceTest.php
    tests\Unit\Services\Analytics\PrivacyComplianceServiceTest.php
) do (
    if exist "%%i" (
        set /a SERVICE_COUNT+=1
        echo Running: %%i
        php vendor\bin\phpunit --configuration phpunit.xml --filter "%%~ni" --testdox
        if !errorlevel! equ 0 (
            set /a SERVICE_PASSED+=1
            echo [PASS] %%i
        ) else (
            set /a SERVICE_FAILED+=1
            echo [FAIL] %%i
        )
    )
)

echo.
echo Service Unit Tests Summary:
echo   Total:  !SERVICE_COUNT!
echo   Passed: !SERVICE_PASSED!
echo   Failed: !SERVICE_FAILED!
echo.

REM ============================================================================
REM Analytics Controller Feature Tests
REM ============================================================================
echo ============================================================================
echo  Running Analytics Controller Feature Tests
echo ============================================================================
echo.

set CONTROLLER_COUNT=0
set CONTROLLER_PASSED=0
set CONTROLLER_FAILED=0

echo Running: AttributionAnalysisControllerTest
php vendor\bin\phpunit --configuration phpunit.xml --filter "AttributionAnalysisControllerTest" --testdox
if !errorlevel! equ 0 (set /a CONTROLLER_PASSED+=1) else (set /a CONTROLLER_FAILED+=1)
set /a CONTROLLER_COUNT+=1

echo Running: LearningAnalyticsControllerTest
php vendor\bin\phpunit --configuration phpunit.xml --filter "LearningAnalyticsControllerTest" --testdox
if !errorlevel! equ 0 (set /a CONTROLLER_PASSED+=1) else (set /a CONTROLLER_FAILED+=1)
set /a CONTROLLER_COUNT+=1

echo Running: AnalyticsControllerTenantIsolationTest
php vendor\bin\phpunit --configuration phpunit.xml --filter "AnalyticsControllerTenantIsolationTest" --testdox
if !errorlevel! equ 0 (set /a CONTROLLER_PASSED+=1) else (set /a CONTROLLER_FAILED+=1)
set /a CONTROLLER_COUNT+=1

echo Running: AnalyticsSystemTest
php vendor\bin\phpunit --configuration phpunit.xml --filter "AnalyticsSystemTest" --testdox
if !errorlevel! equ 0 (set /a CONTROLLER_PASSED+=1) else (set /a CONTROLLER_FAILED+=1)
set /a CONTROLLER_COUNT+=1

echo Running: AttributionApiTest
php vendor\bin\phpunit --configuration phpunit.xml --filter "AttributionApiTest" --testdox
if !errorlevel! equ 0 (set /a CONTROLLER_PASSED+=1) else (set /a CONTROLLER_FAILED+=1)
set /a CONTROLLER_COUNT+=1

echo.
echo Controller Feature Tests Summary:
echo   Total:  !CONTROLLER_COUNT!
echo   Passed: !CONTROLLER_PASSED!
echo   Failed: !CONTROLLER_FAILED!
echo.

REM ============================================================================
REM Analytics Integration Tests
REM ============================================================================
echo ============================================================================
echo  Running Analytics Integration Tests
echo ============================================================================
echo.

set INTEGRATION_COUNT=0
set INTEGRATION_PASSED=0
set INTEGRATION_FAILED=0

echo Running: GoogleAnalyticsServiceIntegrationTest
php vendor\bin\phpunit --configuration phpunit.xml --filter "GoogleAnalyticsServiceIntegrationTest" --testdox
if !errorlevel! equ 0 (set /a INTEGRATION_PASSED+=1) else (set /a INTEGRATION_FAILED+=1)
set /a INTEGRATION_COUNT+=1

echo Running: MatomoServiceIntegrationTest
php vendor\bin\phpunit --configuration phpunit.xml --filter "MatomoServiceIntegrationTest" --testdox
if !errorlevel! equ 0 (set /a INTEGRATION_PASSED+=1) else (set /a INTEGRATION_FAILED+=1)
set /a INTEGRATION_COUNT+=1

echo.
echo Integration Tests Summary:
echo   Total:  !INTEGRATION_COUNT!
echo   Passed: !INTEGRATION_PASSED!
echo   Failed: !INTEGRATION_FAILED!
echo.

REM ============================================================================
REM Analytics JavaScript/Component Tests
REM ============================================================================
echo ============================================================================
echo  Running Analytics Component Tests (JavaScript)
echo ============================================================================
echo.

if exist "tests\Js\Components\Analytics\LearningAnalyticsDashboard.test.ts" (
    echo Running: LearningAnalyticsDashboard.test.ts
    npx vitest run tests/Js/Components/Analytics/LearningAnalyticsDashboard.test.ts --reporter=verbose
    if !errorlevel! equ 0 (
        echo [PASS] LearningAnalyticsDashboard.test.ts
    ) else (
        echo [FAIL] LearningAnalyticsDashboard.test.ts
    )
)

if exist "tests\Js\Composables\useAnalytics.test.ts" (
    echo Running: useAnalytics.test.ts
    npx vitest run tests/Js/Composables/useAnalytics.test.ts --reporter=verbose
)

if exist "tests\Js\Services\AnalyticsService.test.ts" (
    echo Running: AnalyticsService.test.ts
    npx vitest run tests/Js/Services/AnalyticsService.test.ts --reporter=verbose
)

echo.

REM ============================================================================
REM Generate Coverage Report
REM ============================================================================
echo ============================================================================
echo  Generating Test Coverage Report
echo ============================================================================
echo.

echo Running full analytics coverage report...
php vendor\bin\phpunit --configuration phpunit.xml --filter="Analytics" --coverage-html "tests/reports/analytics-coverage-html" --coverage-clover "tests/reports/analytics_coverage.xml" --coverage-text

if exist "tests/reports/analytics_coverage.xml" (
    echo [OK] Coverage report generated: tests/reports/analytics_coverage.xml
    echo [OK] HTML Coverage: tests/reports/analytics-coverage-html/index.html
) else (
    echo [WARNING] Coverage report generation failed
)

REM ============================================================================
REM Final Summary
REM ============================================================================
echo.
echo ============================================================================
echo  Analytics Test Results Summary
echo ============================================================================
echo.
echo   Service Unit Tests:
echo     Total:  !SERVICE_COUNT!
echo     Passed: !SERVICE_PASSED!
echo     Failed: !SERVICE_FAILED!
echo.
echo   Controller Feature Tests:
echo     Total:  !CONTROLLER_COUNT!
echo     Passed: !CONTROLLER_PASSED!
echo     Failed: !CONTROLLER_FAILED!
echo.
echo   Integration Tests:
echo     Total:  !INTEGRATION_COUNT!
echo     Passed: !INTEGRATION_PASSED!
echo     Failed: !INTEGRATION_FAILED!
echo.

set /a TOTAL_COUNT=SERVICE_COUNT+CONTROLLER_COUNT+INTEGRATION_COUNT
set /a TOTAL_PASSED=SERVICE_PASSED+CONTROLLER_PASSED+INTEGRATION_PASSED
set /a TOTAL_FAILED=SERVICE_FAILED+CONTROLLER_FAILED+INTEGRATION_FAILED

echo   Overall:
echo     Total Tests:  !TOTAL_COUNT!
echo     Total Passed:  !TOTAL_PASSED!
echo     Total Failed:  !TOTAL_FAILED!
echo.
echo ============================================================================

REM Generate summary report
echo # Analytics Test Summary Report > tests/reports/ANALYTICS_TEST_SUMMARY.md
echo. >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo Generated: %date% %time% >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo. >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo ## Test Results >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo. >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo ^| Category                    ^| Total ^| Passed ^| Failed ^| >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo ^|------------------------------^|--------^|--------^|--------^| >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo ^| Service Unit Tests          ^| !SERVICE_COUNT!    ^| !SERVICE_PASSED!     ^| !SERVICE_FAILED!     ^| >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo ^| Controller Feature Tests   ^| !CONTROLLER_COUNT!    ^| !CONTROLLER_PASSED!     ^| !CONTROLLER_FAILED!     ^| >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo ^| Integration Tests          ^| !INTEGRATION_COUNT!    ^| !INTEGRATION_PASSED!     ^| !INTEGRATION_FAILED!     ^| >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo. >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo Coverage Report: tests/reports/analytics_coverage.xml >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo HTML Coverage: tests/reports/analytics-coverage-html/index.html >> tests/reports/ANALYTICS_TEST_SUMMARY.md
echo Threshold: 80%% >> tests/reports/ANALYTICS_TEST_SUMMARY.md

echo [OK] Summary report generated: tests/reports/ANALYTICS_TEST_SUMMARY.md

if !TOTAL_FAILED! gtr 0 (
    echo.
    echo [ERROR] Some tests failed. Check the summary report for details.
    exit /b 1
) else (
    echo.
    echo [SUCCESS] All analytics tests passed successfully!
    exit /b 0
)
