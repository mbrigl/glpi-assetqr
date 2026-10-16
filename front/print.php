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
$job   = is_string($token) && preg_match('/^[0-9a-f]{32}$/', $token) === 1
    ? ($_SESSION[QrCode::PRINT_SESSION_KEY][$token] ?? null)
    : null;
if (!is_array($job) || !is_array($job['items'] ?? null)) {
    throw new NotFoundHttpException();
}

$labels = [];
foreach ($job['items'] as $itemtype => $ids) {
    if (!is_string($itemtype) || !is_array($ids) || !QrCode::isSupported($itemtype) || !($item = getItemForItemtype($itemtype))) {
        continue;
    }
    foreach ($ids as $id) {
        // Same limit as the massive action, in case the session holds more
        if (count($labels) >= QrCode::PRINT_MAX_ITEMS) {
            break 2;
        }
        // Rights may have changed since the massive action: skip what is no longer readable
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !$item->getFromDB($id) || !$item->can($id, READ)) {
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

// Standalone page: only its own script, inline styles and the QR codes as data: images; no framing by other sites
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'unsafe-inline'; img-src data:;"
    . " base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$back = $job['back'] ?? null;

TemplateRenderer::getInstance()->display('@assetqr/print.html.twig', [
    'lang'    => substr($_SESSION['glpilanguage'] ?? 'en', 0, 2),
    'labels'  => $labels,
    'settings' => QrCode::getLabelSettings(),
    // Stored by QrCode::getLocalBackUrl(): always a path inside GLPI
    'back'     => is_string($back) && str_starts_with($back, '/') && !str_starts_with($back, '//') ? $back : $CFG_GLPI['root_doc'] . '/',
    'script'  => $CFG_GLPI['root_doc'] . '/plugins/assetqr/js/print.js?v=' . PLUGIN_ASSETQR_VERSION,
]);
