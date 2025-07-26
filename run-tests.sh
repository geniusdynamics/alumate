#!/bin/bash

# Graduate Tracking System - Comprehensive Test Runner
# This script runs the complete automated testing suite

set -e

echo "🚀 Graduate Tracking System - Comprehensive Test Suite"
echo "======================================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default options
SUITE="all"
COVERAGE=false
REPORT=false
PARALLEL=false
STOP_ON_FAILURE=false
ENVIRONMENT="local"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --suite)
            SUITE="$2"
            shift 2
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --report)
            REPORT=true
            shift
            ;;
        --parallel)
            PARALLEL=true
            shift
            ;;
        --stop-on-failure)
            STOP_ON_FAILURE=true
            shift
            ;;
        --env)
            ENVIRONMENT="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --suite SUITE          Test suite to run (all, unit, integration, feature, e2e, performance, security)"
            echo "  --coverage             Generate coverage report"
            echo "  --report               Generate comprehensive test report"
            echo "  --parallel             Run tests in parallel"
            echo "  --stop-on-failure      Stop on first failure"
            echo "  --env ENVIRONMENT      Test environment (local, ci, staging)"
            echo "  --help                 Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0 --suite unit --coverage"
            echo "  $0 --suite all --report --parallel"
            echo "  $0 --suite security --stop-on-failure"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}Configuration:${NC}"
echo "  Suite: $SUITE"
echo "  Environment: $ENVIRONMENT"
echo "  Coverage: $COVERAGE"
echo "  Report: $REPORT"
echo "  Parallel: $PARALLEL"
echo "  Stop on failure: $STOP_ON_FAILURE"
echo ""

# Check if required tools are available
echo -e "${BLUE}Checking prerequisites...${NC}"

if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP is not installed${NC}"
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Composer is not installed${NC}"
    exit 1
fi

if [ ! -f "vendor/bin/phpunit" ]; then
    echo -e "${RED}❌ PHPUnit is not installed. Run: composer install${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Prerequisites check passed${NC}"
echo ""

# Set environment variables
export APP_ENV=testing
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=graduate_tracking_test
export DB_USERNAME=root
export DB_PASSWORD=

# Prepare test environment
echo -e "${BLUE}Preparing test environment...${NC}"

# Clear caches
php artisan cache:clear --env=testing > /dev/null 2>&1
php artisan config:clear --env=testing > /dev/null 2>&1
php artisan route:clear --env=testing > /dev/null 2>&1
php artisan view:clear --env=testing > /dev/null 2>&1

# Create reports directory
mkdir -p tests/reports

# Run database migrations
php artisan migrate:fresh --env=testing --force > /dev/null 2>&1

echo -e "${GREEN}✅ Test environment prepared${NC}"
echo ""

# Build test command
COMMAND="php artisan test:comprehensive"

if [ "$SUITE" != "all" ]; then
    COMMAND="$COMMAND --suite=$SUITE"
fi

if [ "$COVERAGE" = true ]; then
    COMMAND="$COMMAND --coverage"
fi

if [ "$REPORT" = true ]; then
    COMMAND="$COMMAND --report"
fi

if [ "$PARALLEL" = true ]; then
    COMMAND="$COMMAND --parallel"
fi

if [ "$STOP_ON_FAILURE" = true ]; then
    COMMAND="$COMMAND --stop-on-failure"
fi

# Run tests
echo -e "${BLUE}Running tests...${NC}"
echo "Command: $COMMAND"
echo ""

START_TIME=$(date +%s)

if $COMMAND; then
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    echo ""
    echo -e "${GREEN}✅ Tests completed successfully in ${DURATION}s${NC}"
    
    # Show coverage summary if generated
    if [ "$COVERAGE" = true ] && [ -f "tests/reports/coverage.xml" ]; then
        echo ""
        echo -e "${BLUE}Coverage Summary:${NC}"
        # Parse coverage from XML (simplified)
        if command -v xmllint &> /dev/null; then
            COVERAGE_PERCENT=$(xmllint --xpath "string(//coverage/@percent)" tests/reports/coverage.xml 2>/dev/null || echo "N/A")
            echo "  Overall Coverage: $COVERAGE_PERCENT%"
        fi
    fi
    
    # Show report location if generated
    if [ "$REPORT" = true ] && [ -f "tests/reports/latest_report.json" ]; then
        echo ""
        echo -e "${BLUE}Test Report:${NC}"
        echo "  Report saved to: tests/reports/latest_report.json"
        echo "  HTML report: tests/reports/testdox.html"
    fi
    
    EXIT_CODE=0
else
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    echo ""
    echo -e "${RED}❌ Tests failed after ${DURATION}s${NC}"
    
    # Show failure summary
    if [ -f "tests/reports/junit.xml" ]; then
        echo ""
        echo -e "${YELLOW}Failure Summary:${NC}"
        if command -v xmllint &> /dev/null; then
            FAILURES=$(xmllint --xpath "string(//testsuite/@failures)" tests/reports/junit.xml 2>/dev/null || echo "0")
            ERRORS=$(xmllint --xpath "string(//testsuite/@errors)" tests/reports/junit.xml 2>/dev/null || echo "0")
            echo "  Failures: $FAILURES"
            echo "  Errors: $ERRORS"
        fi
    fi
    
    EXIT_CODE=1
fi

# Cleanup
echo ""
echo -e "${BLUE}Cleaning up...${NC}"

# Clear test data
php artisan migrate:fresh --env=testing --force > /dev/null 2>&1

echo -e "${GREEN}✅ Cleanup completed${NC}"

# Final summary
echo ""
echo "======================================================"
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed successfully!${NC}"
else
    echo -e "${RED}💥 Some tests failed. Check the output above.${NC}"
fi
echo "======================================================"

exit $EXIT_CODE