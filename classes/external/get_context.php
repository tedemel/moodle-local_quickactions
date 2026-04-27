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
 * Web service: get_context.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class get_context extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
        ]);
    }

    public static function execute(int $courseid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $courseid = (int)$params['courseid'];

        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/quickactions:use', $context);

        global $DB;
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $sectionlist = [];
        foreach ($sections as $s) {
            $name = $s->name !== null && $s->name !== ''
                ? format_string($s->name)
                : get_string('section') . ' ' . $s->section;
            $sectionlist[] = [
                'id' => (int)$s->id,
                'number' => (int)$s->section,
                'name' => $name,
            ];
        }

        $actions = \local_quickactions\action\registry::get_metadata_for_context($context);

        return [
            'sections' => $sectionlist,
            'actions' => $actions,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Section id'),
                    'number' => new external_value(PARAM_INT, 'Section number'),
                    'name' => new external_value(PARAM_TEXT, 'Section name'),
                ])
            ),
            'actions' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_ALPHANUMEXT, 'Action id'),
                    'name' => new external_value(PARAM_TEXT, 'Display name'),
                    'description' => new external_value(PARAM_TEXT, 'Description'),
                    'icon' => new external_value(PARAM_ALPHANUMEXT, 'Icon name'),
                ])
            ),
        ]);
    }
}
