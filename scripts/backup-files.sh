#!/bin/bash

# File Backup Script
# Backs up uploaded files and important directories

# Configuration
BACKUP_DIR="/var/backups/zdravlje-bih"
APP_DIR="/var/www/zdravlje-bih"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/files_backup_${TIMESTAMP}.tar.gz"
RETENTION_DAYS=30

# Create backup directory
mkdir -p "${BACKUP_DIR}"

echo "Starting file backup at $(date)"

# Backup important directories
tar -czf "${BACKUP_FILE}" \
    -C "${APP_DIR}" \
    backend/storage/app/public \
    backend/.env \
    backend/config \
    .env 2>/dev/null

# Check if backup was successful
if [ $? -eq 0 ]; then
    echo "File backup completed successfully: ${BACKUP_FILE}"

    # Get file size
    SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
    echo "Backup size: ${SIZE}"

    # Delete old backups
    find "${BACKUP_DIR}" -name "files_backup_*.tar.gz" -mtime +${RETENTION_DAYS} -delete
    echo "Old file backups cleaned up"

    # Optional: Upload to S3
    # aws s3 cp "${BACKUP_FILE}" s3://your-bucket/backups/

else
    echo "ERROR: File backup failed!" >&2
    exit 1
fi

echo "File backup completed at $(date)"
