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
 * FAB toggle module.
 *
 * @module     local_quickactions/fab
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as tourRepo from 'tool_usertours/repository';
import BootstrapTour from 'tool_usertours/tour';
import Templates from 'core/templates';

const SELECTORS = {
    root: '#local-quickactions-root',
    fab: '[data-region="qa-fab"]',
    panel: '#local-quickactions-panel',
    closeBtn: '[data-action="qa-close"]',
    helpBtn: '[data-action="qa-help"]',
};

let panelOpen = false;
let cachedConfig = null;

const TOUR_SHOWN_KEY = 'local_quickactions:tourshown';

export const init = (config) => {
    cachedConfig = config;
    const root = document.querySelector(SELECTORS.root);
    if (!root) {
        return;
    }

    const fab = root.querySelector(SELECTORS.fab);
    const panel = root.querySelector(SELECTORS.panel);

    root.classList.add(`qa-pos-${config.fabPosition || 'bottom-right'}`);
    root.classList.add(`qa-mode-${config.selectionMode || 'checkboxes'}`);

    fab.addEventListener('click', () => {
        const wasOpen = panelOpen;
        togglePanel(panel, fab);
        // Auto-start tour the first time the FAB opens the panel in a session.
        if (!wasOpen && config.tourId && !sessionStorage.getItem(TOUR_SHOWN_KEY)) {
            sessionStorage.setItem(TOUR_SHOWN_KEY, '1');
            setTimeout(() => startTour(config.tourId), 400);
        }
    });

    panel.querySelector(SELECTORS.closeBtn)?.addEventListener('click', () => {
        closePanel(panel, fab);
    });

    panel.querySelector(SELECTORS.helpBtn)?.addEventListener('click', () => {
        if (cachedConfig?.tourId) {
            startTour(cachedConfig.tourId);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && panelOpen) {
            closePanel(panel, fab);
        }
    });
};

const togglePanel = (panel, fab) => {
    if (panelOpen) {
        closePanel(panel, fab);
    } else {
        openPanel(panel, fab);
    }
};

const openPanel = (panel, fab) => {
    panel.hidden = false;
    fab.setAttribute('aria-expanded', 'true');
    panelOpen = true;
    document.dispatchEvent(new CustomEvent('local_quickactions:panelopened'));
};

const closePanel = (panel, fab) => {
    panel.hidden = true;
    fab.setAttribute('aria-expanded', 'false');
    panelOpen = false;
    document.dispatchEvent(new CustomEvent('local_quickactions:panelclosed'));
};

/**
 * Start the bundled user tour by id. Mirrors what tool_usertours/usertours
 * does internally for the matching tour, but bypasses the path-match.
 *
 * @param {number} tourId
 */
const startTour = async(tourId) => {
    try {
        // Close the panel first so its FIXED-position card doesn't overlap the tour popovers.
        const panel = document.querySelector(SELECTORS.panel);
        const fab = document.querySelector(SELECTORS.fab);
        if (panel && !panel.hidden) {
            panel.hidden = true;
            fab?.setAttribute('aria-expanded', 'false');
            panelOpen = false;
            document.dispatchEvent(new CustomEvent('local_quickactions:panelclosed'));
        }
        // Scroll to the top so the FAB / first step target is in view.
        window.scrollTo({top: 0, behavior: 'instant'});

        await tourRepo.resetTourState(tourId);
        const response = await tourRepo.fetchTour(tourId);
        if (!response || !response.tourconfig) {
            return;
        }
        const cfg = response.tourconfig;
        const {html} = await Templates.renderForPromise('tool_usertours/tourstep', cfg);
        cfg.tourName = cfg.name;
        delete cfg.name;
        cfg.template = html;
        cfg.steps = (cfg.steps || []).map((step) => {
            if (step.element !== undefined) {
                step.target = step.element;
                delete step.element;
            }
            if (step.reflex !== undefined) {
                step.moveOnClick = !!step.reflex;
                delete step.reflex;
            }
            if (step.content !== undefined) {
                step.body = step.content;
                delete step.content;
            }
            return step;
        });
        const tour = new BootstrapTour(cfg);
        tour.startTour();
    } catch (e) {
        if (window.console) {
            window.console.error('local_quickactions: tour failed', e);
        }
    }
};
