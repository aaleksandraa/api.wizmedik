#!/bin/bash

# Database Restore Script
# Usage: ./restore-database.sh <backup_file>

if [ -z "$1" ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    echo "Example: $0 /var/backups/zdravlje-bih/db_backup_20260118_120000.sql.gz"
    exit 1
fi

BACKUP_FILE="$1"
DB_NAME="${DB_DATABASE}"
DB_USER="${DB_USERNAME}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"

# Check if backup file exists
if [ ! -f "${BACKUP_FILE}" ]; then
    echo "ERROR: Backup file not found: ${BACKUP_FILE}"
    exit 1
fi

echo "WARNING: This will restore database from backup and overwrite current data!"
echo "Backup file: ${BACKUP_FILE}"
echo "Database: ${DB_NAME}"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "${CONFIRM}" != "yes" ]; then
    echo "Restore cancelled"
    exit 0
fi

echo "Starting database restore at $(date)"

# Drop existing database (optional, be careful!)
# PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -c "DROP DATABASE IF EXISTS ${DB_NAME};"
# PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -c "CREATE DATABASE ${DB_NAME};"

# Restore from backup
gunzip -c "${BACKUP_FILE}" | PGPASSWORD="${DB_PASSWORD}" psql \
    -h "${DB_HOST}" \
    -p "${DB_PORT}" \
    -U "${DB_USER}" \
    -d "${DB_NAME}"

if [ $? -eq 0 ]; then
    echo "Database restored successfully at $(date)"
else
    echo "ERROR: Database restore failed!" >&2
    exit 1
fi
