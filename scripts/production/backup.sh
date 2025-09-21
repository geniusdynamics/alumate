#!/bin/bash

# Production Backup Script for Unified Platform
# Handles automated backups of:
# - Database (PostgreSQL)
# - Media files and uploads
# - Configuration files
# - Application logs

set -e  # Exit on any error

# Configuration
BACKUP_ROOT_DIR=${BACKUP_ROOT_DIR:-"/opt/backups"}
RETENTION_DAYS=${RETENTION_DAYS:-30}
BACKUP_TYPE=${BACKUP_TYPE:-"full"}  # full, incremental, differential
COMPRESSION_LEVEL=${COMPRESSION_LEVEL:-6}
ENCRYPT_BACKUPS=${ENCRYPT_BACKUPS:-false}
REMOTE_BACKUP=${REMOTE_BACKUP:-false}
REMOTE_HOST=${REMOTE_HOST:-""}
REMOTE_USER=${REMOTE_USER:-""}
REMOTE_PATH=${REMOTE_PATH:-""}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Create backup directory structure
create_backup_dirs() {
    local timestamp=$(date +"%Y%m%d_%H%M%S")
    local date_dir=$(date +"%Y%m%d")

    BACKUP_DIR="$BACKUP_ROOT_DIR/$date_dir/$timestamp"
    BACKUP_DB_DIR="$BACKUP_DIR/database"
    BACKUP_FILES_DIR="$BACKUP_DIR/files"
    BACKUP_CONFIG_DIR="$BACKUP_DIR/config"
    BACKUP_LOGS_DIR="$BACKUP_DIR/logs"

    mkdir -p "$BACKUP_DB_DIR" "$BACKUP_FILES_DIR" "$BACKUP_CONFIG_DIR" "$BACKUP_LOGS_DIR"

    log_info "Created backup directories: $BACKUP_DIR"
}

# Backup PostgreSQL database
backup_database() {
    log_info "Starting database backup..."

    local db_host=${DB_HOST:-"localhost"}
    local db_port=${DB_PORT:-"5432"}
    local db_name=${DB_DATABASE:-"laravel"}
    local db_user=${DB_USERNAME:-"postgres"}
    local backup_file="$BACKUP_DB_DIR/database_backup.sql"

    # Export password for pg_dump
    export PGPASSWORD="$DB_PASSWORD"

    # Create database dump
    if pg_dump -h "$db_host" -p "$db_port" -U "$db_user" -d "$db_name" \
              --no-password --format=custom --compress=6 \
              --file="$backup_file" --verbose; then
        log_success "Database backup completed: $backup_file"
    else
        log_error "Database backup failed"
        return 1
    fi

    # Backup tenant databases if using schema separation
    if [ "$TENANT_DATABASE_MANAGER" = "PostgreSQLSchemaManager" ]; then
        log_info "Backing up tenant schemas..."
        # Get list of tenant schemas and backup each
        psql -h "$db_host" -p "$db_port" -U "$db_user" -d "$db_name" \
             -c "SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'tenant_%';" \
             --tuples-only --no-align | while read -r schema; do
            if [ -n "$schema" ]; then
                local tenant_backup="$BACKUP_DB_DIR/${schema}_backup.sql"
                pg_dump -h "$db_host" -p "$db_port" -U "$db_user" -d "$db_name" \
                       --no-password --format=custom --compress=6 \
                       --schema="$schema" --file="$tenant_backup"
                log_info "Backed up tenant schema: $schema"
            fi
        done
    # Backup analytics tables specifically
    log_info "Backing up analytics tables..."
    local analytics_backup="$BACKUP_DB_DIR/analytics_backup.sql"
    # Export analytics-related tables
    pg_dump -h "$db_host" -p "$db_port" -U "$db_user" -d "$db_name" \
           --no-password --format=custom --compress=6 \
           --table=analytics_events --table=heat_map_data --table=ab_test_results \
           --table=component_analytics --file="$analytics_backup"
    log_success "Analytics tables backup completed: $analytics_backup"
    fi

    # Unset password
    unset PGPASSWORD
}

