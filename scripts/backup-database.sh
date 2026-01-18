#!/bin/bash

# Database Backup Script
# Runs daily via cron to backup PostgreSQL database

# Configuration
BACKUP_DIR="/var/backups/zdravlje-bih"
DB_NAME="${DB_DATABASE}"
DB_USER="${DB_USERNAME}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/db_backup_${TIMESTAMP}.sql.gz"
RETENTION_DAYS=30

# Create backup directory if it doesn't exist
mkdir -p "${BACKUP_DIR}"

# Perform backup
echo "Starting database backup at $(date)"
PGPASSWORD="${DB_PASSWORD}" pg_dump \
    -h "${DB_HOST}" \
    -p "${DB_PORT}" \
    -U "${DB_USER}" \
    -d "${DB_NAME}" \
    --no-owner \
    --no-acl \
    | gzip > "${BACKUP_FILE}"

# Check if backup was successful
if [ $? -eq 0 ]; then
    echo "Backup completed successfully: ${BACKUP_FILE}"

    # Get file size
    SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
    echo "Backup size: ${SIZE}"

    # Delete old backups (older than RETENTION_DAYS)
    find "${BACKUP_DIR}" -name "db_backup_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
    echo "Old backups cleaned up (retention: ${RETENTION_DAYS} days)"

    # Optional: Upload to S3 or remote storage
    # aws s3 cp "${BACKUP_FILE}" s3://your-bucket/backups/

else
    echo "ERROR: Backup failed!" >&2
    # Send alert email
    # echo "Database backup failed at $(date)" | mail -s "ALERT: Backup Failed" admin@example.com
    exit 1
fi

echo "Backup process completed at $(date)"
