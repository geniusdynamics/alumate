#!/bin/bash
# ABOUTME: Development startup script for Laravel + Vite project (Unix/Linux)
# ABOUTME: Automatically detects project directory and starts both frontend and backend servers

# Default ports
VITE_PORT=5173
LARAVEL_PORT=8080
SKIP_CHECKS=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-checks)
            SKIP_CHECKS=true
            shift
            ;;
        --vite-port)
            VITE_PORT="$2"
            shift 2
            ;;
        --laravel-port)
            LARAVEL_PORT="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            echo "Usage: $0 [--skip-checks] [--vite-port PORT] [--laravel-port PORT]"
            exit 1
            ;;
    esac
done

# Color output functions
print_success() { echo -e "\033[32m$1\033[0m"; }
print_info() { echo -e "\033[36m$1\033[0m"; }
print_warning() { echo -e "\033[33m$1\033[0m"; }
print_error() { echo -e "\033[31m$1\033[0m"; }

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# PHP executable path (adjust for your system)
PHP_PATH="/usr/bin/php"

# Function to test if a port is available
test_port() {
    local port=$1
    if command -v nc >/dev/null 2>&1; then
        ! nc -z localhost "$port" 2>/dev/null
    elif command -v netstat >/dev/null 2>&1; then
        ! netstat -tuln | grep -q ":$port "
    else
        # Fallback: try to bind to the port
        (echo >/dev/tcp/localhost/$port) 2>/dev/null && return 1 || return 0
    fi
}

# Function to kill processes on specific ports
stop_process_on_port() {
    local port=$1
    if command -v lsof >/dev/null 2>&1; then
        local pids=$(lsof -ti:"$port" 2>/dev/null)
        if [[ -n "$pids" ]]; then
            echo "$pids" | xargs kill -9 2>/dev/null
            print_info "Stopped processes on port $port"
        fi
    elif command -v netstat >/dev/null 2>&1; then
        local pids=$(netstat -tulpn 2>/dev/null | grep ":$port " | awk '{print $7}' | cut -d'/' -f1)
        if [[ -n "$pids" ]]; then
            echo "$pids" | xargs kill -9 2>/dev/null
            print_info "Stopped processes on port $port"
        fi
    fi
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Cleanup function
cleanup() {
    print_info "Cleaning up background processes..."
    
    if [[ -n "$VITE_PID" ]]; then
        kill "$VITE_PID" 2>/dev/null
        print_info "Vite server stopped"
    fi
    
    if [[ -n "$LARAVEL_PID" ]]; then
        kill "$LARAVEL_PID" 2>/dev/null
        print_info "Laravel server stopped"
    fi
    
    # Additional cleanup
    stop_process_on_port "$VITE_PORT"
    stop_process_on_port "$LARAVEL_PORT"
    
    print_success "Cleanup completed!"
    exit 0
}

# Set up signal handlers
trap cleanup SIGINT SIGTERM

print_info "=== Laravel + Vite Development Server Startup ==="
print_info "Project Directory: $SCRIPT_DIR"
print_info "PHP Path: $PHP_PATH"

# Verify we're in a Laravel project
if [[ ! -f "artisan" ]]; then
    print_error "Laravel artisan file not found. Please run this script from your Laravel project root."
    exit 1
fi

if [[ "$SKIP_CHECKS" != "true" ]]; then
    # Check PHP
    if ! command_exists php; then
        print_error "PHP not found. Please install PHP."
        exit 1
    fi
    
    # Check Node.js
    if ! command_exists node; then
        print_error "Node.js not found. Please install Node.js."
        exit 1
    fi
    
    # Check npm
    if ! command_exists npm; then
        print_error "npm not found. Please install npm."
        exit 1
    fi
    
    print_success "All dependencies found!"
fi

# Handle port conflicts
if ! test_port "$VITE_PORT"; then
    print_warning "Port $VITE_PORT is in use. Attempting to free it..."
    stop_process_on_port "$VITE_PORT"
    sleep 2
fi

if ! test_port "$LARAVEL_PORT"; then
    print_warning "Port $LARAVEL_PORT is in use. Attempting to free it..."
    stop_process_on_port "$LARAVEL_PORT"
    sleep 2
fi

# Install/update dependencies
print_info "Installing/updating dependencies..."
if [[ -f "package.json" ]]; then
    npm install
    if [[ $? -ne 0 ]]; then
        print_error "npm install failed"
        exit 1
    fi
fi

if [[ -f "composer.json" ]]; then
    if command_exists composer; then
        composer install --no-dev --optimize-autoloader 2>/dev/null || print_warning "Composer install had issues, continuing..."
    fi
fi

# Clear Laravel caches
print_info "Clearing Laravel caches..."
php artisan config:clear 2>/dev/null
php artisan route:clear 2>/dev/null
php artisan view:clear 2>/dev/null
php artisan cache:clear 2>/dev/null

print_success "Caches cleared!"

# Start Vite development server
print_info "Starting Vite development server on port $VITE_PORT..."
npm run dev -- --port "$VITE_PORT" --host 0.0.0.0 &
VITE_PID=$!

sleep 3

# Start Laravel development server
print_info "Starting Laravel development server on port $LARAVEL_PORT..."
php artisan serve --host=127.0.0.1 --port="$LARAVEL_PORT" &
LARAVEL_PID=$!

sleep 3

# Check if servers started successfully
if kill -0 "$VITE_PID" 2>/dev/null && kill -0 "$LARAVEL_PID" 2>/dev/null; then
    print_success "=== Development servers started successfully! ==="
    print_success "Frontend (Vite): http://localhost:$VITE_PORT"
    print_success "Backend (Laravel): http://127.0.0.1:$LARAVEL_PORT"
    print_info "Press Ctrl+C to stop both servers"
    
    # Monitor processes
    while kill -0 "$VITE_PID" 2>/dev/null && kill -0 "$LARAVEL_PID" 2>/dev/null; do
        sleep 5
    done
    
    print_warning "One or more servers stopped unexpectedly"
else
    print_error "Failed to start one or more servers"
    if ! kill -0 "$VITE_PID" 2>/dev/null; then
        print_error "Vite server failed to start"
    fi
    if ! kill -0 "$LARAVEL_PID" 2>/dev/null; then
        print_error "Laravel server failed to start"
    fi
fi

cleanup