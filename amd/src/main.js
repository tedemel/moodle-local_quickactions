// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Quick Actions main entry point.
 *
 * @module     local_quickactions/main
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {init as initFab} from 'local_quickactions/fab';
import {init as initSelection} from 'local_quickactions/selection';
import {init as initActions} from 'local_quickactions/actions';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const UNDO_STORAGE_KEY = 'local_quickactions:lastundo';
const UNDO_TTL_MS = 30 * 60 * 1000; // 30 min, matches server-side TTL.

let config = null;

export const init = (cfg) => {
    config = cfg;

    const root = document.getElementById('local-quickactions-root');
    if (!root) {
        return;
    }
    root.hidden = false;

    initFab(config);
    initSelection(config);
    initActions(config);

    // Auto-reopen panel after a successful apply that reloaded the page.
    try {
        if (sessionStorage.getItem('local_quickactions:autoopen') === '1') {
            sessionStorage.removeItem('local_quickactions:autoopen');
            requestAnimationFrame(() => {
                document.querySelector('[data-region="qa-fab"]')?.click();
            });
        }
    } catch (e) {
        // Storage disabled; ignore.
    }

    // If a recent action stored an undo handle, surface a notification with an Undo link.
    showUndoNotificationIfPending(config);
};

const showUndoNotificationIfPending = async(config) => {
    let raw;
    try {
        raw = sessionStorage.getItem(UNDO_STORAGE_KEY);
    } catch (e) {
        return;
    }
    if (!raw) {
        return;
    }
    let data;
    try {
        data = JSON.parse(raw);
    } catch (e) {
        sessionStorage.removeItem(UNDO_STORAGE_KEY);
        return;
    }
    if (!data.undoid || data.courseid !== config.courseid) {
        return;
    }
    if (Date.now() - data.ts > UNDO_TTL_MS) {
        sessionStorage.removeItem(UNDO_STORAGE_KEY);
        return;
    }
    sessionStorage.removeItem(UNDO_STORAGE_KEY);

    const label = await getString('undo_button', 'local_quickactions');
    const successCount = data.successcount ?? 0;
    const headline = await getString('result_success', 'local_quickactions', successCount);
    const id = 'local-quickactions-undo-toast';
    document.getElementById(id)?.remove();
    const el = document.createElement('div');
    el.id = id;
    el.className = 'alert alert-info local-quickactions-undo-toast';
    el.innerHTML = `<span class="me-2">${escapeHtml(headline)}</span>`
        + `<button type="button" class="btn btn-sm btn-outline-primary">${escapeHtml(label)}</button>`;
    document.body.appendChild(el);

    el.querySelector('button').addEventListener('click', async() => {
        try {
            const result = await Ajax.call([{
                methodname: 'local_quickactions_undo',
                args: {courseid: config.courseid, undoid: data.undoid},
            }])[0];
            const msg = await getString('undo_success', 'local_quickactions', result.restored);
            Notification.addNotification({message: msg, type: 'success'});
            window.location.reload();
        } catch (err) {
            Notification.exception(err);
        }
    });

    setTimeout(() => el.remove(), 30000);
};

const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => (
    {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]
));

export const getConfig = () => config;
