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
 * Action button -> dialog -> preview -> execute flow.
 *
 * @module     local_quickactions/actions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
import {getSelectedCmids, getSelectedSectionIds, hasSelection} from 'local_quickactions/selection';

const SELECTORS = {
    panel: '#local-quickactions-panel',
    actionBtn: '[data-region="qa-action"]',
    dialog: '[data-region="qa-dialog"]',
    actionsGrid: '.local-quickactions-actions-grid',
};

let config = null;
let courseContext = null;
let currentActionId = null;
let pendingParams = null;

export const init = (cfg) => {
    config = cfg;

    const panel = document.querySelector(SELECTORS.panel);
    if (!panel) {
        return;
    }

    panel.addEventListener('click', async(e) => {
        const btn = e.target.closest(SELECTORS.actionBtn);
        if (btn) {
            await openActionDialog(btn.dataset.actionId);
            return;
        }
        if (e.target.closest('[data-action="qa-back"]')) {
            backToActionGrid();
            return;
        }
        if (e.target.closest('[data-action="qa-preview"]')) {
            await runPreview();
            return;
        }
        if (e.target.closest('[data-action="qa-cancel"]')) {
            backToActionGrid();
            return;
        }
        if (e.target.closest('[data-action="qa-apply"]')) {
            await runExecute();
            return;
        }
    });

    document.addEventListener('local_quickactions:panelopened', async() => {
        if (courseContext === null) {
            try {
                courseContext = await Ajax.call([{
                    methodname: 'local_quickactions_get_context',
                    args: {courseid: config.courseid},
                }])[0];
            } catch (err) {
                courseContext = {sections: [], actions: []};
            }
        }
    });
};

const openActionDialog = async(actionId) => {
    currentActionId = actionId;

    if (['visibility', 'dateshift', 'move', 'completion_template'].includes(actionId) && !hasSelection()) {
        const msg = await getString('error_noselection', 'local_quickactions');
        await Notification.alert(msg, msg);
        return;
    }

    const dialog = document.querySelector(SELECTORS.dialog);
    const grid = document.querySelector(SELECTORS.actionsGrid);

    let template;
    let data;
    switch (actionId) {
        case 'visibility':
            template = 'local_quickactions/dialog_visibility';
            data = {};
            break;
        case 'dateshift':
            template = 'local_quickactions/dialog_dateshift';
            data = {};
            break;
        case 'section_duplicate':
            template = 'local_quickactions/dialog_section_duplicate';
            data = {sections: courseContext?.sections || []};
            break;
        case 'move':
            template = 'local_quickactions/dialog_move';
            data = {sections: courseContext?.sections || []};
            break;
        case 'completion_template': {
            // Combine direct cmids and cmids inside selected sections (DOM-walk).
            const allCmids = new Set(getSelectedCmids());
            getSelectedSectionIds().forEach((sid) => {
                document.querySelectorAll(
                    `[data-for="section"][data-id="${sid}"] [data-for="cmitem"][data-id]`
                ).forEach((cmEl) => {
                    const cmid = parseInt(cmEl.dataset.id, 10);
                    if (cmid) {
                        allCmids.add(cmid);
                    }
                });
            });
            const candidates = Array.from(allCmids).map((cmid) => {
                const el = document.querySelector(`[data-for="cmitem"][data-id="${cmid}"]`);
                const name = el?.querySelector('.activity-item .instancename, .activity-item a')?.textContent?.trim()
                    || `cm ${cmid}`;
                return {cmid: cmid, label: name};
            });
            template = 'local_quickactions/dialog_completion_template';
            data = {candidates};
            break;
        }
        default:
            return;
    }

    try {
        const {html, js} = await Templates.renderForPromise(template, data);
        dialog.innerHTML = '';
        Templates.appendNodeContents(dialog, html, js);
        grid.hidden = true;
        dialog.hidden = false;
    } catch (err) {
        Notification.exception(err);
    }
};

const backToActionGrid = () => {
    const dialog = document.querySelector(SELECTORS.dialog);
    const grid = document.querySelector(SELECTORS.actionsGrid);
    dialog.innerHTML = '';
    dialog.hidden = true;
    grid.hidden = false;
    currentActionId = null;
    pendingParams = null;
};

