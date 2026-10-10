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

Session::checkLoginUser();

header('Content-Type: application/json; charset=UTF-8');

$mode = $_POST['mode'] ?? '';
if (!ThemeSwitch::setUserMode((int) Session::getLoginUserID(), $mode)) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    return;
}

ThemeSwitch::applyToSession();

echo json_encode(['success' => true, 'mode' => $mode]);
