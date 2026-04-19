#!/bin/bash

# ============================================================================
# Production Backup Configuration Script
# ============================================================================
# This script configures automated backup systems for the Alumni Platform
# Supports database backups, file backups, and configuration backups
# Includes backup verification, notifications, and disaster recovery procedures
# ============================================================================

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${APP_ROOT}/backups"
BACKUP_LOG="${APP_ROOT}/storage/logs/backup.log"
DB_BACKUP_DIR="${BACKUP_DIR}/database"
FILES_BACKUP_DIR="${BACKUP_DIR}/files"
CONFIG_BACKUP_DIR="${BACKUP_DIR}/config"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Retention settings (in days)
DB_RETENTION=${BACKUP_RETENTION_DATABASE:-30}
FILES_RETENTION=${BACKUP_RETENTION_FILES:-90}
CONFIG_RETENTION=${BACKUP_RETENTION_CONFIG:-365}

# Compression settings
COMPRESSION_LEVEL=9
BACKUP_COMPRESSION=lz4

# Notification settings
NOTIFICATIONS_ENABLED=${BACKUP_NOTIFICATIONS_ENABLED:-true}
NOTIFICATION_EMAIL_RECIPIENTS=${ALERT_EMAIL_RECIPIENTS:-admin@example.com}

# ============================================================================
# LOGGING FUNCTIONS
# ============================================================================

