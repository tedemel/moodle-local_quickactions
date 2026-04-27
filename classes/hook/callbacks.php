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
 * Hook callbacks for local_quickactions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\hook;

/**
 * Class callbacks.
 */
class callbacks {

    /**
     * before_standard_top_of_body_html.
     */
    public static function before_standard_top_of_body_html(
        \core\hook\output\before_standard_top_of_body_html_generation $hook
    ): void {
        global $PAGE, $COURSE;

        if (!get_config('local_quickactions', 'enabled')) {
            return;
        }

        if (empty($COURSE) || (int)$COURSE->id <= 1) {
            return;
        }
        if (empty($PAGE->user_is_editing())) {
            return;
        }

        $context = \context_course::instance((int)$COURSE->id);
        if (!has_capability('local/quickactions:use', $context)) {
            return;
        }

        try {
            /** @var \local_quickactions\output\renderer $renderer */
            $renderer = $PAGE->get_renderer('local_quickactions');
            $html = $renderer->render_fab_and_panel((int)$COURSE->id);
            $hook->add_html($html);

            $tourid = self::get_tour_id();
            $PAGE->requires->js_call_amd(
                'local_quickactions/main',
                'init',
                [
                    [
                        'courseid' => (int)$COURSE->id,
                        'selectionMode' => get_config('local_quickactions', 'defaultselectionmode') ?: 'checkboxes',
                        'fabPosition' => get_config('local_quickactions', 'fabposition') ?: 'bottom-right',
                        'confirmThreshold' => (int)(get_config('local_quickactions', 'confirmthreshold') ?: 10),
                        'canDuplicate' => has_capability('local/quickactions:duplicatesection', $context),
                        'canBulkUpdate' => has_capability('local/quickactions:bulkupdate', $context),
                        'tourId' => $tourid,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            debugging('local_quickactions: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Find the bundled tour's id (or 0 if tool_usertours not installed).
     */
    private static function get_tour_id(): int {
        global $DB;
        try {
            $row = $DB->get_record(
                'tool_usertours_tours',
                ['name' => 'tour_name,local_quickactions'],
                'id',
                IGNORE_MISSING
            );
            return $row ? (int)$row->id : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
