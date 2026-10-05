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

namespace GlpiPlugin\Assetqr;

use CommonDBTM;
use Com\Tecnick\Barcode\Barcode;
use Config;
use Dropdown;
use Entity;
use Html;

/**
 * Generates QR codes for assets from a configurable text template.
 *
 * Template placeholders: {column} for every column of the asset.
 * Foreign keys (e.g. {manufacturers_id}, {users_id}) are resolved to their display name.
 * Additionally: {id}, {itemtype}, {url}, {entity}.
 */
class QrCode
{
    public const CONFIG_CONTEXT = 'plugin:assetqr';

    /** Placeholders that do not come from a table column. */
    public const SPECIAL_PLACEHOLDERS = ['id', 'itemtype', 'url', 'entity'];

    /** Default template, with labels in the current user's language. */
    public static function getDefaultTemplate(): string
    {
        return implode("\n", [
            '{itemtype}: {name}',
            __('Serial number', 'assetqr') . ': {serial}',
            __('Inventory number', 'assetqr') . ': {otherserial}',
            '{url}',
        ]);
    }

    public static function isSupported(CommonDBTM|string $item): bool
    {
        global $CFG_GLPI;

        $itemtype = is_string($item) ? $item : $item::class;
        return in_array($itemtype, $CFG_GLPI['asset_types'] ?? [], true);
    }

    public static function getConfigKey(string $itemtype): string
    {
        return 'template_' . $itemtype;
    }

    /** Template of the asset type, falling back to the default template. */
    public static function getTemplate(string $itemtype): string
    {
        $config = Config::getConfigurationValues(self::CONFIG_CONTEXT);

        $template = trim($config[self::getConfigKey($itemtype)] ?? '');
        if ($template === '') {
            $template = trim($config['template_default'] ?? '');
        }
        return $template !== '' ? $template : self::getDefaultTemplate();
    }

    /** Replaces the template placeholders with the asset values. */
    public static function buildText(CommonDBTM $item, ?string $template = null): string
    {
        $template ??= self::getTemplate($item::class);

        $text = preg_replace_callback(
            '/\{([a-z0-9_]+)\}/i',
            fn(array $m) => self::resolvePlaceholder($item, strtolower($m[1])),
            $template,
        );

        // Do not encode lines that are empty after substitution
        $lines = array_filter(
            array_map('rtrim', preg_split('/\R/', $text)),
            fn(string $line) => $line !== '',
        );
        return implode("\n", $lines);
    }

    public static function resolvePlaceholder(CommonDBTM $item, string $key): string
    {
        global $CFG_GLPI;

        switch ($key) {
            case 'itemtype':
                return $item::getTypeName(1);
            case 'url':
                return $CFG_GLPI['url_base'] . $item::getFormURLWithID($item->getID(), false);
            case 'entity':
                return Dropdown::getDropdownName(Entity::getTable(), (int) ($item->fields['entities_id'] ?? 0), tooltip: false);
        }

        if (!array_key_exists($key, $item->fields)) {
            return '';
        }

        $value = $item->fields[$key];
        if ($value === null) {
            return '';
        }

        if ($key !== 'id' && isForeignKeyField($key)) {
            if ((int) $value <= 0) {
                return '';
            }
            if (str_starts_with($key, 'users_id')) {
                return getUserName((int) $value);
            }
            $table = getTableNameForForeignKeyField($key);
            $name  = $table !== '' ? Dropdown::getDropdownName($table, (int) $value, tooltip: false) : '';
            return html_entity_decode(trim($name), ENT_QUOTES);
        }

        return (string) $value;
    }

    /**
     * Available placeholders for an asset type.
     *
     * @return string[]
     */
    public static function getPlaceholders(string $itemtype): array
    {
        global $DB;

        $columns = array_keys($DB->listFields($itemtype::getTable()));
        return array_values(array_unique(array_merge(self::SPECIAL_PLACEHOLDERS, $columns)));
    }

    /**
     * @return \Com\Tecnick\Barcode\Model
     */
    public static function generate(string $text)
    {
        return (new Barcode())
            ->getBarcodeObj('QRCODE,M', $text, -6, -6, 'black', [4, 4, 4, 4])
            ->setBackgroundColor('white');
    }

    /**
     * Hook post_item_form: button below the form of every asset.
     */
    public static function postItemForm(array $params): void
    {
        $item = $params['item'] ?? null;
        if (!$item instanceof CommonDBTM || $item->isNewItem() || !self::isSupported($item)) {
            return;
        }

        $label = __('QR code', 'assetqr');
        echo sprintf(
            '<div class="card-body mx-n2 border-top d-flex flex-row-reverse py-2">'
            . '<button type="button" class="btn btn-outline-secondary assetqr-qrcode-btn"'
            . ' data-itemtype="%s" data-items-id="%d" data-title="%s">'
            . '<i class="ti ti-qrcode"></i><span>%s</span></button></div>',
            Html::entities_deep($item::class),
            $item->getID(),
            Html::entities_deep(sprintf('%s – %s', $label, $item->getNameID())),
            Html::entities_deep($label),
        );
    }
}
