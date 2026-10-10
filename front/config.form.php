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

use Glpi\Application\View\TemplateRenderer;
use Glpi\UI\ThemeManager;
use GlpiPlugin\Themeswitch\ThemeSwitch;

Session::checkRight('config', UPDATE);

$themes = ThemeManager::getInstance();

if (isset($_POST['update'])) {
    $values = [];
    foreach (['light_palette', 'dark_palette'] as $name) {
        $key = $_POST[$name] ?? '';
        if ($themes->getTheme($key) !== null) {
            $values[$name] = $key;
        }
    }
    Config::setConfigurationValues(ThemeSwitch::CONFIG_CONTEXT, $values);
    Session::addMessageAfterRedirect(__s('Configuration updated', 'themeswitch'));
    Html::back();
}

$palettes = [];
foreach ($themes->getAllThemes() as $theme) {
    $palettes[$theme->getKey()] = $theme->getName() . ($theme->isDarkTheme() ? ' (' . __('dark', 'themeswitch') . ')' : '');
}
asort($palettes);

Html::header(__('Theme Switch', 'themeswitch'), '', 'config', 'plugin');
TemplateRenderer::getInstance()->display('@themeswitch/config.html.twig', [
    'config'   => ThemeSwitch::getConfig(),
    'palettes' => $palettes,
]);
Html::footer();