log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $*" | tee -a "$BACKUP_LOG"; }
log_success() { echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS:${NC} $*" | tee -a "$BACKUP_LOG"; }
log_warning() { echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $*" | tee -a "$BACKUP_LOG"; }
log_error() { echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $*" | tee -a "$BACKUP_LOG"; }

# ============================================================================
# NOTIFICATION FUNCTIONS
# ============================================================================

send_notification() {
    local status="$1"
    local message="$2"
    
    if [ "$NOTIFICATIONS_ENABLED" != "true" ]; then
        return 0
    fi
    
    log "Sending notification: $status"
    
    # Log to application log for Laravel notification system
    local log_entry="{\"type\":\"backup_notification\",\"status\":\"$status\",\"message\":\"$message\",\"timestamp\":\"$(date -Iseconds)\"}"
    echo "$log_entry" >> "${APP_ROOT}/storage/logs/backup_notifications.log"
    
    # If mail command is available, send email notification
    if command -v mail &> /dev/null; then
        local subject="[Alumni Platform] Backup $status: $message"
        echo "$message" | mail -s "$subject" "$NOTIFICATION_EMAIL_RECIPIENTS" 2>/dev/null || true
    fi
}

# ============================================================================
# DATABASE BACKUP FUNCTIONS
# ============================================================================

backup_database() {
    local backup_file="${DB_BACKUP_DIR}/db_${TIMESTAMP}.sql.gz"
    log "Starting database backup..."
    
    # Ensure backup directory exists
    mkdir -p "$DB_BACKUP_DIR"
    
    # Get database connection parameters from environment
    local DB_HOST="${DB_HOST:-db}"
    local DB_PORT="${DB_PORT:-5432}"
    local DB_DATABASE="${DB_DATABASE:-alumni_platform}"
    local DB_USERNAME="${DB_USERNAME:-}"
    local DB_PASSWORD="${DB_PASSWORD:-}"
    
    # Create database backup using pg_dump
    if command -v pg_dump &> /dev/null; then
        export PGPASSWORD="$DB_PASSWORD"
        
        # Perform the backup with compression
        pg_dump \
            -h "$DB_HOST" \
            -U "$DB_USERNAME" \
            -d "$DB_DATABASE" \
            --format=custom \
            --compress="$COMPRESSION_LEVEL" \
            --no-owner \
            --no-privileges \
            --verbose \
            --file="$backup_file" 2>&1 | tee -a "$BACKUP_LOG"
        
        if [ $? -eq 0 ]; then
            log_success "Database backup completed: $backup_file"
            verify_backup "$backup_file" "database"
            upload_to_remote "$backup_file" "database"
            send_notification "SUCCESS" "Database backup completed: $backup_file"
            return 0
        else
            log_error "Database backup failed"
            send_notification "FAILED" "Database backup failed"
            return 1
        fi
    else
        log_error "pg_dump not found - cannot create database backup"
        return 1
    fi
}

# ============================================================================
# FILES BACKUP FUNCTIONS
# ============================================================================

backup_files() {
    local backup_file="${FILES_BACKUP_DIR}/files_${TIMESTAMP}.tar.gz"
    log "Starting files backup..."
    
    # Ensure backup directory exists
    mkdir -p "$FILES_BACKUP_DIR"
    
    # Define directories to backup
    local backup_dirs=(
        "${APP_ROOT}/storage/app/public"
        "${APP_ROOT}/storage/framework/cache"
        "${APP_ROOT}/storage/framework/sessions"
        "${APP_ROOT}/storage/framework/views"
        "${APP_ROOT}/storage/logs"
    )
    
    # Create compressed archive of important files
    tar -czf "$backup_file" \
        -C "$(dirname "$APP_ROOT")" \
        --listed-incremental="${BACKUP_DIR}/files.snar" \
        --exclude="*.log" \
        --exclude="*.tmp" \
        --exclude="node_modules" \
        --exclude="vendor" \
        --exclude=".git" \
        --exclude="backups" \
        $(basename "$APP_ROOT")/storage 2>&1 | tee -a "$BACKUP_LOG"
    
    if [ $? -eq 0 ]; then
        log_success "Files backup completed: $backup_file"
        verify_backup "$backup_file" "files"
        upload_to_remote "$backup_file" "files"
        send_notification "SUCCESS" "Files backup completed: $backup_file"
        return 0
    else
        log_error "Files backup failed"
        send_notification "FAILED" "Files backup failed"
        return 1
    fi
}

# ============================================================================
# CONFIGURATION BACKUP FUNCTIONS
# ============================================================================

backup_config() {
    local backup_file="${CONFIG_BACKUP_DIR}/config_${TIMESTAMP}.tar.gz"
    log "Starting configuration backup..."
    
    # Ensure backup directory exists
    mkdir -p "$CONFIG_BACKUP_DIR"
    
    # Define configuration files to backup
    local config_files=(
        "${APP_ROOT}/.env.production"
        "${APP_ROOT}/.env"
        "${APP_ROOT}/config/"
        "${APP_ROOT}/infrastructure/production/"
        "${APP_ROOT}/docker-compose.prod.yml"
        "${APP_ROOT}/nginx.conf"
    )
    
    # Create compressed archive of configuration files
    tar -czf "$backup_file" \
        -C "$(dirname "$APP_ROOT")" \
        --exclude="*.log" \
        --exclude="*.tmp" \
        --exclude="*.key" \
        --exclude="*.pem" \
        $(basename "$APP_ROOT")/.env* \
        $(basename "$APP_ROOT")/config/ \
        $(basename "$APP_ROOT")/infrastructure/ \
        $(basename "$APP_ROOT")/docker-compose.prod.yml \
        $(basename "$APP_ROOT")/nginx.conf 2>&1 | tee -a "$BACKUP_LOG"
    
    if [ $? -eq 0 ]; then
        log_success "Configuration backup completed: $backup_file"
        verify_backup "$backup_file" "config"
        upload_to_remote "$backup_file" "config"
        send_notification "SUCCESS" "Configuration backup completed: $backup_file"
        return 0
    else
        log_error "Configuration backup failed"
        send_notification "FAILED" "Configuration backup failed"
        return 1
    fi
}

# ============================================================================
# REMOTE UPLOAD FUNCTIONS
# ============================================================================

upload_to_remote() {
    local backup_file="$1"
    local backup_type="$2"
    local remote_provider="${BACKUP_PROVIDER:-aws_s3}"
    
    log "Uploading $backup_type backup to remote storage..."
    
    case "$remote_provider" in
        aws_s3)
            if command -v aws &> /dev/null; then
                local s3_bucket="${BACKUP_AWS_BUCKET:-your-backup-bucket}"
                local s3_path="s3://${s3_bucket}/${APP_ENV}/${backup_type}/${TIMESTAMP}/"
                
                aws s3 cp "$backup_file" "$s3_path" \
                    --storage-class STANDARD_IA \
                    --metadata "backup-type=${backup_type},timestamp=${TIMESTAMP}" \
                    2>&1 | tee -a "$BACKUP_LOG"
                
                if [ $? -eq 0 ]; then
                    log_success "Uploaded to S3: $s3_path"
                else
                    log_warning "S3 upload failed - backup retained locally"
                fi
            else
                log_warning "AWS CLI not found - skipping remote upload"
            fi
            ;;
        gcp)
            if command -v gsutil &> /dev/null; then
                local gcs_bucket="${BACKUP_GCS_BUCKET:-your-backup-bucket}"
                local gcs_path="gs://${gcs_bucket}/${APP_ENV}/${backup_type}/${TIMESTAMP}/"
                
                gsutil cp "$backup_file" "$gcs_path" \
                    -c STANDARD \
                    -m 2>&1 | tee -a "$BACKUP_LOG"
                
                if [ $? -eq 0 ]; then
                    log_success "Uploaded to GCS: $gcs_path"
                else
                    log_warning "GCS upload failed - backup retained locally"
                fi
            else
                log_warning "GSUtil not found - skipping remote upload"
            fi
            ;;
        azure)
            if command -v az &> /dev/null; then
                local azure_container="${BACKUP_AZURE_CONTAINER:-backups}"
                local azure_path="${APP_ENV}/${backup_type}/${TIMESTAMP}/"
                
                az storage blob upload \
                    --container-name "$azure_container" \
                    --name "${azure_path}$(basename "$backup_file")" \
                    --file "$backup_file" \
                    2>&1 | tee -a "$BACKUP_LOG"
                
                if [ $? -eq 0 ]; then
                    log_success "Uploaded to Azure Blob Storage"
                else
                    log_warning "Azure upload failed - backup retained locally"
                fi
            else
                log_warning "Azure CLI not found - skipping remote upload"
            fi
            ;;
        *)
            log_warning "Unknown remote provider: $remote_provider"
            ;;
    esac
}

