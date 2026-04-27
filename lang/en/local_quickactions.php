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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings for local_quickactions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quick Actions';

$string['setting_enabled'] = 'Enable Quick Actions';
$string['setting_enabled_desc'] = 'Show the floating Quick Actions button in course edit mode.';
$string['setting_defaultselection'] = 'Default selection mode';
$string['setting_defaultselection_desc'] = 'How should activities be selected for bulk actions? "Checkboxes" adds a checkbox next to each activity. "Lasso" allows drawing a selection rectangle. "Both" shows checkboxes and enables lasso simultaneously.';
$string['selection_checkboxes'] = 'Checkboxes only';
$string['selection_lasso'] = 'Lasso (drag rectangle) only';
$string['selection_both'] = 'Both checkboxes and lasso';
$string['setting_fabposition'] = 'Floating button position';
$string['setting_fabposition_desc'] = 'Where the floating Quick Actions button appears in the viewport.';
$string['fab_bottomright'] = 'Bottom right';
$string['fab_bottomleft'] = 'Bottom left';
$string['fab_bottomcenter'] = 'Bottom center';
$string['setting_confirmthreshold'] = 'Confirmation threshold';
$string['setting_confirmthreshold_desc'] = 'When an action affects this many activities or more, an extra confirmation dialog is shown.';

$string['fab_label'] = 'Quick Actions';
$string['fab_aria'] = 'Open Quick Actions panel';
$string['panel_title'] = 'Quick Actions';
$string['panel_subtitle_none'] = 'Select activities to act on';
$string['panel_subtitle_count'] = 'Selected: {$a}';
$string['panel_close'] = 'Close';
$string['panel_clear'] = 'Clear selection';
$string['panel_selectall'] = 'Select all';
$string['panel_lasso_hint'] = 'Lasso active: hold Shift and drag a rectangle';

$string['action_visibility'] = 'Toggle visibility';
$string['action_visibility_desc'] = 'Show or hide selected activities.';
$string['action_visibility_show'] = 'Show selected';
$string['action_visibility_hide'] = 'Hide selected';
$string['action_visibility_stealth'] = 'Make stealth (available but hidden)';

$string['action_dateshift'] = 'Shift dates';
$string['action_dateshift_desc'] = 'Move all relevant dates of selected activities by a given amount.';
$string['action_dateshift_amount'] = 'Shift by';
$string['action_dateshift_unit'] = 'Unit';
$string['action_dateshift_unit_days'] = 'Days';
$string['action_dateshift_unit_weeks'] = 'Weeks';
$string['action_dateshift_unit_months'] = 'Months';
$string['action_dateshift_direction'] = 'Direction';
$string['action_dateshift_forward'] = 'Forward (later)';
$string['action_dateshift_backward'] = 'Backward (earlier)';

$string['action_section_duplicate'] = 'Duplicate section with contents';
$string['action_section_duplicate_desc'] = 'Create a copy of the selected section, including all its activities.';
$string['action_section_duplicate_position'] = 'Place new section';
$string['action_section_duplicate_after'] = 'Right after original';
$string['action_section_duplicate_end'] = 'At the end of the course';

$string['action_move'] = 'Move to section';
$string['action_move_desc'] = 'Move selected activities into another section of this course.';
$string['action_move_target'] = 'Target section';

$string['action_completion_template'] = 'Apply completion from template';
$string['action_completion_template_desc'] = 'Pick one selected activity as the template — its completion settings are applied to all other selected activities.';
$string['action_completion_template_pick'] = 'Pick template';
$string['error_completion_needs_two'] = 'Select at least two activities (one template + targets).';

$string['reason_move_already_there'] = 'All selected activities are already in the target section.';
$string['reason_dateshift_no_dates'] = 'None of the selected activities have shiftable date fields.';
$string['reason_completion_template_none'] = 'The template has no completion settings — applying it would clear all targets.';
$string['reason_completion_template_match'] = 'All target activities already match the template completion settings.';

$string['dialog_preview'] = 'Preview';
$string['dialog_preview_intro'] = 'The following changes will be applied:';
$string['dialog_apply'] = 'Apply';
$string['dialog_cancel'] = 'Cancel';
$string['dialog_confirm_title'] = 'Confirm action';
$string['dialog_confirm_text'] = 'This will affect {$a} items. Are you sure?';
$string['dialog_done'] = 'Done';
$string['dialog_running'] = 'Applying...';

$string['result_success'] = 'Action completed successfully on {$a} items.';
$string['result_partial'] = 'Action partially succeeded: {$a->success} of {$a->total} items.';
$string['result_failure'] = 'Action failed.';

$string['error_noselection'] = 'No activities selected.';
$string['error_invalidcourse'] = 'Invalid course context.';
$string['error_permissiondenied'] = 'You do not have permission to perform this action.';
$string['error_invalidcm'] = 'One or more selected items are invalid.';
$string['error_invalidsection'] = 'Invalid section.';
$string['error_dateshift_zero'] = 'Please pick a target date.';
$string['error_actionnotfound'] = 'Unknown action.';

$string['quickactions:use'] = 'Use the Quick Actions panel';
$string['quickactions:bulkupdate'] = 'Apply bulk actions to course modules';
$string['quickactions:duplicatesection'] = 'Duplicate course sections via Quick Actions';

$string['privacy:metadata'] = 'The Quick Actions plugin does not store any personal data. All operations are performed in real time on existing Moodle data, using existing Moodle APIs.';

$string['event_actionexecuted'] = 'Quick action executed';

$string['task_cleanup_undo'] = 'Expire stale Quick Actions undo snapshots';
$string['undo_button'] = 'Undo';
$string['undo_success'] = 'Reverted: {$a} items restored.';
$string['undo_failed'] = 'Undo failed.';
$string['undo_expired'] = 'This undo record has expired.';

// User tour.
$string['tour_name'] = 'Quick Actions intro';
$string['tour_desc'] = 'Learn in a few steps how to use the Quick Actions panel.';
$string['tour_step1_title'] = 'Open Quick Actions';
$string['tour_step1_content'] = 'This floating button opens the Quick Actions panel — bundling frequent bulk operations for your course.';
$string['tour_step2_title'] = 'Select & act';
$string['tour_step2_content'] = 'When the panel is open, checkboxes appear next to every activity and section. Mark the items the action should apply to, then pick one of the four actions.';
$string['tour_step3_title'] = 'Mark activities';
$string['tour_step3_content'] = 'Tick the box next to an activity to select it. Multiple activities can be selected at once.';
$string['tour_step4_title'] = 'Mark sections';
$string['tour_step4_content'] = 'Whole sections can be selected too — visibility and dates then apply to all activities inside.';
$string['tour_step5_title'] = 'Five actions';
$string['tour_step5_content'] = '<ul><li><b>Visibility:</b> show, hide, or stealth</li><li><b>Shift dates:</b> pick a target date and time</li><li><b>Duplicate section:</b> with all its content</li><li><b>Move to section:</b> move activities between sections</li><li><b>Apply completion from template:</b> copy completion settings of one selected activity to all the others</li></ul>Each action shows a preview before applying.';
