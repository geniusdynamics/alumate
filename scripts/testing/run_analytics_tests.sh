#!/bin/bash

# ============================================================================
# Analytics Test Runner Script
# ============================================================================
# This script runs comprehensive analytics tests including:
# - Unit tests for analytics services
# - Feature tests for analytics controllers
# - Component tests for analytics components
# - Generates test coverage report
# - Validates test coverage meets 80% threshold
#
# Usage: ./scripts/testing/run_analytics_tests.sh [OPTIONS]
#
# Options:
#   --coverage          Generate coverage report
#   --services          Run only service unit tests
#   --controllers       Run only controller feature tests
#   --components        Run only component tests
#   --parallel          Run tests in parallel
#   --verbose           Show detailed test output
#   --filter=PATTERN    Filter tests by pattern
#   --help              Show this help message
#
# Examples:
#   ./scripts/testing/run_analytics_tests.sh --coverage
#   ./scripts/testing/run_analytics_tests.sh --services --verbose
#   ./scripts/testing/run_analytics_tests.sh --components
# ============================================================================

set -euo pipefail

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$(dirname "${SCRIPT_DIR}/../..")" && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default options
RUN_COVERAGE=false
RUN_SERVICES=false
RUN_CONTROLLERS=false
RUN_COMPONENTS=false
RUN_PARALLEL=false
VERBOSE=false
FILTER_PATTERN=""

# Test coverage threshold
COVERAGE_THRESHOLD=80

# Output files
COVERAGE_REPORT="${PROJECT_ROOT}/tests/reports/analytics_coverage.xml"
HTML_COVERAGE_DIR="${PROJECT_ROOT}/tests/reports/analytics-coverage-html"
TESTDOX_REPORT="${PROJECT_ROOT}/tests/reports/analytics_testdox.txt"
JUNIT_REPORT="${PROJECT_ROOT}/tests/reports/analytics_junit.xml"

# ============================================================================
# Functions
# ============================================================================

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

show_help() {
    cat << EOF
Analytics Test Runner Script

Usage: $(basename "$0") [OPTIONS]

Options:
  --coverage          Generate coverage report
  --services          Run only service unit tests
  --controllers       Run only controller feature tests
  --components        Run only component tests
  --parallel          Run tests in parallel
  --verbose           Show detailed test output
  --filter=PATTERN    Filter tests by pattern
  --help              Show this help message

Examples:
  $(basename "$0") --coverage
  $(basename "$0") --services --verbose
  $(basename "$0") --components
  $(basename "$0") --controllers --filter="TenantIsolation"
EOF
}

# Change to project root
cd "${PROJECT_ROOT}"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --coverage)
            RUN_COVERAGE=true
            shift
            ;;
        --services)
            RUN_SERVICES=true
            shift
            ;;
        --controllers)
            RUN_CONTROLLERS=true
            shift
            ;;
        --components)
            RUN_COMPONENTS=true
            shift
            ;;
        --parallel)
            RUN_PARALLEL=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --filter=*)
            FILTER_PATTERN="${1#*=}"
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# If no specific test type is selected, run all
if [[ "$RUN_SERVICES" == false && "$RUN_CONTROLLERS" == false && "$RUN_COMPONENTS" == false ]]; then
    RUN_SERVICES=true
    RUN_CONTROLLERS=true
    RUN_COMPONENTS=true
fi

# ============================================================================
# Prerequisites Check
# ============================================================================

print_header "Checking Prerequisites"

# Check PHP
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed"
    exit 1
fi
print_success "PHP is installed: $(php -v | head -n 1)"

# Check Composer
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed"
    exit 1
fi
print_success "Composer is installed: $(composer --version | head -n 1)"

# Check PHPUnit
if [ ! -f "${PROJECT_ROOT}/vendor/bin/phpunit" ]; then
    print_error "PHPUnit is not installed. Run: composer install"
    exit 1
fi
print_success "PHPUnit is installed"

# Check Node.js
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed"
    exit 1
fi
print_success "Node.js is installed: $(node -v)"

# Check npm
if ! command -v npm &> /dev/null; then
    print_error "npm is not installed"
    exit 1
fi
print_success "npm is installed: $(npm -v)"

