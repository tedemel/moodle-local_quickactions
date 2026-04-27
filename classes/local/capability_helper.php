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
 * Capability helper for local_quickactions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\local;

class capability_helper {

    public static function can_use(int $courseid): bool {
        $context = \context_course::instance($courseid);
        return has_capability('local/quickactions:use', $context);
    }

    public static function can_bulk_update(int $courseid): bool {
        $context = \context_course::instance($courseid);
        return has_capability('local/quickactions:bulkupdate', $context);
    }

    public static function can_duplicate_section(int $courseid): bool {
        $context = \context_course::instance($courseid);
        return has_capability('local/quickactions:duplicatesection', $context);
    }
}
