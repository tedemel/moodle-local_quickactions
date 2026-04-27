// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Multi-select for course modules and sections.
 *
 * @module     local_quickactions/selection
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    root: '#local-quickactions-root',
    panel: '#local-quickactions-panel',
    subtitle: '[data-region="qa-subtitle"]',
    selectAllBtn: '[data-action="qa-selectall"]',
    clearBtn: '[data-action="qa-clear"]',
    cmItem: '[data-for="cmitem"][data-id]',
    section: '[data-for="section"][data-id]',
    sectionTitle: '[data-for="section_title"]',
    lassoOverlay: '[data-region="qa-lasso"]',
    lassoRect: '[data-region="qa-lasso-rect"]',
};

const state = {
    selectedCms: new Set(),
    selectedSections: new Set(),
    mode: 'checkboxes',
};

export const init = (config) => {
    state.mode = config.selectionMode || 'checkboxes';

    document.addEventListener('local_quickactions:panelopened', enableSelection);
    document.addEventListener('local_quickactions:panelclosed', disableSelection);

    const panel = document.querySelector(SELECTORS.panel);
    panel?.querySelector(SELECTORS.selectAllBtn)?.addEventListener('click', selectAll);
    panel?.querySelector(SELECTORS.clearBtn)?.addEventListener('click', clearSelection);
};

const enableSelection = () => {
    if (state.mode === 'checkboxes' || state.mode === 'both') {
        document.querySelectorAll(SELECTORS.cmItem).forEach(addCmCheckbox);
        document.querySelectorAll(SELECTORS.section).forEach(addSectionCheckbox);
    }
    // Lasso only on pointer-fine devices — no touch-drag confusion on mobile.
    const isTouch = window.matchMedia('(pointer: coarse)').matches;
    if (!isTouch && (state.mode === 'lasso' || state.mode === 'both')) {
        document.addEventListener('mousedown', onLassoStart);
    }
    document.body.classList.add('local-quickactions-selecting');
};

const disableSelection = () => {
    document.querySelectorAll('.local-quickactions-checkbox, .local-quickactions-section-checkbox')
        .forEach((cb) => cb.remove());
    document.removeEventListener('mousedown', onLassoStart);
    state.selectedCms.clear();
    state.selectedSections.clear();
    updateSubtitle();
    document.querySelectorAll('.local-quickactions-selected').forEach((el) =>
        el.classList.remove('local-quickactions-selected'));
    document.body.classList.remove('local-quickactions-selecting');
};

const addCmCheckbox = (cmEl) => {
    if (cmEl.querySelector('.local-quickactions-checkbox')) {
        return;
    }
    const cmid = parseInt(cmEl.dataset.id, 10);
    if (!cmid) {
        return;
    }
    // Insert into the inner activity-item row so the checkbox sits next to the icon.
    const target = cmEl.querySelector('.activity-item') || cmEl;
    const wrapper = document.createElement('label');
    wrapper.className = 'local-quickactions-checkbox';
    wrapper.innerHTML = `<input type="checkbox" data-cmid="${cmid}">`;
    target.prepend(wrapper);
    wrapper.querySelector('input').addEventListener('change', (e) => {
        toggleCm(cmid, e.target.checked, cmEl);
    });
};

const addSectionCheckbox = (sectionEl) => {
    if (sectionEl.querySelector(':scope > .local-quickactions-section-checkbox')) {
        return;
    }
    const sectionid = parseInt(sectionEl.dataset.id, 10);
    if (!sectionid) {
        return;
    }
    const wrapper = document.createElement('label');
    wrapper.className = 'local-quickactions-section-checkbox';
    wrapper.innerHTML = `<input type="checkbox" data-sectionid="${sectionid}" title="Sektion auswählen">`;
    sectionEl.appendChild(wrapper);
    wrapper.querySelector('input').addEventListener('change', (e) => {
        toggleSection(sectionid, e.target.checked, sectionEl);
    });
};

const toggleCm = (cmid, selected, el) => {
    if (selected) {
        state.selectedCms.add(cmid);
        el.classList.add('local-quickactions-selected');
    } else {
        state.selectedCms.delete(cmid);
        el.classList.remove('local-quickactions-selected');
    }
    updateSubtitle();
};

const toggleSection = (sectionid, selected, el) => {
    if (selected) {
        state.selectedSections.add(sectionid);
        el.classList.add('local-quickactions-selected');
    } else {
        state.selectedSections.delete(sectionid);
        el.classList.remove('local-quickactions-selected');
    }
    updateSubtitle();
};

