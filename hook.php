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

use GlpiPlugin\Assetqr\QrCode;

/**
 * Installation: create tables, initialize rights/configuration.
 */
function plugin_assetqr_install(): bool
{
    $config = Config::getConfigurationValues(QrCode::CONFIG_CONTEXT, ['template_default']);
    if (!isset($config['template_default'])) {
        Config::setConfigurationValues(QrCode::CONFIG_CONTEXT, ['template_default' => QrCode::getDefaultTemplate()]);
    }
    return true;
}

/**
 * Massive actions added to the lists of an itemtype.
 */
function plugin_assetqr_MassiveActions(string $itemtype): array
{
    if (!QrCode::isSupported($itemtype)) {
        return [];
    }
    return [
        QrCode::class . MassiveAction::CLASS_ACTION_SEPARATOR . QrCode::MASSIVE_ACTION_PRINT
            => '<i class="ti ti-qrcode"></i>' . __s('Print QR codes', 'assetqr'),
    ];
}

/**
 * Uninstallation: remove everything again.
 */
function plugin_assetqr_uninstall(): bool
{
    $config = new Config();
    $config->deleteByCriteria(['context' => QrCode::CONFIG_CONTEXT]);
    return true;
}