# ============================================================================
# Environment Setup
# ============================================================================

print_header "Preparing Test Environment"

# Set environment variables
export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=":memory:"
export CACHE_STORE=array
export SESSION_DRIVER=array
export QUEUE_CONNECTION=sync

# Create reports directory if it doesn't exist
mkdir -p "${PROJECT_ROOT}/tests/reports"
mkdir -p "${HTML_COVERAGE_DIR}"

# Clear Laravel caches
php artisan cache:clear --env=testing &> /dev/null || true
php artisan config:clear --env=testing &> /dev/null || true
php artisan route:clear --env=testing &> /dev/null || true
php artisan view:clear --env=testing &> /dev/null || true

print_success "Test environment prepared"

# ============================================================================
# Backend Analytics Unit Tests (Services)
# ============================================================================

if [[ "$RUN_SERVICES" == true ]]; then
    print_header "Running Analytics Service Unit Tests"

    SERVICE_TESTS=(
        "tests/Unit/Services/Analytics/BehaviorFlowServiceTest.php"
        "tests/Unit/Services/Analytics/CohortAnalysisServiceTest.php"
        "tests/Unit/Services/Analytics/AnalyticsDataSyncServiceTest.php"
        "tests/Unit/Services/Analytics/AttributionTrackingServiceTest.php"
        "tests/Unit/Services/Analytics/AutomatedInsightsServiceTest.php"
        "tests/Unit/Services/Analytics/LearningAnalyticsServiceTest.php"
        "tests/Unit/Services/Analytics/PrivacyComplianceServiceTest.php"
    )

    SERVICE_TEST_COUNT=0
    SERVICE_PASSED=0
    SERVICE_FAILED=0

    for test_file in "${SERVICE_TESTS[@]}"; do
        if [ -f "${PROJECT_ROOT}/${test_file}" ]; then
            ((SERVICE_TEST_COUNT++))
            
            if [[ "$VERBOSE" == true ]]; then
                echo -e "\n${BLUE}Running:${NC} ${test_file}"
            fi
            
            # Run the test with coverage if requested
            if [[ "$RUN_COVERAGE" == true ]]; then
                php vendor/bin/phpunit --configuration phpunit.xml \
                    --testsuite Unit \
                    --filter "$(basename "$test_file" .php)" \
                    --coverage-html "${HTML_COVERAGE_DIR}/services" \
                    --coverage-clover "${COVERAGE_REPORT}" \
                    --log-junit "${JUNIT_REPORT}" \
                    --testdox \
                    ${VERBOSE:+--verbose} \
                    2>&1 | tee "${PROJECT_ROOT}/tests/reports/services_output.log"
            else
                php vendor/bin/phpunit --configuration phpunit.xml \
                    --testsuite Unit \
                    --filter "$(basename "$test_file" .php)" \
                    ${VERBOSE:+--verbose} \
                    2>&1 | tee "${PROJECT_ROOT}/tests/reports/services_output.log"
            fi
            
            if [ ${PIPESTATUS[0]} -eq 0 ]; then
                ((SERVICE_PASSED++))
                print_success "$(basename "$test_file") passed"
            else
                ((SERVICE_FAILED++))
                print_error "$(basename "$test_file") failed"
            fi
        fi
    done

    echo -e "\n${BLUE}Service Unit Tests Summary:${NC}"
    echo -e "  Total: ${SERVICE_TEST_COUNT}"
    echo -e "  Passed: ${GREEN}${SERVICE_PASSED}${NC}"
    if [ $SERVICE_FAILED -gt 0 ]; then
        echo -e "  Failed: ${RED}${SERVICE_FAILED}${NC}"
    else
        echo -e "  Failed: ${SERVICE_FAILED}"
    fi
fi

# ============================================================================
# Backend Analytics Feature Tests (Controllers)
# ============================================================================

