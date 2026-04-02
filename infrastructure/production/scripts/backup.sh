#!/bin/bash
# automated database backup script

BACKUP_DIR="/var/backups/postgres"
mkdir -p "$BACKUP_DIR"

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/alumate_prod_$TIMESTAMP.sql.gz"

echo "Starting database backup: $BACKUP_FILE"

# Run pg_dump from the db container
docker exec alumate_db_prod pg_dump -U "${DB_USERNAME:-alumate_user}" "${DB_DATABASE:-alumate_prod}" | gzip > "$BACKUP_FILE"

# Keep only last 7 days of backups
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +7 -exec rm {} \;

echo "Backup completed and old backups rotated."