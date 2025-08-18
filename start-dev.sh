#!/bin/bash
# ABOUTME: Enhanced Laravel + Vue development startup script for Unix/Linux environments
# ABOUTME: Provides server management, health monitoring, auto-restart, and interactive controls

# Configuration
VITE_PORT=5100
LARAVEL_PORT=8080
VITE_PID_FILE=".vite.pid"
LARAVEL_PID_FILE=".laravel.pid"
LOG_FILE="dev-server.log"
VITE_LOG="vite.log"
LARAVEL_LOG="laravel.log"

# Color definitions
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Executable paths (will be detected)
NODE_PATH=""
PHP_PATH=""
NPM_PATH=""
COMPOSER_PATH=""

# Function to log messages with timestamp
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to print colored output
print_colored() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Function to print status with emoji
print_status() {
    local status=$1
    local message=$2
    case $status in
        "success")
            print_colored "$GREEN" "✅ $message"
            ;;
        "error")
            print_colored "$RED" "❌ $message"
            ;;
        "warning")
            print_colored "$YELLOW" "⚠️  $message"
            ;;
        "info")
            print_colored "$BLUE" "ℹ️  $message"
            ;;
        "running")
            print_colored "$CYAN" "🚀 $message"
            ;;
        *)
            echo "$message"
            ;;
    esac
}

# Function to test if port is in use
test_port() {
    local port=$1
    if command -v netstat >/dev/null 2>&1; then
        netstat -tuln | grep -q ":$port "
    elif command -v ss >/dev/null 2>&1; then
        ss -tuln | grep -q ":$port "
    else
        # Fallback using lsof if available
        if command -v lsof >/dev/null 2>&1; then
            lsof -i :"$port" >/dev/null 2>&1
        else
            return 1
        fi
    fi
}

# Function to stop process on port
stop_process_on_port() {
    local port=$1
    local pids
    
    if command -v lsof >/dev/null 2>&1; then
        pids=$(lsof -ti :"$port" 2>/dev/null)
        if [ -n "$pids" ]; then
            print_status "warning" "Stopping processes on port $port..."
            echo "$pids" | xargs kill -TERM 2>/dev/null || true
            sleep 2
            # Force kill if still running
            pids=$(lsof -ti :"$port" 2>/dev/null)
            if [ -n "$pids" ]; then
                echo "$pids" | xargs kill -KILL 2>/dev/null || true
            fi
        fi
    else
        print_status "warning" "lsof not available, cannot stop processes on port $port"
    fi
}

# Function to test server health
test_server_health() {
    local port=$1
    local server_name=$2
    
    if command -v curl >/dev/null 2>&1; then
        if curl -s --connect-timeout 3 "http://localhost:$port" >/dev/null 2>&1; then
            return 0
        fi
    elif command -v wget >/dev/null 2>&1; then
        if wget -q --timeout=3 --tries=1 "http://localhost:$port" -O /dev/null 2>/dev/null; then
            return 0
        fi
    fi
    return 1
}

# Function to show memory usage
show_memory_usage() {
    print_status "info" "Memory Usage:"
    
    # Node.js processes
    if command -v pgrep >/dev/null 2>&1; then
        local node_pids=$(pgrep -f "node.*vite" 2>/dev/null || true)
        if [ -n "$node_pids" ]; then
            echo "Node.js (Vite) processes:"
            ps -p "$node_pids" -o pid,ppid,pcpu,pmem,comm 2>/dev/null || true
        fi
        
        # PHP processes
        local php_pids=$(pgrep -f "php.*artisan.*serve" 2>/dev/null || true)
        if [ -n "$php_pids" ]; then
            echo "PHP (Laravel) processes:"
            ps -p "$php_pids" -o pid,ppid,pcpu,pmem,comm 2>/dev/null || true
        fi
    fi
    
    # System memory
    if command -v free >/dev/null 2>&1; then
        echo "System Memory:"
        free -h
    elif [ -f /proc/meminfo ]; then
        echo "System Memory:"
        grep -E '^(MemTotal|MemFree|MemAvailable):' /proc/meminfo
    fi
}