if [[ "$RUN_CONTROLLERS" == true ]]; then
    print_header "Running Analytics Controller Feature Tests"

    CONTROLLER_TESTS=(
        "tests/Feature/Analytics/AttributionAnalysisControllerTest.php"
        "tests/Feature/Analytics/LearningAnalyticsControllerTest.php"
        "tests/Feature/AnalyticsControllerTenantIsolationTest.php"
        "tests/Feature/AnalyticsSystemTest.php"
        "tests/Feature/AttributionApiTest.php"
        "tests/Feature/CustomEventControllerTest.php"
        "tests/Integration/AnalyticsApiIntegrationTest.php"
        "tests/Integration/AnalyticsCrossModuleIntegrationTest.php"
        "tests/Integration/AnalyticsJobFlowIntegrationTest.php"
        "tests/Integration/AnalyticsWebSocketIntegrationTest.php"
        "tests/Integration/CrmAnalyticsIntegrationTest.php"
    )

    CONTROLLER_TEST_COUNT=0
    CONTROLLER_PASSED=0
    CONTROLLER_FAILED=0

    for test_file in "${CONTROLLER_TESTS[@]}"; do
        if [ -f "${PROJECT_ROOT}/${test_file}" ]; then
            ((CONTROLLER_TEST_COUNT++))
            
            if [[ "$VERBOSE" == true ]]; then
                echo -e "\n${BLUE}Running:${NC} ${test_file}"
            fi
            
            # Run the test
            php vendor/bin/phpunit --configuration phpunit.xml \
                --testsuite Feature \
                --filter "$(basename "$test_file" .php)" \
                ${VERBOSE:+--verbose} \
                2>&1 | tee "${PROJECT_ROOT}/tests/reports/controllers_output.log"
            
            if [ ${PIPESTATUS[0]} -eq 0 ]; then
                ((CONTROLLER_PASSED++))
                print_success "$(basename "$test_file") passed"
            else
                ((CONTROLLER_FAILED++))
                print_error "$(basename "$test_file") failed"
            fi
        fi
    done

    # Also run tests matching analytics patterns
    print_info "Running additional analytics feature tests..."
    
    php vendor/bin/phpunit --configuration phpunit.xml \
        --testsuite Feature \
        --filter="Analytics" \
        ${VERBOSE:+--verbose} \
        2>&1 | tee "${PROJECT_ROOT}/tests/reports/analytics_feature.log"
    
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        print_success "Analytics feature tests passed"
    else
        print_warning "Some analytics feature tests failed"
    fi

    echo -e "\n${BLUE}Controller Feature Tests Summary:${NC}"
    echo -e "  Total: ${CONTROLLER_TEST_COUNT}"
    echo -e "  Passed: ${GREEN}${CONTROLLER_PASSED}${NC}"
    if [ $CONTROLLER_FAILED -gt 0 ]; then
        echo -e "  Failed: ${RED}${CONTROLLER_FAILED}${NC}"
    else
        echo -e "  Failed: ${CONTROLLER_FAILED}"
    fi
fi

# ============================================================================
# Frontend Analytics Component Tests
# ============================================================================

if [[ "$RUN_COMPONENTS" == true ]]; then
    print_header "Running Analytics Component Tests"

    COMPONENT_TESTS=(
        "tests/Js/Components/Analytics/LearningAnalyticsDashboard.test.ts"
        "tests/Js/Composables/useAnalytics.test.ts"
        "tests/Js/Services/AnalyticsService.test.ts"
    )

    COMPONENT_TEST_COUNT=0
    COMPONENT_PASSED=0
    COMPONENT_FAILED=0

    # Check if vitest is available
    if ! command -v vitest &> /dev/null; then
        if [ -f "${PROJECT_ROOT}/node_modules/.bin/vitest" ]; then
            VITE_CMD="${PROJECT_ROOT}/node_modules/.bin/vitest"
        else
            print_warning "Vitest not found, running npm install first..."
            npm install --prefix "${PROJECT_ROOT}" &> /dev/null
            VITE_CMD="${PROJECT_ROOT}/node_modules/.bin/vitest"
        fi
    else
        VITE_CMD="vitest"
    fi

    for test_file in "${COMPONENT_TESTS[@]}"; do
        if [ -f "${PROJECT_ROOT}/${test_file}" ]; then
            ((COMPONENT_TEST_COUNT++))
            
            if [[ "$VERBOSE" == true ]]; then
                echo -e "\n${BLUE}Running:${NC} ${test_file}"
            fi
            
            # Run the test
            ${VITE_CMD} run \
                "${PROJECT_ROOT}/${test_file}" \
                --reporter=verbose \
                ${RUN_COVERAGE:+--coverage} \
                2>&1 | tee "${PROJECT_ROOT}/tests/reports/components_output.log"
            
            if [ ${PIPESTATUS[0]} -eq 0 ]; then
                ((COMPONENT_PASSED++))
                print_success "$(basename "$test_file") passed"
            else
                ((COMPONENT_FAILED++))
                print_error "$(basename "$test_file") failed"
            fi
        fi
    done

    # Run all analytics-related JavaScript tests
    print_info "Running all JavaScript analytics tests..."
    
    ${VITE_CMD} run \
        "${PROJECT_ROOT}/tests/Js" \
        --filter="Analytics|analytics" \
        --reporter=verbose \
        2>&1 | tee "${PROJECT_ROOT}/tests/reports/js_analytics.log"

    echo -e "\n${BLUE}Component Tests Summary:${NC}"
    echo -e "  Total: ${COMPONENT_TEST_COUNT}"
    echo -e "  Passed: ${GREEN}${COMPONENT_PASSED}${NC}"
    if [ $COMPONENT_FAILED -gt 0 ]; then
        echo -e "  Failed: ${RED}${COMPONENT_FAILED}${NC}"
    else
        echo -e "  Failed: ${COMPONENT_FAILED}"
    fi
