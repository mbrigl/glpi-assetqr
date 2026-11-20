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

/* global CFG_GLPI, glpi_ajax_dialog, _ */

// "Print" in the dialog is a plain link to front/print.php, which applies the label settings
$(document).on('click', '.assetqr-qrcode-btn', function () {
    const btn = $(this);
    glpi_ajax_dialog({
        url: `${CFG_GLPI.root_doc}/plugins/assetqr/ajax/qrcode.php`,
        method: 'get',
        params: {
            itemtype: btn.attr('data-itemtype'),
            items_id: btn.attr('data-items-id'),
        },
        // glpi_ajax_dialog inserts the title as HTML: escape the asset name
        title: _.escape(btn.attr('data-title')),
    });
});
