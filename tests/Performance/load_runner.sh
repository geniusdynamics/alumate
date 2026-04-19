#!/bin/bash

# Analytics Load Testing Runner Script
# Executes Artillery load tests for analytics endpoints with proper environment setup

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
ARTILLERY_CONFIG="$SCRIPT_DIR/artillery/analytics_load.yml"
REPORTS_DIR="$SCRIPT_DIR/reports"
LOG_FILE="$REPORTS_DIR/load_test_$(date +%Y%m%d_%H%M%S).log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

# Check dependencies
check_dependencies() {
    log "Checking dependencies..."

    if ! command -v artillery &> /dev/null; then
        error "Artillery is not installed. Please run: npm install -g artillery"
    fi

    if ! command -v node &> /dev/null; then
        error "Node.js is not installed"
    fi

    if ! command -v php &> /dev/null; then
        error "PHP is not installed"
    fi

    success "All dependencies are available"
}

# Setup environment
setup_environment() {
    log "Setting up test environment..."

    # Create reports directory
    mkdir -p "$REPORTS_DIR"

    # Check if Laravel server is running
    if ! curl -s http://127.0.0.1:8080/api/health > /dev/null 2>&1; then
        warning "Laravel development server not detected on port 8080"
        log "Please ensure the Laravel server is running with: php artisan serve --host=127.0.0.1 --port=8080"
        log "Continuing with tests anyway..."
    else
        success "Laravel server is running"
    fi

    # Set environment variables
    export NODE_ENV=test
    export APP_ENV=testing

    success "Environment setup complete"
}

# Run Artillery load tests
run_artillery_tests() {
    local environment=${1:-development}
    local config_file=${2:-$ARTILLERY_CONFIG}

    log "Running Artillery load tests (environment: $environment)..."

    if [ ! -f "$config_file" ]; then
        error "Artillery config file not found: $config_file"
    fi

    local artillery_cmd="artillery run --environment $environment --output $REPORTS_DIR/artillery-results-$(date +%Y%m%d_%H%M%S).json $config_file"

    log "Executing: $artillery_cmd"

    if eval "$artillery_cmd" >> "$LOG_FILE" 2>&1; then
        success "Artillery load tests completed successfully"
    else
        error "Artillery load tests failed"
    fi
}

# Run PHP performance tests
run_php_performance_tests() {
    log "Running PHP performance tests..."

    cd "$PROJECT_ROOT"

    # Run specific performance test classes
    local test_classes=(
        "Tests\\Performance\\EventProcessingLoadTest"
        "Tests\\Performance\\DashboardRenderingStressTest"
        "Tests\\Performance\\SyncAndIntegrationStressTest"
        "Tests\\Performance\\PerformanceBenchmarkRunner"
    )

    for test_class in "${test_classes[@]}"; do
        log "Running $test_class..."

        if php artisan test --filter="$test_class" >> "$LOG_FILE" 2>&1; then
            success "$test_class completed successfully"
        else
            error "$test_class failed"
        fi
    done
}

# Generate comprehensive report
generate_report() {
    log "Generating comprehensive load testing report..."

    local report_file="$REPORTS_DIR/load_test_report_$(date +%Y%m%d_%H%M%S).json"

    # Collect all test results
    cat > "$report_file" << EOF
{
    "test_run": {
        "timestamp": "$(date -Iseconds)",
        "environment": "development",
        "duration": "TBD"
    },
    "artillery_results": $(find "$REPORTS_DIR" -name "artillery-results-*.json" -newer "$LOG_FILE" -exec cat {} \; 2>/dev/null | head -1 || echo "{}"),
    "php_performance_results": $(find "$REPORTS_DIR" -name "*performance*.json" -newer "$LOG_FILE" -exec cat {} \; 2>/dev/null | jq -s '.[0] // {}' 2>/dev/null || echo "{}"),
    "summary": {
        "status": "completed",
        "log_file": "$LOG_FILE"
    }
}
EOF

    success "Comprehensive report generated: $report_file"
}

# Cleanup function
cleanup() {
    log "Cleaning up temporary files..."
    # Add cleanup logic here if needed
    success "Cleanup completed"
}

# Main execution
main() {
    local environment=${1:-development}
    local run_artillery=${2:-true}
    local run_php=${3:-true}

    log "Starting Analytics Load Testing Suite"
    log "Environment: $environment"
    log "Run Artillery: $run_artillery"
    log "Run PHP Tests: $run_php"

    trap cleanup EXIT

    check_dependencies
    setup_environment

    if [ "$run_artillery" = true ]; then
        run_artillery_tests "$environment"
    fi

    if [ "$run_php" = true ]; then
        run_php_performance_tests
    fi

    generate_report

    success "Analytics Load Testing Suite completed successfully"
    log "Results saved to: $REPORTS_DIR"
    log "Log file: $LOG_FILE"
}

# Parse command line arguments
ENVIRONMENT="development"
RUN_ARTILLERY=true
RUN_PHP=true

while [[ $# -gt 0 ]]; do
    case $1 in
        --environment=*)
            ENVIRONMENT="${1#*=}"
            shift
            ;;
        --skip-artillery)
            RUN_ARTILLERY=false
            shift
            ;;
        --skip-php)
            RUN_PHP=false
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --environment=ENV    Set test environment (default: development)"
            echo "  --skip-artillery      Skip Artillery load tests"
            echo "  --skip-php           Skip PHP performance tests"
            echo "  --help               Show this help message"
            exit 0
            ;;
        *)
            error "Unknown option: $1"
            ;;
    esac
done

# Run main function
main "$ENVIRONMENT" "$RUN_ARTILLERY" "$RUN_PHP"