fi

# ============================================================================
# Integration Services Tests
# ============================================================================

print_header "Running Analytics Integration Tests"

INTEGRATION_TESTS=(
    "tests/Integration/Services/Analytics/GoogleAnalyticsServiceIntegrationTest.php"
    "tests/Integration/Services/Analytics/MatomoServiceIntegrationTest.php"
)

INTEGRATION_TEST_COUNT=0
INTEGRATION_PASSED=0
INTEGRATION_FAILED=0

for test_file in "${INTEGRATION_TESTS[@]}"; do
    if [ -f "${PROJECT_ROOT}/${test_file}" ]; then
        ((INTEGRATION_TEST_COUNT++))
        
        if [[ "$VERBOSE" == true ]]; then
            echo -e "\n${BLUE}Running:${NC} ${test_file}"
        fi
        
        php vendor/bin/phpunit --configuration phpunit.xml \
            --testsuite Integration \
            --filter "$(basename "$test_file" .php)" \
            ${VERBOSE:+--verbose} \
            2>&1 | tee "${PROJECT_ROOT}/tests/reports/integration_output.log"
        
        if [ ${PIPESTATUS[0]} -eq 0 ]; then
            ((INTEGRATION_PASSED++))
            print_success "$(basename "$test_file") passed"
        else
            ((INTEGRATION_FAILED++))
            print_error "$(basename "$test_file") failed"
        fi
    fi
done

echo -e "\n${BLUE}Integration Tests Summary:${NC}"
echo -e "  Total: ${INTEGRATION_TEST_COUNT}"
echo -e "  Passed: ${GREEN}${INTEGRATION_PASSED}${NC}"
if [ $INTEGRATION_FAILED -gt 0 ]; then
    echo -e "  Failed: ${RED}${INTEGRATION_FAILED}${NC}"
else
    echo -e "  Failed: ${INTEGRATION_FAILED}"
fi

# ============================================================================
# Generate Coverage Report
# ============================================================================