# ============================================================================
# CLEANUP FUNCTIONS
# ============================================================================

cleanup_old_backups() {
    log "Cleaning up old backups..."
    
    # Database backups cleanup
    if [ -d "$DB_BACKUP_DIR" ]; then
        find "$DB_BACKUP_DIR" -type f -name "*.sql.gz" -mtime +$DB_RETENTION -delete
        log "Cleaned up database backups older than $DB_RETENTION days"
    fi
    
    # Files backups cleanup
    if [ -d "$FILES_BACKUP_DIR" ]; then
        find "$FILES_BACKUP_DIR" -type f -name "*.tar.gz" -mtime +$FILES_RETENTION -delete
        log "Cleaned up files backups older than $FILES_RETENTION days"
    fi
    
    # Config backups cleanup
    if [ -d "$CONFIG_BACKUP_DIR" ]; then
        find "$CONFIG_BACKUP_DIR" -type f -name "*.tar.gz" -mtime +$CONFIG_RETENTION -delete
        log "Cleaned up config backups older than $CONFIG_RETENTION days"
    fi
    
    # Cleanup old S3 backups
    cleanup_remote_old_backups
    
    log_success "Old backups cleanup completed"
}

cleanup_remote_old_backups() {
    local remote_provider="${BACKUP_PROVIDER:-aws_s3}"
    
    case "$remote_provider" in
        aws_s3)
            if command -v aws &> /dev/null; then
                local s3_bucket="${BACKUP_AWS_BUCKET:-your-backup-bucket}"
                
                # List and delete old S3 objects
                aws s3 ls "s3://${s3_bucket}/${APP_ENV}/" --recursive | while read -r line; do
                    local object_date=$(echo "$line" | awk '{print $1}')
                    local object_path=$(echo "$line" | awk '{print $4}')
                    local object_timestamp=$(echo "$object_path" | grep -oE '[0-9]{8}_[0-9]{6}')
                    
                    if [ -n "$object_timestamp" ]; then
                        local object_epoch=$(date -d "$object_date" +%s 2>/dev/null || date -j -f "%Y-%m-%d" "$object_date" +%s 2>/dev/null)
                        local current_epoch=$(date +%s)
                        local retention_seconds=$((DB_RETENTION * 86400))
                        
                        if [ $((current_epoch - object_epoch)) -gt $retention_seconds ]; then
                            aws s3 rm "s3://${s3_bucket}/${object_path}" 2>&1 | tee -a "$BACKUP_LOG"
                        fi
                    fi
                done
            fi
            ;;
    esac
}

