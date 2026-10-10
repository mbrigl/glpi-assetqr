<?php

/**
 * -------------------------------------------------------------------------
 * Theme Switch plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Theme Switch.
 *
 * Theme Switch is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Theme Switch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Theme Switch. If not, see <https://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by Markus Brigl.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/mbrigl/glpi-themeswitch
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Themeswitch\ThemeSwitch;

/**
 * Installation: create tables, initialize rights/configuration.
 */
function plugin_themeswitch_install(): bool
{
    global $DB;

    $migration = new Migration(PLUGIN_THEMESWITCH_VERSION);

    $table = ThemeSwitch::TABLE;
    if (!$DB->tableExists($table)) {
        $charset   = DBConnection::getDefaultCharset();
        $collation = DBConnection::getDefaultCollation();
        $sign      = DBConnection::getDefaultPrimaryKeySignOption();

        $DB->doQuery(
            "CREATE TABLE `$table` (
                `id` int {$sign} NOT NULL AUTO_INCREMENT,
                `users_id` int {$sign} NOT NULL DEFAULT '0',
                `mode` varchar(10) NOT NULL DEFAULT 'system',
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `users_id` (`users_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC"
        );
    }

    // addConfig() keeps existing values on reinstall/update.
    $migration->addConfig(
        [
            'light_palette' => ThemeSwitch::DEFAULT_LIGHT_PALETTE,
            'dark_palette'  => ThemeSwitch::DEFAULT_DARK_PALETTE,
        ],
        ThemeSwitch::CONFIG_CONTEXT
    );

    $migration->executeMigration();

    return true;
}

/**
 * Uninstallation: remove everything again.
 */
function plugin_themeswitch_uninstall(): bool
{
    global $DB;

    $DB->dropTable(ThemeSwitch::TABLE, true);
    Config::deleteConfigurationValues(ThemeSwitch::CONFIG_CONTEXT, ['light_palette', 'dark_palette']);

    return true;
}