if [[ "$RUN_COVERAGE" == true ]]; then
    print_header "Generating Test Coverage Report"
    
    # Run full analytics coverage report
    php vendor/bin/phpunit --configuration phpunit.xml \
        --filter="Analytics" \
        --coverage-clover "${COVERAGE_REPORT}" \
        --coverage-html "${HTML_COVERAGE_DIR}" \
        --coverage-text \
        2>&1 | tee "${PROJECT_ROOT}/tests/reports/coverage_output.log"
    
    if [ -f "${COVERAGE_REPORT}" ]; then
        print_success "Coverage report generated: ${COVERAGE_REPORT}"
        
        # Extract coverage percentage from clover report
        if command -v xmllint &> /dev/null; then
            COVERAGE_PERCENT=$(xmllint --xpath "string(//coverage/project/metrics/@coveredRatio)" "${COVERAGE_REPORT}" 2>/dev/null || echo "0")
            COVERAGE_PERCENT=$(echo "${COVERAGE_PERCENT}" | awk '{printf "%.2f", $1 * 100}')
            echo -e "\n${BLUE}Coverage Report:${NC}"
            echo -e "  Coverage: ${COVERAGE_PERCENT}%"
            echo -e "  Threshold: ${COVERAGE_THRESHOLD}%"
            echo -e "  Report: ${COVERAGE_REPORT}"
            echo -e "  HTML: ${HTML_COVERAGE_DIR}/index.html"
            
            # Check if coverage meets threshold
            COVERAGE_CHECK=$(echo "${COVERAGE_PERCENT} >= ${COVERAGE_THRESHOLD}" | bc -l)
            if [ "${COVERAGE_CHECK}" == "1" ]; then
                print_success "Coverage threshold met (${COVERAGE_PERCENT}% >= ${COVERAGE_THRESHOLD}%)"
            else
                print_warning "Coverage threshold not met (${COVERAGE_PERCENT}% < ${COVERAGE_THRESHOLD}%)"
            fi
        else
            print_info "xmllint not available, manual coverage review required"
        fi
    else
        print_error "Coverage report generation failed"
    fi
fi

# ============================================================================
# Final Summary
# ============================================================================

print_header "Analytics Tests - Final Summary"