# Backup media files and uploads
backup_files() {
    log_info "Starting files backup..."

    local source_dirs=("storage/app" "storage/logs" "public/uploads" "public/images")
    local backup_file="$BACKUP_FILES_DIR/files_backup.tar.gz"

    # Create list of files to backup
    local temp_file_list=$(mktemp)
    for dir in "${source_dirs[@]}"; do
        if [ -d "$dir" ]; then
            find "$dir" -type f >> "$temp_file_list"
        fi
    done

    # Create compressed archive
    if [ -s "$temp_file_list" ]; then
        tar -czf "$backup_file" --files-from="$temp_file_list" --transform='s|^./||'
        log_success "Files backup completed: $backup_file"
    else
        log_warning "No files found to backup"
    fi

    # Cleanup
    rm -f "$temp_file_list"
}

# Backup configuration files
backup_config() {
    log_info "Starting configuration backup..."

    local config_files=(".env" "config/*.php" "infrastructure/k8s/*.yaml" "docker-compose.yml")
    local backup_file="$BACKUP_CONFIG_DIR/config_backup.tar.gz"

    # Create list of config files to backup
    local temp_file_list=$(mktemp)
    for pattern in "${config_files[@]}"; do
        if compgen -G "$pattern" > /dev/null; then
            ls $pattern >> "$temp_file_list" 2>/dev/null || true
        fi
    done

    # Create compressed archive
    if [ -s "$temp_file_list" ]; then
        tar -czf "$backup_file" --files-from="$temp_file_list"
        log_success "Configuration backup completed: $backup_file"
    else
        log_warning "No configuration files found to backup"
    fi

    # Cleanup
    rm -f "$temp_file_list"
}

# Backup application logs
backup_logs() {
    log_info "Starting logs backup..."

    local log_files=("storage/logs/*.log" "logs/*.log")
    local backup_file="$BACKUP_LOGS_DIR/logs_backup.tar.gz"

    # Create list of log files to backup
    local temp_file_list=$(mktemp)
    for pattern in "${log_files[@]}"; do
        if compgen -G "$pattern" > /dev/null; then
            ls $pattern >> "$temp_file_list" 2>/dev/null || true
        fi
    done

    # Create compressed archive
    if [ -s "$temp_file_list" ]; then
        tar -czf "$backup_file" --files-from="$temp_file_list"
        log_success "Logs backup completed: $backup_file"
    else
        log_warning "No log files found to backup"
    fi

    # Cleanup
    rm -f "$temp_file_list"
}

# Encrypt backup files
encrypt_backups() {
    if [ "$ENCRYPT_BACKUPS" = true ]; then
        log_info "Encrypting backup files..."

        local encryption_key=${BACKUP_ENCRYPTION_KEY:-""}
        if [ -z "$encryption_key" ]; then
            log_error "BACKUP_ENCRYPTION_KEY not set for encryption"
            return 1
        fi

        # Encrypt each backup file
        find "$BACKUP_DIR" -name "*.tar.gz" -o -name "*.sql" | while read -r file; do
            if [ -f "$file" ]; then
                openssl enc -aes-256-cbc -salt -in "$file" -out "${file}.enc" -k "$encryption_key"
                rm -f "$file"
                log_info "Encrypted: $file"
            fi
        done

        log_success "Backup encryption completed"
    fi
}

# Transfer backups to remote location
transfer_to_remote() {
    if [ "$REMOTE_BACKUP" = true ] && [ -n "$REMOTE_HOST" ] && [ -n "$REMOTE_USER" ]; then
        log_info "Transferring backups to remote location..."

        if command_exists rsync; then
            rsync -avz --delete "$BACKUP_DIR/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"
            log_success "Remote backup transfer completed"
        else
            log_error "rsync not found for remote transfer"
            return 1
        fi
    fi
}

