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

use Com\Tecnick\Barcode\Exception as BarcodeException;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Assetqr\QrCode;

global $CFG_GLPI;

Session::checkLoginUser();

// Selection stored by the massive action "Print QR codes"
$token = $_GET['token'] ?? null;
$job   = is_string($token) ? ($_SESSION[QrCode::PRINT_SESSION_KEY][$token] ?? null) : null;
if (!is_array($job)) {
    throw new NotFoundHttpException();
}

$labels = [];
foreach ($job['items'] as $itemtype => $ids) {
    if (!QrCode::isSupported($itemtype) || !($item = getItemForItemtype($itemtype))) {
        continue;
    }
    foreach ($ids as $id) {
        // Rights may have changed since the massive action: skip what is no longer readable
        if (!$item->getFromDB((int) $id) || !$item->can((int) $id, READ)) {
            continue;
        }
        $text  = QrCode::buildText($item);
        $label = ['text' => $text, 'svg' => null, 'error' => null];
        try {
            $label['svg'] = base64_encode(QrCode::generate($text)->getSvgCode());
        } catch (BarcodeException $e) {
            // Data errors of the QR code library, e.g. text too long for a QR code
            $label['error'] = $e->getMessage();
        }
        $labels[] = $label;
    }
}

TemplateRenderer::getInstance()->display('@assetqr/print.html.twig', [
    'lang'    => substr($_SESSION['glpilanguage'] ?? 'en', 0, 2),
    'labels'  => $labels,
    'back'    => $job['back'] ?? $CFG_GLPI['url_base'],
    'script'  => $CFG_GLPI['root_doc'] . '/plugins/assetqr/js/print.js?v=' . PLUGIN_ASSETQR_VERSION,
]);