# ============================================================================
# VERIFICATION FUNCTIONS
# ============================================================================

verify_backup() {
    local backup_file="$1"
    local backup_type="$2"
    
    log "Verifying backup: $backup_file"
    
    if [ ! -f "$backup_file" ]; then
        log_error "Backup file not found: $backup_file"
        return 1
    fi
    
    # Check file size (minimum 1KB for non-empty backups)
    local file_size=$(stat -f%z "$backup_file" 2>/dev/null || stat -c%s "$backup_file" 2>/dev/null)
    local min_size=1024
    
    if [ "$file_size" -lt "$min_size" ]; then
        log_error "Backup file too small: $file_size bytes (minimum $min_size bytes)"
        return 1
    fi
    
    # Verify compression integrity for compressed files
    if [[ "$backup_file" =~ \.(gz|tar\.gz)$ ]]; then
        if ! gzip -t "$backup_file" 2>/dev/null; then
            log_error "Backup file corrupted: $backup_file"
            return 1
        fi
    fi
    
    log_success "Backup verification passed: $backup_file ($file_size bytes)"
    return 0
}

# ============================================================================
# BACKUP STATUS FUNCTIONS
# ============================================================================

get_backup_status() {
    log "=== Backup Status Report ==="
    log "Generated: $(date)"
    log ""
    
    log "Database Backups:"
    if [ -d "$DB_BACKUP_DIR" ]; then
        local db_count=$(find "$DB_BACKUP_DIR" -type f -name "*.sql.gz" | wc -l)
        local db_size=$(du -sh "$DB_BACKUP_DIR" 2>/dev/null | cut -f1)
        log "  Count: $db_count files"
        log "  Size: $db_size"
    else
        log "  No database backups found"
    fi
    
    log ""
    log "Files Backups:"
    if [ -d "$FILES_BACKUP_DIR" ]; then
        local files_count=$(find "$FILES_BACKUP_DIR" -type f -name "*.tar.gz" | wc -l)
        local files_size=$(du -sh "$FILES_BACKUP_DIR" 2>/dev/null | cut -f1)
        log "  Count: $files_count files"
        log "  Size: $files_size"
    else
        log "  No files backups found"
    fi
    
    log ""
    log "Configuration Backups:"
    if [ -d "$CONFIG_BACKUP_DIR" ]; then
        local config_count=$(find "$CONFIG_BACKUP_DIR" -type f -name "*.tar.gz" | wc -l)
        local config_size=$(du -sh "$CONFIG_BACKUP_DIR" 2>/dev/null | cut -f1)
        log "  Count: $config_count files"
        log "  Size: $config_size"
    else
        log "  No configuration backups found"
    fi
    
    log ""
    log "Total Backup Size:"
    if [ -d "$BACKUP_DIR" ]; then
        du -sh "$BACKUP_DIR" 2>/dev/null
    fi
    
    log "=== End Report ==="
}

# ============================================================================
# RESTORE FUNCTIONS
# ============================================================================

restore_database() {
    local backup_file="$1"
    
    if [ ! -f "$backup_file" ]; then
        log_error "Backup file not found: $backup_file"
        return 1
    fi
    
    log "Starting database restoration from: $backup_file"
    
    # Get database connection parameters from environment
    local DB_HOST="${DB_HOST:-db}"
    local DB_PORT="${DB_PORT:-5432}"
    local DB_DATABASE="${DB_DATABASE:-alumni_platform}"
    local DB_USERNAME="${DB_USERNAME:-}"
    local DB_PASSWORD="${DB_PASSWORD:-}"
    
    # Stop application to prevent new writes
    log "Putting application in maintenance mode..."
    cd "$APP_ROOT"
    php artisan down --message="Database restoration in progress" --retry=60 || true
    
    export PGPASSWORD="$DB_PASSWORD"
    
    # Restore database using pg_restore
    pg_restore \
        -h "$DB_HOST" \
        -U "$DB_USERNAME" \
        -d "$DB_DATABASE" \
        --clean \
        --if-exists \
        --verbose \
        "$backup_file" 2>&1 | tee -a "$BACKUP_LOG"
    
    if [ $? -eq 0 ]; then
        log_success "Database restoration completed"
        
        # Clear application caches
        php artisan cache:clear
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        
        # Bring application back online
        php artisan up
        
        return 0
    else
        log_error "Database restoration failed"
        
        # Attempt to bring application back online
        php artisan up || true
        
        return 1
    fi
}

