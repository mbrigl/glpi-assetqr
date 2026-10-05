<?php

/**
 * -------------------------------------------------------------------------
 * Asset QR Codes plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Asset QR Codes.
 *
 * Asset QR Codes is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Asset QR Codes is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Asset QR Codes. If not, see <https://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by Markus Brigl.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/mbrigl/glpi-assetqr
 * -------------------------------------------------------------------------
 */

define('PLUGIN_ASSETQR_VERSION', '0.1.0');
define('PLUGIN_ASSETQR_MIN_GLPI', '11.0.0');
define('PLUGIN_ASSETQR_MAX_GLPI', '11.0.99');

/**
 * Called on every request while the plugin is active.
 */
function plugin_init_assetqr(): void
{
    global $PLUGIN_HOOKS;
}

function plugin_version_assetqr(): array
{
    return [
        'name'         => 'Asset QR Codes',
        'version'      => PLUGIN_ASSETQR_VERSION,
        'author'       => 'Markus Brigl',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/mbrigl/glpi-assetqr',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_ASSETQR_MIN_GLPI,
                'max' => PLUGIN_ASSETQR_MAX_GLPI,
            ],
            'php' => ['min' => '8.2'],
        ],
    ];
}

function plugin_assetqr_check_prerequisites(): bool
{
    return true;
}

function plugin_assetqr_check_config($verbose = false): bool
{
    return true;
}