# Function to show detailed memory usage
show_detailed_memory_usage() {
    print_status "info" "Detailed Memory Usage:"
    
    if command -v ps >/dev/null 2>&1; then
        echo "Top memory consuming processes:"
        ps aux --sort=-%mem | head -10
    fi
}

# Function to restart Vite server
restart_vite_server() {
    print_status "warning" "Restarting Vite server..."
    
    # Stop existing Vite process
    if [ -f "$VITE_PID_FILE" ]; then
        local vite_pid=$(cat "$VITE_PID_FILE" 2>/dev/null)
        if [ -n "$vite_pid" ] && kill -0 "$vite_pid" 2>/dev/null; then
            kill -TERM "$vite_pid" 2>/dev/null || true
            sleep 2
            if kill -0 "$vite_pid" 2>/dev/null; then
                kill -KILL "$vite_pid" 2>/dev/null || true
            fi
        fi
        rm -f "$VITE_PID_FILE"
    fi
    
    stop_process_on_port $VITE_PORT
    
    # Start new Vite process
    print_status "running" "Starting Vite server on port $VITE_PORT..."
    nohup "$NPM_PATH" run dev > "$VITE_LOG" 2>&1 &
    echo $! > "$VITE_PID_FILE"
    
    sleep 3
    if test_server_health $VITE_PORT "Vite"; then
        print_status "success" "Vite server restarted successfully"
    else
        print_status "error" "Failed to restart Vite server"
    fi
}

# Function to restart Laravel server
restart_laravel_server() {
    print_status "warning" "Restarting Laravel server..."
    
    # Stop existing Laravel process
    if [ -f "$LARAVEL_PID_FILE" ]; then
        local laravel_pid=$(cat "$LARAVEL_PID_FILE" 2>/dev/null)
        if [ -n "$laravel_pid" ] && kill -0 "$laravel_pid" 2>/dev/null; then
            kill -TERM "$laravel_pid" 2>/dev/null || true
            sleep 2
            if kill -0 "$laravel_pid" 2>/dev/null; then
                kill -KILL "$laravel_pid" 2>/dev/null || true
            fi
        fi
        rm -f "$LARAVEL_PID_FILE"
    fi
    
    stop_process_on_port $LARAVEL_PORT
    
    # Start new Laravel process
    print_status "running" "Starting Laravel server on port $LARAVEL_PORT..."
    nohup "$PHP_PATH" artisan serve --host=0.0.0.0 --port=$LARAVEL_PORT > "$LARAVEL_LOG" 2>&1 &
    echo $! > "$LARAVEL_PID_FILE"
    
    sleep 3
    if test_server_health $LARAVEL_PORT "Laravel"; then
        print_status "success" "Laravel server restarted successfully"
    else
        print_status "error" "Failed to restart Laravel server"
    fi
}

# Function to restart both servers
restart_servers() {
    print_status "warning" "Restarting both servers..."
    restart_vite_server
    restart_laravel_server
}

# Function to clear Laravel caches
clear_laravel_caches() {
    print_status "info" "Clearing Laravel caches..."
    
    local commands=("config:clear" "cache:clear" "route:clear" "view:clear")
    
    for cmd in "${commands[@]}"; do
        print_status "info" "Running: php artisan $cmd"
        if "$PHP_PATH" artisan $cmd 2>&1 | tee -a "$LOG_FILE"; then
            print_status "success" "✅ $cmd completed"
        else
            print_status "error" "❌ $cmd failed"
        fi
    done
    
    print_status "success" "Laravel caches cleared"
}

