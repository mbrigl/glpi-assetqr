#!/usr/bin/env bash
set -euo pipefail

GLPI_DIR=/var/www/glpi
GLPI_URL="http://localhost:${GLPI_PORT:-8080}"
PLUGIN_NAME="$(basename "$PWD")"
DB_ARGS=(--db-host=db --db-name=glpi --db-user=glpi --db-password=glpi)

as_www() { sudo -u www-data php "$@"; }
console() { as_www "$GLPI_DIR/bin/console" "$@" --no-interaction; }

echo ">> Waiting for MariaDB ..."
until mysqladmin ping -h db -u glpi -pglpi --silent 2>/dev/null; do sleep 2; done

if [ -f /var/lib/glpi/config/config_db.php ]; then
  echo ">> GLPI is already installed."
else
  echo ">> Installing GLPI database ..."
  console db:install "${DB_ARGS[@]}" --default-language=de_DE
  console config:set url_base "$GLPI_URL" || true
fi

if [ -f composer.json ]; then
  echo ">> Running composer install in plugin ..."
  composer install --no-interaction
fi

if [ -f setup.php ]; then
  echo ">> Installing and activating plugin '$PLUGIN_NAME' ..."
  console plugin:install --username=glpi "$PLUGIN_NAME" || true
  console plugin:activate "$PLUGIN_NAME" || true
fi

if [ "${GLPI_SEED_DATA:-1}" = "1" ]; then
  echo ">> Seeding sample data ..."
  as_www .devcontainer/seed.php || true
fi

cat <<MSG

============================================================
 GLPI is running at $GLPI_URL
 Login: glpi / glpi   (Admin)
 GLPI console: sudo -u www-data php $GLPI_DIR/bin/console
============================================================
MSG
