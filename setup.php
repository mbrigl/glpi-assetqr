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

define('PLUGIN_THEMESWITCH_VERSION', '0.1.0');
define('PLUGIN_THEMESWITCH_MIN_GLPI', '11.0.0');
define('PLUGIN_THEMESWITCH_MAX_GLPI', '11.0.99');

/**
 * Called on every request while the plugin is active.
 */
function plugin_init_themeswitch(): void
{
    global $PLUGIN_HOOKS;
}

function plugin_version_themeswitch(): array
{
    return [
        'name'         => 'Theme Switch',
        'version'      => PLUGIN_THEMESWITCH_VERSION,
        'author'       => 'Markus Brigl',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/mbrigl/glpi-themeswitch',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_THEMESWITCH_MIN_GLPI,
                'max' => PLUGIN_THEMESWITCH_MAX_GLPI,
            ],
            'php' => ['min' => '8.2'],
        ],
    ];
}

function plugin_themeswitch_check_prerequisites(): bool
{
    return true;
}

function plugin_themeswitch_check_config($verbose = false): bool
{
    return true;
}