# Function to show log file
show_log_file() {
    local log_choice
    echo
    print_status "info" "Available log files:"
    echo "1. Combined log ($LOG_FILE)"
    echo "2. Vite log ($VITE_LOG)"
    echo "3. Laravel log ($LARAVEL_LOG)"
    echo "4. Laravel application log"
    echo
    read -p "Select log file (1-4): " log_choice
    
    case $log_choice in
        1)
            if [ -f "$LOG_FILE" ]; then
                print_status "info" "Showing last 50 lines of $LOG_FILE (Press Ctrl+C to exit):"
                tail -f "$LOG_FILE"
            else
                print_status "error" "Log file $LOG_FILE not found"
            fi
            ;;
        2)
            if [ -f "$VITE_LOG" ]; then
                print_status "info" "Showing last 50 lines of $VITE_LOG (Press Ctrl+C to exit):"
                tail -f "$VITE_LOG"
            else
                print_status "error" "Log file $VITE_LOG not found"
            fi
            ;;
        3)
            if [ -f "$LARAVEL_LOG" ]; then
                print_status "info" "Showing last 50 lines of $LARAVEL_LOG (Press Ctrl+C to exit):"
                tail -f "$LARAVEL_LOG"
            else
                print_status "error" "Log file $LARAVEL_LOG not found"
            fi
            ;;
        4)
            local laravel_log_path="storage/logs/laravel.log"
            if [ -f "$laravel_log_path" ]; then
                print_status "info" "Showing last 50 lines of $laravel_log_path (Press Ctrl+C to exit):"
                tail -f "$laravel_log_path"
            else
                print_status "error" "Laravel log file not found at $laravel_log_path"
            fi
            ;;
        *)
            print_status "error" "Invalid choice"
            ;;
    esac
}

# Function to cleanup on exit
cleanup() {
    print_status "warning" "Shutting down servers..."
    
    # Stop Vite server
    if [ -f "$VITE_PID_FILE" ]; then
        local vite_pid=$(cat "$VITE_PID_FILE" 2>/dev/null)
        if [ -n "$vite_pid" ] && kill -0 "$vite_pid" 2>/dev/null; then
            kill -TERM "$vite_pid" 2>/dev/null || true
            sleep 2
            if kill -0 "$vite_pid" 2>/dev/null; then
                kill -KILL "$vite_pid" 2>/dev/null || true
            fi
        fi
        rm -f "$VITE_PID_FILE"
    fi
    
    # Stop Laravel server
    if [ -f "$LARAVEL_PID_FILE" ]; then
        local laravel_pid=$(cat "$LARAVEL_PID_FILE" 2>/dev/null)
        if [ -n "$laravel_pid" ] && kill -0 "$laravel_pid" 2>/dev/null; then
            kill -TERM "$laravel_pid" 2>/dev/null || true
            sleep 2
            if kill -0 "$laravel_pid" 2>/dev/null; then
                kill -KILL "$laravel_pid" 2>/dev/null || true
            fi
        fi
        rm -f "$LARAVEL_PID_FILE"
    fi
    
    # Clean up any remaining processes on ports
    stop_process_on_port $VITE_PORT
    stop_process_on_port $LARAVEL_PORT
    
    print_status "success" "Cleanup completed. Goodbye! 👋"
    exit 0
}

# Set up signal handlers
trap cleanup SIGINT SIGTERM EXIT

# Main script starts here
print_colored "$MAGENTA" "🚀 Enhanced Laravel + Vue Development Server Startup"
print_colored "$MAGENTA" "================================================="
echo

# Detect executable paths
print_status "info" "Detecting executable paths..."

NODE_PATH=$(command -v node 2>/dev/null)
if [ -z "$NODE_PATH" ]; then
    print_status "error" "Node.js not found. Please install Node.js."
    exit 1
fi
print_status "success" "Node.js found: $NODE_PATH"

PHP_PATH=$(command -v php 2>/dev/null)
if [ -z "$PHP_PATH" ]; then
    print_status "error" "PHP not found. Please install PHP."
    exit 1
