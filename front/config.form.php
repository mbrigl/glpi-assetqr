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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Assetqr\QrCode;

global $CFG_GLPI;

Session::checkRight('config', UPDATE);

$form_url  = $CFG_GLPI['root_doc'] . '/plugins/assetqr/front/config.form.php';
$itemtypes = array_values(array_filter(
    $CFG_GLPI['asset_types'],
    fn(string $itemtype) => getItemForItemtype($itemtype) !== false,
));

if (isset($_POST['update'])) {
    $posted    = is_array($_POST['templates'] ?? null) ? $_POST['templates'] : [];
    $as_string = static fn(mixed $value): string => is_string($value) ? trim($value) : '';

    // Templates are keyed by itemtype; anything not in the list of asset types is ignored
    $values = ['template_default' => $as_string($_POST['template_default'] ?? '')];
    foreach ($itemtypes as $itemtype) {
        $values[QrCode::getConfigKey($itemtype)] = $as_string($posted[$itemtype] ?? '');
    }

    $too_long = array_filter($values, fn(string $value) => mb_strlen($value) > QrCode::TEMPLATE_MAX_LENGTH);
    if ($too_long !== []) {
        Session::addMessageAfterRedirect(
            sprintf(__('A template must not be longer than %d characters.', 'assetqr'), QrCode::TEMPLATE_MAX_LENGTH),
            false,
            ERROR,
        );
    } else {
        Config::setConfigurationValues(QrCode::CONFIG_CONTEXT, $values);
        Session::addMessageAfterRedirect(__('Configuration updated successfully'));
    }
    Html::redirect($form_url);
}

$config = Config::getConfigurationValues(QrCode::CONFIG_CONTEXT);

$types = [];
foreach ($itemtypes as $itemtype) {
    $types[] = [
        'itemtype'     => $itemtype,
        'label'        => $itemtype::getTypeName(1),
        'template'     => $config[QrCode::getConfigKey($itemtype)] ?? '',
        'placeholders' => QrCode::getPlaceholders($itemtype),
    ];
}

Html::header(__('QR code', 'assetqr'), '', 'config', 'plugin');
TemplateRenderer::getInstance()->display('@assetqr/config.html.twig', [
    'action'           => $form_url,
    'max_length'       => QrCode::TEMPLATE_MAX_LENGTH,
    'template_default' => $config['template_default'] ?? QrCode::getDefaultTemplate(),
    'types'            => $types,
]);
Html::footer();
