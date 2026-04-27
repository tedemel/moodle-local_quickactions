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
 * Imports the bundled Quick Actions user tour.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\local;

class tour_installer {

    /**
     * Import the bundled tour JSON if tool_usertours is available
     * and a tour with the same name does not already exist.
     */
    public static function install(): void {
        global $CFG, $DB;

        if (!class_exists('\\tool_usertours\\manager')) {
            return;
        }

        $jsonfile = $CFG->dirroot . '/local/quickactions/tours/quickactions_tour.json';
        if (!file_exists($jsonfile)) {
            return;
        }

        try {
            // Avoid duplicate import.
            $existing = $DB->get_record('tool_usertours_tours', ['name' => 'tour_name,local_quickactions']);
            if ($existing) {
                return;
            }
            $json = file_get_contents($jsonfile);
            \tool_usertours\manager::import_tour_from_json($json);
        } catch (\Throwable $e) {
            debugging('local_quickactions: tour install failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
