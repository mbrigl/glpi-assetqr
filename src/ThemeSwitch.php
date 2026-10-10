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

namespace GlpiPlugin\Themeswitch;

use Config;
use Glpi\UI\ThemeManager;
use Session;

/**
 * Light / dark / system theme switching on top of GLPI palettes.
 *
 * The admin picks one palette for "light" and one for "dark"; every user picks
 * a mode, which is stored server-side in the plugin table.
 */
final class ThemeSwitch
{
    public const MODE_LIGHT  = 'light';
    public const MODE_DARK   = 'dark';
    public const MODE_SYSTEM = 'system';
    public const MODES       = [self::MODE_LIGHT, self::MODE_DARK, self::MODE_SYSTEM];

    public const CONFIG_CONTEXT = 'plugin:themeswitch';
    public const TABLE          = 'glpi_plugin_themeswitch_users';

    /** Cookie in which the browser reports its `prefers-color-scheme` (used for the "system" mode). */
    public const SCHEME_COOKIE = 'glpi_themeswitch_scheme';

    public const DEFAULT_LIGHT_PALETTE = 'auror';
    public const DEFAULT_DARK_PALETTE  = 'darker';

    private const SESSION_KEY = 'plugin_themeswitch';

    /**
     * @return array{light_palette: string, dark_palette: string}
     */
    public static function getConfig(): array
    {
        $values = Config::getConfigurationValues(self::CONFIG_CONTEXT, ['light_palette', 'dark_palette']);
        $themes = ThemeManager::getInstance();

        $light = $values['light_palette'] ?? '';
        $dark  = $values['dark_palette'] ?? '';

        return [
            'light_palette' => $themes->getTheme($light) !== null ? $light : self::DEFAULT_LIGHT_PALETTE,
            'dark_palette'  => $themes->getTheme($dark) !== null ? $dark : self::DEFAULT_DARK_PALETTE,
        ];
    }

    /**
     * Mode chosen by the user, or null if the user never used the switch
     * (in which case the GLPI palette preference stays untouched).
     */
    public static function getUserMode(int $users_id): ?string
    {
        global $DB;

        // The mode is read once per session and user (impersonation changes the user).
        $cache = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_array($cache) && ($cache['users_id'] ?? null) === $users_id) {
            return $cache['mode'];
        }

        $mode = null;
        $row  = $DB->request([
            'SELECT' => ['mode'],
            'FROM'   => self::TABLE,
            'WHERE'  => ['users_id' => $users_id],
            'LIMIT'  => 1,
        ])->current();
        if ($row !== null && in_array($row['mode'], self::MODES, true)) {
            $mode = $row['mode'];
        }

        $_SESSION[self::SESSION_KEY] = ['users_id' => $users_id, 'mode' => $mode];
        return $mode;
    }

    public static function setUserMode(int $users_id, string $mode): bool
    {
        global $DB;

        if (!in_array($mode, self::MODES, true)) {
            return false;
        }

        $DB->updateOrInsert(
            self::TABLE,
            ['mode' => $mode, 'date_mod' => $_SESSION['glpi_currenttime']],
            ['users_id' => $users_id]
        );
        $_SESSION[self::SESSION_KEY] = ['users_id' => $users_id, 'mode' => $mode];

        return true;
    }

    /**
     * Light or dark, after resolving the "system" mode from the browser cookie.
     */
    public static function resolveScheme(string $mode): string
    {
        if ($mode === self::MODE_SYSTEM) {
            return ($_COOKIE[self::SCHEME_COOKIE] ?? '') === self::MODE_DARK ? self::MODE_DARK : self::MODE_LIGHT;
        }
        return $mode === self::MODE_DARK ? self::MODE_DARK : self::MODE_LIGHT;
    }

    public static function getPaletteForScheme(string $scheme): string
    {
        $config = self::getConfig();
        return $scheme === self::MODE_DARK ? $config['dark_palette'] : $config['light_palette'];
    }

    /**
     * Override the session palette so that GLPI renders the page with the right theme
     * straight away (no flash of the wrong theme).
     */
    public static function applyToSession(): void
    {
        $users_id = Session::getLoginUserID();
        if ($users_id === false) {
            return;
        }

        $mode = self::getUserMode((int) $users_id);
        if ($mode === null) {
            return;
        }

        $_SESSION['glpipalette'] = self::getPaletteForScheme(self::resolveScheme($mode));
    }

    /**
     * Data needed by the switch in the browser.
     *
     * @return array<string, mixed>
     */
    public static function getClientConfig(): array
    {
        global $CFG_GLPI;

        $config   = self::getConfig();
        $themes   = ThemeManager::getInstance();
        $users_id = Session::getLoginUserID();

        return [
            'mode'     => $users_id !== false ? self::getUserMode((int) $users_id) : null,
            'cookie'   => self::SCHEME_COOKIE,
            'url'      => $CFG_GLPI['root_doc'] . '/plugins/themeswitch/ajax/mode.php',
            'palettes' => [
                self::MODE_LIGHT => [
                    'key'  => $config['light_palette'],
                    'dark' => $themes->getTheme($config['light_palette'])?->isDarkTheme() ?? false,
                ],
                self::MODE_DARK => [
                    'key'  => $config['dark_palette'],
                    'dark' => $themes->getTheme($config['dark_palette'])?->isDarkTheme() ?? true,
                ],
            ],
            'labels'   => [
                'title'           => __('Theme', 'themeswitch'),
                'error'           => __('The theme could not be saved.', 'themeswitch'),
                self::MODE_LIGHT  => __('Light', 'themeswitch'),
                self::MODE_DARK   => __('Dark', 'themeswitch'),
                self::MODE_SYSTEM => __('System', 'themeswitch'),
            ],
        ];
    }
}
