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

use Glpi\Plugin\Hooks;
use GlpiPlugin\Themeswitch\ThemeSwitch;

define('PLUGIN_THEMESWITCH_VERSION', '0.1.0');
define('PLUGIN_THEMESWITCH_MIN_GLPI', '11.0.0');
define('PLUGIN_THEMESWITCH_MAX_GLPI', '11.0.99');

/**
 * Called on every request while the plugin is active.
 */
function plugin_init_themeswitch(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['themeswitch'] = true;

    if (!Plugin::isPluginActive('themeswitch')) {
        return;
    }

    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['themeswitch'] = 'front/config.form.php';
    }

    if (Session::getLoginUserID() === false) {
        return;
    }

    ThemeSwitch::applyToSession();

    $PLUGIN_HOOKS[Hooks::ADD_CSS]['themeswitch']        = 'css/themeswitch.css';
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['themeswitch'] = 'js/themeswitch.js';
    $PLUGIN_HOOKS[Hooks::ADD_HEADER_TAG]['themeswitch'] = [
        [
            'tag'        => 'meta',
            'properties' => [
                'name'    => 'glpi-plugin-themeswitch',
                'content' => json_encode(ThemeSwitch::getClientConfig(), JSON_THROW_ON_ERROR),
            ],
        ],
    ];
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
