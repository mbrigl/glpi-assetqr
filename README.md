# Theme Switch for GLPI

GLPI 11 plugin for switching the GLPI theme.

## Usage
- **Users:** user menu (avatar, top right) → *Theme* → light / dark / system.
  The choice is stored per user on the server. "System" follows the OS setting (`prefers-color-scheme`).
- **Admins:** *Setup → Plugins → Theme Switch* (wrench icon) selects the palette used for light and for dark.
- As long as a user has never used the switch, their normal GLPI palette (*My settings*) applies.

## Carbon palettes
The plugin ships two palettes inspired by the [Carbon Design System](https://carbondesignsystem.com)
(Apache-2.0, not affiliated with IBM): **Carbon Light** ("White") and **Carbon Dark** ("Gray 100").
They are the default light/dark palettes.
- Source: `palettes/carbon_*.scss` (colors only). On install they are copied into GLPI's custom themes
  directory (`GLPI_THEMES_DIR`) and removed again on uninstall. After editing them, reinstall the plugin.
- The Carbon look (IBM Plex Sans, square corners, focus outline, fields) lives in `public/css/themeswitch.css`
  and applies to every palette whose key starts with `carbon_`.
- IBM Plex Sans is bundled under the SIL Open Font License (`public/fonts/ibm-plex-sans/OFL.txt`).

## Development environment
1. Open the folder in VS Code → "Reopen in Container".
2. After the first start: http://localhost:8080 (login `glpi` / `glpi`).
   If port 8080 is taken, put e.g. `GLPI_PORT=8081` into `.devcontainer/.env` and rebuild the container.
3. The plugin lives at `/var/www/glpi/plugins/themeswitch` and is installed and activated automatically
   as soon as a `setup.php` exists.

## Sample data
```bash
sudo -u www-data php .devcontainer/seed.php          # 50 computers (idempotent)
sudo -u www-data php .devcontainer/seed.php 200      # different number
sudo -u www-data php .devcontainer/seed.php --purge  # delete all computers first
```
Sample data is seeded automatically when the container is created; put `GLPI_SEED_DATA=0` into `.devcontainer/.env` to disable it.

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
- `docker-compose.yml`: mount target `/var/www/glpi/plugins/<name>` and image name
- `devcontainer.json`: `workspaceFolder`
- `setup.php` / `hook.php`: function names `plugin_<name>_...` and constants

## Starting from scratch
Delete the volumes (`glpi-data`, `db-data`) and rebuild the container.
