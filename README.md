# Asset QR Codes for GLPI

GLPI 11 plugin that adds a QR code button to every asset. The encoded text is built from a
configurable template of asset fields.

## Feature: QR codes for assets

Every asset form (computers, monitors, printers, … including custom asset types) gets a **QR code** button below the form.
It opens a dialog with the QR code, the encoded text, and "Download PNG" / "Print" actions.

The encoded text is a template configured under *Setup → Plugins → Asset QR Codes* (gear icon), globally and optionally per asset type:

```
{itemtype}: {name}
Serial number: {serial}
Manufacturer: {manufacturers_id}
{url}
```

- `{column}`: any column of the asset; foreign keys (`*_id`) are resolved to their names
- `{id}`, `{itemtype}`, `{url}`, `{entity}`: extra values
- Lines that are empty after substitution are dropped

## Development environment
1. Open the folder in VS Code → "Reopen in Container".
2. After the first start: http://localhost:8080 (login `glpi` / `glpi`).
3. The plugin lives at `/var/www/glpi/plugins/assetqr` and is installed and activated automatically.

## Sample data
```bash
sudo -u www-data php .devcontainer/seed.php          # 50 computers (idempotent)
sudo -u www-data php .devcontainer/seed.php 200      # different number
sudo -u www-data php .devcontainer/seed.php --purge  # delete all computers first
```
To seed automatically when the container is created, set `GLPI_SEED_DATA=1` in `docker-compose.yml`.

## Useful commands
```bash
sudo -u www-data php /var/www/glpi/bin/console plugin:list
sudo -u www-data php /var/www/glpi/bin/console cache:clear
tail -f /var/log/glpi/php-errors.log
```

## Debugging
Xdebug only starts on trigger: use the "Xdebug Helper" browser extension
or append `?XDEBUG_TRIGGER=1` to the URL, then start "Xdebug: Listen" in VS Code.

## Renaming the plugin
The plugin name (lowercase letters/digits only) must be identical everywhere:
- `docker-compose.yml`: mount target `/var/www/glpi/plugins/<name>`
- `devcontainer.json`: `workspaceFolder`
- `setup.php` / `hook.php`: function names `plugin_<name>_...` and constants

## Starting from scratch
Delete the volumes (`glpi-data`, `db-data`) and rebuild the container.
