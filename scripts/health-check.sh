#!/bin/bash

# Health Check Script
# Monitors application health and sends alerts

# Configuration
APP_URL="${APP_URL:-http://localhost:8000}"
HEALTH_ENDPOINT="${APP_URL}/api/health"
ALERT_EMAIL="admin@example.com"
LOG_FILE="/var/log/zdravlje-bih/health-check.log"

# Create log directory
mkdir -p "$(dirname ${LOG_FILE})"

# Function to send alert
send_alert() {
    local message="$1"
    echo "[ALERT] $(date): ${message}" >> "${LOG_FILE}"
    # Send email (requires mailutils)
    # echo "${message}" | mail -s "Health Check Alert" "${ALERT_EMAIL}"
    # Or send to Slack
    # curl -X POST -H 'Content-type: application/json' \
    #   --data "{\"text\":\"${message}\"}" \
    #   YOUR_SLACK_WEBHOOK_URL
}

# Check API health
echo "Checking API health at $(date)..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${HEALTH_ENDPOINT}")

if [ "${HTTP_CODE}" -eq 200 ]; then
    echo "[OK] API is healthy (HTTP ${HTTP_CODE})" >> "${LOG_FILE}"
else
    send_alert "API health check failed! HTTP code: ${HTTP_CODE}"
    exit 1
fi

# Check database connection
echo "Checking database connection..."
DB_CHECK=$(cd /var/www/zdravlje-bih/backend && php artisan db:show 2>&1)
if [ $? -eq 0 ]; then
    echo "[OK] Database connection successful" >> "${LOG_FILE}"
else
    send_alert "Database connection failed!"
    exit 1
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "${DISK_USAGE}" -gt 80 ]; then
    send_alert "Disk usage is high: ${DISK_USAGE}%"
fi

# Check memory usage
MEM_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [ "${MEM_USAGE}" -gt 90 ]; then
    send_alert "Memory usage is high: ${MEM_USAGE}%"
fi

echo "Health check completed successfully at $(date)"
