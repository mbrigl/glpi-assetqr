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
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Assetqr\QrCode;

Session::checkLoginUser();

$itemtype = $_GET['itemtype'] ?? null;
$items_id = filter_var($_GET['items_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!is_string($itemtype) || $items_id === false || !QrCode::isSupported($itemtype)) {
    throw new BadRequestHttpException();
}

// Same response for "does not exist" and "not allowed", so IDs cannot be probed
$item = getItemForItemtype($itemtype);
if (!$item || !$item->getFromDB($items_id) || !$item->can($items_id, READ)) {
    throw new NotFoundHttpException();
}

$text  = QrCode::buildText($item);
$error = null;
$svg   = $png = null;
try {
    $qrcode = QrCode::generate($text);
    $svg    = base64_encode($qrcode->getSvgCode());
    $png    = base64_encode($qrcode->getPngData());
} catch (BarcodeException $e) {
    // Data errors of the QR code library, e.g. text too long for a QR code
    $error = $e->getMessage();
}

TemplateRenderer::getInstance()->display('@assetqr/qrcode.html.twig', [
    'text'     => $text,
    'svg'      => $svg,
    'png'      => $png,
    'error'    => $error,
    'filename' => preg_replace('/[^A-Za-z0-9._-]+/', '_', $item->getName() ?: ($item::getType() . '_' . $items_id)) . '.png',
]);
