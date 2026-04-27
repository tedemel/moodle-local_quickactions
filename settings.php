<?php
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
 * Admin settings for local_quickactions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_quickactions',
        get_string('pluginname', 'local_quickactions')
    );
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_quickactions/enabled',
        get_string('setting_enabled', 'local_quickactions'),
        get_string('setting_enabled_desc', 'local_quickactions'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'local_quickactions/defaultselectionmode',
        get_string('setting_defaultselection', 'local_quickactions'),
        get_string('setting_defaultselection_desc', 'local_quickactions'),
        'checkboxes',
        [
            'checkboxes' => get_string('selection_checkboxes', 'local_quickactions'),
            'lasso'      => get_string('selection_lasso', 'local_quickactions'),
            'both'       => get_string('selection_both', 'local_quickactions'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'local_quickactions/fabposition',
        get_string('setting_fabposition', 'local_quickactions'),
        get_string('setting_fabposition_desc', 'local_quickactions'),
        'bottom-right',
        [
            'bottom-right'  => get_string('fab_bottomright', 'local_quickactions'),
            'bottom-left'   => get_string('fab_bottomleft', 'local_quickactions'),
            'bottom-center' => get_string('fab_bottomcenter', 'local_quickactions'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'local_quickactions/confirmthreshold',
        get_string('setting_confirmthreshold', 'local_quickactions'),
        get_string('setting_confirmthreshold_desc', 'local_quickactions'),
        '10',
        PARAM_INT
    ));
}
