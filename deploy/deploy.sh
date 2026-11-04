#!/usr/bin/env bash
set -euo pipefail

main() {
  if [[ $EUID -ne 0 ]]; then
    echo "Root sifatida ishga tushiring: sudo erp-deploy [branch]"
    exit 1
  fi

  APP_USER="${APP_USER:-brainup}"
  APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
  DOMAIN="${DOMAIN:-brain-up.uz}"
  BRANCH="${1:-main}"

  local pool
  pool="$(grep -l '^\[erp-brainup\]' /etc/php/*/fpm/pool.d/*.conf 2>/dev/null | head -1 || true)"
  if [[ -n "$pool" ]]; then
    PHP_V="$(cut -d/ -f4 <<<"$pool")"
  else
    PHP_V="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
  fi

  step "Texnik rejim yoqildi"
  as_app "php artisan down --retry=15" || true
  trap 'as_app "php artisan up" >/dev/null 2>&1 || true' EXIT

  step "Yangi kod: origin/${BRANCH}"
  as_app "git fetch --prune origin '${BRANCH}' && git reset --hard 'origin/${BRANCH}'"
  as_app "git log -1 --format='%h %s (%an, %ar)'"

  step "Composer"
  as_app "composer install --no-dev --optimize-autoloader --no-interaction --no-progress"

  step "Frontend build"
  as_app "npm ci --no-audit --no-fund && npm run build"

  step "Migratsiyalar"
  as_app "php artisan migrate --force"

  step "Kesh"
  as_app "php artisan optimize:clear >/dev/null && php artisan optimize"

  step "Qayta ishga tushirish: php${PHP_V}-fpm, queue, Pulse"
  systemctl reload "php${PHP_V}-fpm"
  as_app "php artisan queue:restart"
  as_app "php artisan pulse:restart"

  step "Texnik rejim o'chirildi"
  as_app "php artisan up"
  trap - EXIT

  curl -sS -o /dev/null -w "https://${DOMAIN}/up: %{http_code} in %{time_total}s\n" "https://${DOMAIN}/up"
  printf '\n\033[1;32mDeploy tayyor.\033[0m\n'
}

as_app() { sudo -u "$APP_USER" -H bash -c "cd '$APP_DIR' && $*"; }
step() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }

main "$@"
