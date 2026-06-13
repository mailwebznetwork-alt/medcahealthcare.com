#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ "${APP_ENV:-}" == "production" ]] || grep -q '^APP_ENV=production' .env 2>/dev/null; then
  echo "Refusing to run tests while APP_ENV=production on this server."
  echo "Run tests on a staging machine or local dev environment."
  exit 1
fi

php artisan config:clear --ansi
php artisan test "$@"
