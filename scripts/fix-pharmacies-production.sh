#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

cd "${BACKEND_DIR}"

echo "==> 1/6 Clearing Laravel caches"
php artisan optimize:clear

echo "==> 2/6 Running migrations"
php artisan migrate --force

echo "==> 3/6 Ensuring pharmacy routes exist"
php artisan route:list --path=apoteke

echo "==> 4/6 Seeding pharmacy demo data"
php artisan db:seed --class=Database\\Seeders\\PharmacyDemoSeeder --force --no-interaction

echo "==> 5/6 Rebuilding runtime caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> 6/6 Done"
echo "Run these checks after deploy:"
echo "  curl -i https://api.wizmedik.com/api/apoteke"
echo "  curl -i https://api.wizmedik.com/api/apoteke/dezurne?grad=sarajevo"