const collectActionParams = () => {
    switch (currentActionId) {
        case 'visibility': {
            const mode = document.querySelector('input[name="qa-visibility-mode"]:checked')?.value;
            return {mode};
        }
        case 'dateshift': {
            const dateStr = document.getElementById('qa-dateshift-target').value;
            // Datetime-local value e.g. "2026-06-01T14:30" — interpreted as local time.
            const target = dateStr ? Math.floor(new Date(dateStr).getTime() / 1000) : 0;
            return {targetdate: target};
        }
        case 'section_duplicate': {
            return {
                sectionid: parseInt(document.getElementById('qa-secdup-section').value, 10) || 0,
                position: document.getElementById('qa-secdup-position').value,
            };
        }
        case 'move': {
            return {
                targetsectionid: parseInt(document.getElementById('qa-move-target').value, 10) || 0,
            };
        }
        case 'completion_template': {
            return {
                templatecmid: parseInt(document.getElementById('qa-completion-template-cm').value, 10) || 0,
            };
        }
        default:
            return {};
    }
};

const runPreview = async() => {
    const cmids = currentActionId === 'section_duplicate' ? [] : getSelectedCmids();
    const params = collectActionParams();
    if (currentActionId !== 'section_duplicate') {
        params.sectionids = getSelectedSectionIds();
    }
    pendingParams = params;

    try {
        const result = await Ajax.call([{
            methodname: 'local_quickactions_preview',
            args: {
                courseid: config.courseid,
                actionid: currentActionId,
                cmids: cmids,
                paramsjson: JSON.stringify(params),
            },
        }])[0];

        const {html, js} = await Templates.renderForPromise('local_quickactions/preview_table', {
            intro: await getString('dialog_preview_intro', 'local_quickactions'),
            rows: result.rows,
            applyStr: await getString('dialog_apply', 'local_quickactions'),
            cancelStr: await getString('dialog_cancel', 'local_quickactions'),
        });

        const dialog = document.querySelector(SELECTORS.dialog);
        dialog.innerHTML = '';
        Templates.appendNodeContents(dialog, html, js);
    } catch (err) {
        Notification.exception(err);
    }
};

const runExecute = async() => {
    const cmids = currentActionId === 'section_duplicate' ? [] : getSelectedCmids();
    const params = pendingParams || collectActionParams();
    if (currentActionId !== 'section_duplicate' && !params.sectionids) {
        params.sectionids = getSelectedSectionIds();
    }

    if (cmids.length >= (config.confirmThreshold || 10)) {
        const confirmStr = await getString('dialog_confirm_text', 'local_quickactions', cmids.length);
        const titleStr = await getString('dialog_confirm_title', 'local_quickactions');
        try {
            await new Promise((resolve, reject) => {
                Notification.confirm(titleStr, confirmStr, 'OK', 'Cancel', resolve, reject);
            });
        } catch (e) {
            return;
        }
    }

    try {
        const result = await Ajax.call([{
            methodname: 'local_quickactions_execute',
            args: {
                courseid: config.courseid,
                actionid: currentActionId,
                cmids: cmids,
                paramsjson: JSON.stringify(params),
            },
        }])[0];

        // Stash undo id for next page load — undo button is rendered then.
        if (result.undoid) {
            try {
                sessionStorage.setItem('local_quickactions:lastundo', JSON.stringify({
                    undoid: result.undoid,
                    courseid: config.courseid,
                    ts: Date.now(),
                }));
            } catch (e) {
                // Storage disabled — undo button won't appear, action still succeeded.
            }
        }

        if (result.failed === 0) {
            const msg = await getString('result_success', 'local_quickactions', result.success);
            Notification.addNotification({message: msg, type: 'success'});
        } else {
            const msg = await getString('result_partial', 'local_quickactions', {
                success: result.success,
                total: result.success + result.failed,
            });
            Notification.addNotification({message: msg, type: 'warning'});
        }

        // Reload to reflect changes; signal main.js to auto-reopen panel.
        try {
            sessionStorage.setItem('local_quickactions:autoopen', '1');
        } catch (e) {
            // Storage may be disabled; ignore.
        }
        window.location.reload();
    } catch (err) {
        Notification.exception(err);
    }
};