# Clean up old backups
cleanup_old_backups() {
    log_info "Cleaning up old backups (retention: ${RETENTION_DAYS} days)..."

    # Find and remove old backup directories
    find "$BACKUP_ROOT_DIR" -maxdepth 1 -type d -mtime +"$RETENTION_DAYS" \
         -exec rm -rf {} \; 2>/dev/null || true

    log_success "Old backups cleanup completed"
}

# Generate backup report
generate_report() {
    local report_file="$BACKUP_DIR/backup_report.txt"
    local total_size=$(du -sh "$BACKUP_DIR" | cut -f1)

    cat > "$report_file" << EOF
BACKUP REPORT
=============

Backup Date: $(date)
Backup Type: $BACKUP_TYPE
Backup Directory: $BACKUP_DIR
Total Size: $total_size

BACKUP COMPONENTS:
$(find "$BACKUP_DIR" -type f -exec ls -lh {} \; | awk '{print "  " $9 " (" $5 ")"}')

SYSTEM INFORMATION:
- Hostname: $(hostname)
- OS: $(uname -s) $(uname -r)
- Database: PostgreSQL
- Compression: gzip level $COMPRESSION_LEVEL
- Encryption: $([ "$ENCRYPT_BACKUPS" = true ] && echo "Enabled" || echo "Disabled")

RETENTION POLICY:
- Retention Period: $RETENTION_DAYS days
- Auto Cleanup: Enabled

REMOTE BACKUP:
- Enabled: $([ "$REMOTE_BACKUP" = true ] && echo "Yes" || echo "No")
$(if [ "$REMOTE_BACKUP" = true ]; then echo "- Remote Host: $REMOTE_HOST"; fi)
$(if [ "$REMOTE_BACKUP" = true ]; then echo "- Remote Path: $REMOTE_PATH"; fi)

BACKUP STATUS: SUCCESS
EOF

    log_success "Backup report generated: $report_file"
}

# Send notification
send_notification() {
    local subject="Backup Completed Successfully - $(date)"
    local body="Backup completed for $(hostname) at $(date).\nBackup location: $BACKUP_DIR\nTotal size: $(du -sh "$BACKUP_DIR" | cut -f1)"

    # Send email if mail command is available
    if command_exists mail && [ -n "$NOTIFICATION_EMAIL" ]; then
        echo -e "$body" | mail -s "$subject" "$NOTIFICATION_EMAIL"
        log_info "Notification email sent to $NOTIFICATION_EMAIL"
    fi

    # Log to system logger
    logger -t backup-script "$subject"
}

# Main backup function
main() {
    log_info "Starting backup process..."

    # Validate environment
    if [ ! -f ".env" ]; then
        log_error "No .env file found. Please run this script from the application root directory."
        exit 1
    fi

    # Load environment variables
    set -a
    source .env
    set +a

    create_backup_dirs
    backup_database
    backup_files
    backup_config
    backup_logs
    encrypt_backups
    transfer_to_remote
    cleanup_old_backups
    generate_report
    send_notification

    log_success "Backup process completed successfully!"
}

# Handle command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --type=*)
            BACKUP_TYPE="${1#*=}"
            shift
            ;;
        --retention=*)
            RETENTION_DAYS="${1#*=}"
            shift
            ;;
        --encrypt)
            ENCRYPT_BACKUPS=true
            shift
            ;;
        --remote)
            REMOTE_BACKUP=true
            shift
            ;;
        --cleanup-only)
            log_info "Running cleanup only..."
            cleanup_old_backups
            exit 0
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --type=TYPE         Backup type: full, incremental, differential (default: full)"
            echo "  --retention=DAYS    Number of days to retain backups (default: 30)"
            echo "  --encrypt           Encrypt backup files"
            echo "  --remote            Transfer backups to remote location"
            echo "  --cleanup-only      Only cleanup old backups"
            echo "  --help              Show this help message"
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Run main backup
main