restore_files() {
    local backup_file="$1"
    
    if [ ! -f "$backup_file" ]; then
        log_error "Backup file not found: $backup_file"
        return 1
    fi
    
    log "Starting files restoration from: $backup_file"
    
    # Extract files to temporary directory
    local temp_dir="${BACKUP_DIR}/temp_restore_${TIMESTAMP}"
    mkdir -p "$temp_dir"
    
    tar -xzf "$backup_file" -C "$temp_dir" 2>&1 | tee -a "$BACKUP_LOG"
    
    if [ $? -eq 0 ]; then
        # Copy restored files to application directory
        cp -r "${temp_dir}/$(basename "$APP_ROOT")/storage/"* "${APP_ROOT}/storage/" 2>&1 | tee -a "$BACKUP_LOG"
        
        # Clean up temporary directory
        rm -rf "$temp_dir"
        
        log_success "Files restoration completed"
        return 0
    else
        log_error "Files restoration failed"
        rm -rf "$temp_dir"
        return 1
    fi
}

# ============================================================================
# MAIN FUNCTIONS
# ============================================================================

run_full_backup() {
    log "=== Starting Full Backup ==="
    
    local success=true
    
    # Run all backups
    backup_database || success=false
    backup_files || success=false
    backup_config || success=false
    
    # Cleanup old backups
    cleanup_old_backups
    
    if [ "$success" = true ]; then
        log_success "=== Full Backup Completed Successfully ==="
        get_backup_status
    else
        log_error "=== Full Backup Completed With Errors ==="
        return 1
    fi
}

show_help() {
    echo "Alumni Platform Backup Management Script"
    echo ""
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  db           - Backup database only"
    echo "  files        - Backup files only"
    echo "  config       - Backup configuration only"
    echo "  full         - Run full backup (database, files, config)"
    echo "  cleanup      - Clean up old backups"
    echo "  status       - Show backup status"
    echo "  verify       - Verify backup integrity"
    echo "  restore-db   - Restore database from backup"
    echo "  restore-files - Restore files from backup"
    echo "  help         - Show this help message"
    echo ""
    echo "Environment Variables:"
    echo "  DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
    echo "  BACKUP_PROVIDER (aws_s3, gcp, azure)"
    echo "  BACKUP_AWS_BUCKET, BACKUP_GCS_BUCKET, BACKUP_AZURE_CONTAINER"
    echo ""
}

# ============================================================================
# SCRIPT ENTRY POINT
# ============================================================================

main() {
    # Create log file
    mkdir -p "$(dirname "$BACKUP_LOG")"
    touch "$BACKUP_LOG"
    
    # Default command
    local command="${1:-full}"
    
    case "$command" in
        db)
            backup_database
            ;;
        files)
            backup_files
            ;;
        config)
            backup_config
            ;;
        full)
            run_full_backup
            ;;
        cleanup)
            cleanup_old_backups
            ;;
        status)
            get_backup_status
            ;;
        verify)
            if [ -n "${2:-}" ]; then
                verify_backup "$2" "${3:-unknown}"
            else
                log_error "Please specify backup file to verify"
                exit 1
            fi
            ;;
        restore-db)
            if [ -n "${2:-}" ]; then
                restore_database "$2"
            else
                log_error "Please specify backup file to restore"
                exit 1
            fi
            ;;
        restore-files)
            if [ -n "${2:-}" ]; then
                restore_files "$2"
            else
                log_error "Please specify backup file to restore"
                exit 1
            fi
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            log_error "Unknown command: $command"
            show_help
            exit 1
            ;;
    esac
}

main "$@"
