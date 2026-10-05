#!/usr/bin/env bash
set -euo pipefail

GLPI_DIR=/var/www/glpi
PLUGIN_NAME="$(basename "$PWD")"

console() { sudo -u www-data php "$GLPI_DIR/bin/console" "$@"; }

echo ">> Waiting for MariaDB ..."
until mysqladmin ping -h db -u glpi -pglpi --silent 2>/dev/null; do sleep 2; done

if [ ! -f /var/lib/glpi/config/config_db.php ]; then
  echo ">> Installing GLPI database ..."
  console db:install \
    --db-host=db --db-name=glpi --db-user=glpi --db-password=glpi \
    --default-language=de_DE --no-interaction
  console config:set url_base "http://localhost:8080" --no-interaction || true
else
  echo ">> GLPI is already installed."
fi

if [ -f composer.json ]; then
  echo ">> Running composer install in plugin ..."
  composer install --no-interaction
fi

if [ -f setup.php ]; then
  echo ">> Installing and activating plugin '$PLUGIN_NAME' ..."
  console plugin:install --username=glpi "$PLUGIN_NAME" --no-interaction || true
  console plugin:activate "$PLUGIN_NAME" --no-interaction || true
fi

if [ "${GLPI_SEED_DATA:-0}" = "1" ]; then
  echo ">> Seeding sample data ..."
  sudo -u www-data php "$PWD/.devcontainer/seed.php" || true
fi

cat <<MSG

============================================================
 GLPI is running at http://localhost:8080
 Login: glpi / glpi   (Admin)
 GLPI console: sudo -u www-data php /var/www/glpi/bin/console
============================================================
MSG