fi
print_status "success" "PHP found: $PHP_PATH"

NPM_PATH=$(command -v npm 2>/dev/null)
if [ -z "$NPM_PATH" ]; then
    print_status "error" "npm not found. Please install npm."
    exit 1
fi
print_status "success" "npm found: $NPM_PATH"

COMPOSER_PATH=$(command -v composer 2>/dev/null)
if [ -z "$COMPOSER_PATH" ]; then
    print_status "warning" "Composer not found. Some features may not work."
else
    print_status "success" "Composer found: $COMPOSER_PATH"
fi

echo

# Check for required files
if [ ! -f "package.json" ]; then
    print_status "error" "package.json not found. Are you in the correct directory?"
    exit 1
fi

if [ ! -f "artisan" ]; then
    print_status "error" "artisan file not found. Are you in a Laravel project directory?"
    exit 1
fi

# Check Node.js version
node_version=$("$NODE_PATH" --version 2>/dev/null | sed 's/v//')
if [ -n "$node_version" ]; then
    print_status "success" "Node.js version: $node_version"
fi

# Check PHP version
php_version=$("$PHP_PATH" --version 2>/dev/null | head -n1 | cut -d' ' -f2)
if [ -n "$php_version" ]; then
    print_status "success" "PHP version: $php_version"
fi

# Check npm version
npm_version=$("$NPM_PATH" --version 2>/dev/null)
if [ -n "$npm_version" ]; then
    print_status "success" "npm version: $npm_version"
fi

echo

# Check for port conflicts
print_status "info" "Checking for port conflicts..."

if test_port $VITE_PORT; then
    print_status "warning" "Port $VITE_PORT is already in use. Attempting to free it..."
    stop_process_on_port $VITE_PORT
    sleep 2
    if test_port $VITE_PORT; then
        print_status "error" "Failed to free port $VITE_PORT. Please manually stop the process."
        exit 1
    fi
    print_status "success" "Port $VITE_PORT is now available"
else
    print_status "success" "Port $VITE_PORT is available"
fi

if test_port $LARAVEL_PORT; then
    print_status "warning" "Port $LARAVEL_PORT is already in use. Attempting to free it..."
    stop_process_on_port $LARAVEL_PORT
    sleep 2
    if test_port $LARAVEL_PORT; then
        print_status "error" "Failed to free port $LARAVEL_PORT. Please manually stop the process."
        exit 1
    fi
    print_status "success" "Port $LARAVEL_PORT is now available"
else
    print_status "success" "Port $LARAVEL_PORT is available"
fi

echo

# Start Vite server
print_status "running" "Starting Vite development server on port $VITE_PORT..."
nohup "$NPM_PATH" run dev > "$VITE_LOG" 2>&1 &
echo $! > "$VITE_PID_FILE"
log_message "Vite server started with PID $(cat "$VITE_PID_FILE")"

# Start Laravel server
print_status "running" "Starting Laravel development server on port $LARAVEL_PORT..."
nohup "$PHP_PATH" artisan serve --host=0.0.0.0 --port=$LARAVEL_PORT > "$LARAVEL_LOG" 2>&1 &
echo $! > "$LARAVEL_PID_FILE"
log_message "Laravel server started with PID $(cat "$LARAVEL_PID_FILE")"

# Wait for servers to start
print_status "info" "Waiting for servers to start..."
sleep 5

# Check server health
vite_healthy=false
laravel_healthy=false

if test_server_health $VITE_PORT "Vite"; then
    print_status "success" "Vite server is running and healthy"
    vite_healthy=true
else
    print_status "error" "Vite server failed to start or is not responding"
fi

if test_server_health $LARAVEL_PORT "Laravel"; then
    print_status "success" "Laravel server is running and healthy"
    laravel_healthy=true
else
    print_status "error" "Laravel server failed to start or is not responding"
fi

echo

# Display status
print_colored "$GREEN" "🎉 Development Environment Status:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━