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

/* global CFG_GLPI, glpi_ajax_dialog */

$(document).on('click', '.assetqr-qrcode-btn', function () {
    const btn = $(this);
    glpi_ajax_dialog({
        url: `${CFG_GLPI.root_doc}/plugins/assetqr/ajax/qrcode.php`,
        method: 'get',
        params: {
            itemtype: btn.data('itemtype'),
            items_id: btn.data('items-id'),
        },
        title: btn.data('title'),
    });
});

$(document).on('click', '.assetqr-qrcode-print', function () {
    const container = $(this).closest('.assetqr-qrcode');
    const win = window.open('', '_blank', 'width=500,height=600');
    if (!win) {
        return;
    }
    win.document.write(`<!doctype html><html><head><title></title>
        <style>body{font-family:sans-serif;text-align:center;margin:2em}
        svg{width:60mm;height:auto}pre{text-align:left;display:inline-block;white-space:pre-wrap}</style>
        </head><body>${container.find('.assetqr-qrcode-image').html()}
        <br><pre></pre></body></html>`);
    win.document.title = $(this).data('title');
    win.document.querySelector('pre').textContent = container.find('pre').text();
    win.document.close();
    win.focus();
    win.print();
});
