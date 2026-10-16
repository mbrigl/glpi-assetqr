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
use MassiveAction;

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

    /** Maximum length of a template; a QR code holds about 2300 characters at most. */
    public const TEMPLATE_MAX_LENGTH = 2000;

    /** Name of the massive action that prints the QR codes of the selected assets. */
    public const MASSIVE_ACTION_PRINT = 'print';

    /** Session key of the selection handed over to the print page. */
    public const PRINT_SESSION_KEY = 'plugin_assetqr_print';

    /** Maximum number of assets printed at once; every QR code is generated when the page is rendered. */
    public const PRINT_MAX_ITEMS = 500;

    /**
     * Numeric label settings of the print page: config key => [default, min, max].
     * Sizes in mm, font size in pt; a height of 0 means "as high as the content".
     */
    public const LABEL_NUMBERS = [
        'label_width'     => [60, 15, 200],
        'label_height'    => [0, 0, 300],
        'label_qr_size'   => [40, 8, 150],
        'label_font_size' => [8, 4, 16],
        'label_gap'       => [4, 0, 30],
    ];

    /** Position of the text relative to the QR code; the first one is the default. */
    public const LABEL_LAYOUTS = ['below', 'beside', 'none'];

    /** Columns that must never be encoded, whatever the template says. */
    private const SENSITIVE_COLUMN_PATTERN = '/pass|secret|token|private|api_?key|cookie|hash|salt|credential/i';

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

    /**
     * Label settings of the print page, falling back to the defaults for missing or invalid values.
     *
     * @return array{label_width: int, label_height: int, label_qr_size: int, label_font_size: int, label_gap: int, label_layout: string, label_border: bool}
     */
    public static function getLabelSettings(?array $values = null): array
    {
        $values ??= Config::getConfigurationValues(self::CONFIG_CONTEXT);

        $settings = [];
        foreach (self::LABEL_NUMBERS as $key => [$default]) {
            $settings[$key] = self::parseLabelNumber($key, $values[$key] ?? null) ?? $default;
        }
        $layout = $values['label_layout'] ?? null;
        $settings['label_layout'] = in_array($layout, self::LABEL_LAYOUTS, true) ? $layout : self::LABEL_LAYOUTS[0];
        $settings['label_border'] = (bool) ($values['label_border'] ?? true);
        return $settings;
    }

    /** Integer value of a numeric label setting, or null when it is not a number within its range. */
    public static function parseLabelNumber(string $key, mixed $value): ?int
    {
        [, $min, $max] = self::LABEL_NUMBERS[$key];
        $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        return $value === false ? null : $value;
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
                $name = Dropdown::getDropdownName(Entity::getTable(), (int) ($item->fields['entities_id'] ?? 0), tooltip: false);
                return html_entity_decode(trim($name), ENT_QUOTES);
        }

        if (!self::isAllowedColumn($key) || !array_key_exists($key, $item->fields)) {
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

        $columns = array_filter(array_keys($DB->listFields($itemtype::getTable())), self::isAllowedColumn(...));
        return array_values(array_unique(array_merge(self::SPECIAL_PLACEHOLDERS, $columns)));
    }

    private static function isAllowedColumn(string $column): bool
    {
        return preg_match(self::SENSITIVE_COLUMN_PATTERN, $column) !== 1;
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
            htmlescape($item::class),
            $item->getID(),
            htmlescape(sprintf('%s – %s', $label, $item->getNameID())),
            htmlescape($label),
        );
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma): bool
    {
        if ($ma->getAction() !== self::MASSIVE_ACTION_PRINT) {
            return false;
        }
        echo Html::submit(__('Print', 'assetqr'), [
            'name'  => 'massiveaction',
            'icon'  => 'ti ti-printer',
            'class' => 'btn btn-sm btn-primary',
        ]);
        return true;
    }

    /**
     * Hands the selection over to the print page via the session; the page redirects there afterwards.
     */
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids): void
    {
        if ($ma->getAction() !== self::MASSIVE_ACTION_PRINT) {
            return;
        }

        $selected = array_sum(array_map('count', $ma->getItems()));
        if ($selected > self::PRINT_MAX_ITEMS) {
            $ma->addMessage(sprintf(
                __('Too many assets selected: at most %d QR codes can be printed at once.', 'assetqr'),
                self::PRINT_MAX_ITEMS,
            ));
            $ma->itemDone($item::class, array_map('intval', $ids), MassiveAction::ACTION_KO);
            return;
        }

        $readable = $denied = [];
        foreach ($ids as $id) {
            if (self::isSupported($item) && $item->can($id, READ)) {
                $readable[] = (int) $id;
            } else {
                $denied[] = (int) $id;
            }
        }

        // The whole selection (all itemtypes) is stored on every call: this way nothing is lost when
        // GLPI reloads the page during processing. The print page checks the rights again.
        $token = bin2hex(random_bytes(16));
        $_SESSION[self::PRINT_SESSION_KEY] = [
            $token => [
                'items' => $ma->getItems(),
                'back'  => self::getLocalBackUrl(),
            ],
        ];
        $ma->setRedirect(self::getPrintUrl($token));

        // Last: itemDone() may reload the page when processing takes too long
        if ($denied !== []) {
            $ma->itemDone($item::class, $denied, MassiveAction::ACTION_NORIGHT);
        }
        if ($readable !== []) {
            $ma->itemDone($item::class, $readable, MassiveAction::ACTION_OK);
        }
    }

    /**
     * Previous page as a path inside GLPI (never another host), for the "Back" link of the print page.
     */
    public static function getLocalBackUrl(): string
    {
        global $CFG_GLPI;

        $root    = $CFG_GLPI['root_doc'];
        $referer = Html::getRefererUrl() ?? '';
        $path    = parse_url($referer, PHP_URL_PATH);
        $query   = parse_url($referer, PHP_URL_QUERY);

        if (!is_string($path) || !str_starts_with($path, $root . '/') || str_starts_with($path, '//')) {
            return $root . '/';
        }
        return $path . (is_string($query) && $query !== '' ? '?' . $query : '');
    }

    public static function getPrintUrl(string $token): string
    {
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . '/plugins/assetqr/front/print.php?token=' . urlencode($token);
    }
}