TOTAL_TESTS=$((SERVICE_TEST_COUNT + CONTROLLER_TEST_COUNT + COMPONENT_TEST_COUNT + INTEGRATION_TEST_COUNT))
TOTAL_PASSED=$((SERVICE_PASSED + CONTROLLER_PASSED + COMPONENT_PASSED + INTEGRATION_PASSED))
TOTAL_FAILED=$((SERVICE_FAILED + CONTROLLER_FAILED + COMPONENT_FAILED + INTEGRATION_FAILED))

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Analytics Test Results${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${BLUE}Service Unit Tests:${NC}"
echo -e "    Total:  ${SERVICE_TEST_COUNT}"
echo -e "    Passed: ${GREEN}${SERVICE_PASSED}${NC}"
echo -e "    Failed: $([ $SERVICE_FAILED -gt 0 ] && echo -e "${RED}${SERVICE_FAILED}${NC}" || echo $SERVICE_FAILED)"
echo ""
echo -e "  ${BLUE}Controller Feature Tests:${NC}"
echo -e "    Total:  ${CONTROLLER_TEST_COUNT}"
echo -e "    Passed: ${GREEN}${CONTROLLER_PASSED}${NC}"
echo -e "    Failed: $([ $CONTROLLER_FAILED -gt 0 ] && echo -e "${RED}${CONTROLLER_FAILED}${NC}" || echo $CONTROLLER_FAILED)"
echo ""
echo -e "  ${BLUE}Component Tests:${NC}"
echo -e "    Total:  ${COMPONENT_TEST_COUNT}"
echo -e "    Passed: ${GREEN}${COMPONENT_PASSED}${NC}"
echo -e "    Failed: $([ $COMPONENT_FAILED -gt 0 ] && echo -e "${RED}${COMPONENT_FAILED}${NC}" || echo $COMPONENT_FAILED)"
echo ""
echo -e "  ${BLUE}Integration Tests:${NC}"
echo -e "    Total:  ${INTEGRATION_TEST_COUNT}"
echo -e "    Passed: ${GREEN}${INTEGRATION_PASSED}${NC}"
echo -e "    Failed: $([ $INTEGRATION_FAILED -gt 0 ] && echo -e "${RED}${INTEGRATION_FAILED}${NC}" || echo $INTEGRATION_FAILED)"
echo ""
echo -e "  ${BLUE}Overall:${NC}"
echo -e "    Total Tests:  ${TOTAL_TESTS}"
echo -e "    Total Passed: ${GREEN}${TOTAL_PASSED}${NC}"
echo -e "    Total Failed: $([ $TOTAL_FAILED -gt 0 ] && echo -e "${RED}${TOTAL_FAILED}${NC}" || echo $TOTAL_FAILED)"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"

# Generate summary report
SUMMARY_REPORT="${PROJECT_ROOT}/tests/reports/ANALYTICS_TEST_SUMMARY.md"
cat > "${SUMMARY_REPORT}" << EOF
# Analytics Test Summary Report

Generated: $(date -u +"%Y-%m-%d %H:%M:%S UTC")

## Test Results

| Category | Total | Passed | Failed |
|----------|-------|--------|--------|
| Service Unit Tests | ${SERVICE_TEST_COUNT} | ${SERVICE_PASSED} | ${SERVICE_FAILED} |
| Controller Feature Tests | ${CONTROLLER_TEST_COUNT} | ${CONTROLLER_PASSED} | ${CONTROLLER_FAILED} |
| Component Tests | ${COMPONENT_TEST_COUNT} | ${COMPONENT_PASSED} | ${COMPONENT_FAILED} |
| Integration Tests | ${INTEGRATION_TEST_COUNT} | ${INTEGRATION_PASSED} | ${INTEGRATION_FAILED} |
| **Total** | **${TOTAL_TESTS}** | **${TOTAL_PASSED}** | **${TOTAL_FAILED}** |

## Coverage Report

- Coverage Report: ${COVERAGE_REPORT}
- HTML Coverage: ${HTML_COVERAGE_DIR}/index.html
- Threshold: ${COVERAGE_THRESHOLD}%

## Output Files

- Services Output: tests/reports/services_output.log
- Controllers Output: tests/reports/controllers_output.log
- Components Output: tests/reports/components_output.log
- Integration Output: tests/reports/integration_output.log
- Coverage Output: tests/reports/coverage_output.log

## Test Files

### Service Unit Tests
$(for test_file in "${SERVICE_TESTS[@]}"; do echo "- ${test_file}"; done)

### Controller Feature Tests
$(for test_file in "${CONTROLLER_TESTS[@]}"; do echo "- ${test_file}"; done)

### Component Tests
$(for test_file in "${COMPONENT_TESTS[@]}"; do echo "- ${test_file}"; done)

### Integration Tests
$(for test_file in "${INTEGRATION_TESTS[@]}"; do echo "- ${test_file}"; done)

## Recommendations

$(if [ $TOTAL_FAILED -gt 0 ]; then
    echo "❌ Some tests failed. Review the output logs for details."
    echo ""
    echo "### Failed Tests"
    if [ $SERVICE_FAILED -gt 0 ]; then
        echo "- Service tests failed: Review tests/reports/services_output.log"
    fi
    if [ $CONTROLLER_FAILED -gt 0 ]; then
        echo "- Controller tests failed: Review tests/reports/controllers_output.log"
    fi
    if [ $COMPONENT_FAILED -gt 0 ]; then
        echo "- Component tests failed: Review tests/reports/components_output.log"
    fi
    if [ $INTEGRATION_FAILED -gt 0 ]; then
        echo "- Integration tests failed: Review tests/reports/integration_output.log"
    fi
else
    echo "✅ All tests passed successfully!"
fi)

$(if [[ "$RUN_COVERAGE" == true ]]; then
    echo ""
    echo "## Coverage Analysis"
    if [ -f "${COVERAGE_REPORT}" ]; then
        COVERAGE_CHECK=$(echo "${COVERAGE_PERCENT} >= ${COVERAGE_THRESHOLD}" | bc -l 2>/dev/null || echo "0")
        if [ "${COVERAGE_CHECK}" == "1" ]; then
            echo "✅ Coverage threshold met (${COVERAGE_PERCENT}% >= ${COVERAGE_THRESHOLD}%)"
        else
            echo "⚠️ Coverage threshold not met (${COVERAGE_PERCENT}% < ${COVERAGE_THRESHOLD}%)"
            echo ""
            echo "### Coverage Recommendations"
            echo "- Review uncovered lines in HTML coverage report"
            echo "- Add unit tests for uncovered methods"
            echo "- Consider removing dead code"
        fi
    fi
fi)
EOF

print_success "Summary report generated: ${SUMMARY_REPORT}"

# Exit with appropriate code
if [ $TOTAL_FAILED -gt 0 ]; then
    echo ""
    print_error "Some tests failed. Check the summary report for details."
    exit 1
else
    echo ""
    print_success "All analytics tests passed successfully!"
    exit 0
fi