const selectAll = () => {
    document.querySelectorAll(SELECTORS.cmItem).forEach((el) => {
        const cmid = parseInt(el.dataset.id, 10);
        if (!cmid) {
            return;
        }
        state.selectedCms.add(cmid);
        el.classList.add('local-quickactions-selected');
        const cb = el.querySelector(':scope > .local-quickactions-checkbox input');
        if (cb) {
            cb.checked = true;
        }
    });
    updateSubtitle();
};

const clearSelection = () => {
    state.selectedCms.clear();
    state.selectedSections.clear();
    document.querySelectorAll('.local-quickactions-selected').forEach((el) =>
        el.classList.remove('local-quickactions-selected'));
    document.querySelectorAll('.local-quickactions-checkbox input:checked, .local-quickactions-section-checkbox input:checked')
        .forEach((cb) => {
            cb.checked = false;
        });
    updateSubtitle();
};

const updateSubtitle = () => {
    const subtitle = document.querySelector(SELECTORS.subtitle);
    if (!subtitle) {
        return;
    }
    const cmCount = state.selectedCms.size;
    const sectionCount = state.selectedSections.size;
    if (cmCount + sectionCount === 0) {
        subtitle.textContent = subtitle.dataset.emptyText;
        return;
    }
    const parts = [];
    if (cmCount > 0) {
        parts.push(`${cmCount} Aktivität${cmCount === 1 ? '' : 'en'}`);
    }
    if (sectionCount > 0) {
        parts.push(`${sectionCount} Sektion${sectionCount === 1 ? '' : 'en'}`);
    }
    subtitle.textContent = `Ausgewählt: ${parts.join(' + ')}`;
};

let lassoStart = null;
let lassoEl = null;

const onLassoStart = (e) => {
    if (!e.shiftKey) {
        return;
    }
    if (e.target.closest(SELECTORS.panel)) {
        return;
    }
    lassoStart = {x: e.clientX, y: e.clientY};
    const overlay = document.querySelector(SELECTORS.lassoOverlay);
    const rect = document.querySelector(SELECTORS.lassoRect);
    if (!overlay || !rect) {
        return;
    }
    overlay.hidden = false;
    rect.style.left = `${e.clientX}px`;
    rect.style.top = `${e.clientY}px`;
    rect.style.width = '0';
    rect.style.height = '0';
    lassoEl = rect;
    document.addEventListener('mousemove', onLassoMove);
    document.addEventListener('mouseup', onLassoEnd);
    e.preventDefault();
};

const onLassoMove = (e) => {
    if (!lassoStart || !lassoEl) {
        return;
    }
    const x = Math.min(e.clientX, lassoStart.x);
    const y = Math.min(e.clientY, lassoStart.y);
    const w = Math.abs(e.clientX - lassoStart.x);
    const h = Math.abs(e.clientY - lassoStart.y);
    lassoEl.style.left = `${x}px`;
    lassoEl.style.top = `${y}px`;
    lassoEl.style.width = `${w}px`;
    lassoEl.style.height = `${h}px`;
};

const onLassoEnd = () => {
    if (!lassoStart || !lassoEl) {
        return;
    }
    const rectBounds = lassoEl.getBoundingClientRect();
    document.querySelectorAll(SELECTORS.cmItem).forEach((el) => {
        if (intersects(rectBounds, el.getBoundingClientRect())) {
            const cmid = parseInt(el.dataset.id, 10);
            if (cmid) {
                state.selectedCms.add(cmid);
                el.classList.add('local-quickactions-selected');
                const cb = el.querySelector(':scope > .local-quickactions-checkbox input');
                if (cb) {
                    cb.checked = true;
                }
            }
        }
    });
    updateSubtitle();
    document.querySelector(SELECTORS.lassoOverlay).hidden = true;
    document.removeEventListener('mousemove', onLassoMove);
    document.removeEventListener('mouseup', onLassoEnd);
    lassoStart = null;
    lassoEl = null;
};

const intersects = (a, b) =>
    !(a.right < b.left || a.left > b.right || a.bottom < b.top || a.top > b.bottom);

export const getSelectedCmids = () => Array.from(state.selectedCms);
export const getSelectedSectionIds = () => Array.from(state.selectedSections);
export const hasSelection = () => state.selectedCms.size + state.selectedSections.size > 0;
